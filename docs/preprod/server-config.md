# Configuración de servidor en preprod (EC2)

Este documento lista la configuración que debe estar aplicada en la instancia
EC2 de preprod (i-0a812def1bdfc9127, sa-east-1, EIP 56.126.130.100). Estos
valores **no están en el repo**: viven en archivos del sistema o en variables
de entorno y se pierden si se reconstruye la instancia sin re-aplicarlos.

Si después de un deploy las observaciones reportan "página de Error" o "no se
carga el archivo", lo primero que hay que revisar son estos valores.

## 1. .env de Laravel (`/var/www/gore/.env` o equivalente)

```dotenv
APP_ENV=preprod
APP_DEBUG=false
APP_URL=https://preprod.gore-valparaiso.cl
APP_KEY=base64:...   # generar con php artisan key:generate

DB_CONNECTION=mariadb
DB_HOST=<endpoint-rds-privado>
DB_PORT=3306
DB_DATABASE=gore_preprod
DB_USERNAME=gore_app
DB_PASSWORD=<secreto>

# Storage: forzar s3 en preprod para evitar perdida si EC2 muere
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<iam-key>
AWS_SECRET_ACCESS_KEY=<iam-secret>
AWS_DEFAULT_REGION=sa-east-1
AWS_BUCKET=<bucket-gore-preprod>
AWS_USE_PATH_STYLE_ENDPOINT=false

# 4 horas: previene CSRF expirado en sesiones largas de formulario de
# observacion con adjunto grande (subir 100MB con conexion lenta).
SESSION_LIFETIME=240
SESSION_DRIVER=database

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=info
```

## 2. IAM mínimo para el bucket S3

Crear un usuario IAM `gore-preprod-app` con esta policy mínima
(reemplazar `${BUCKET}` por el nombre real):

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": "arn:aws:s3:::${BUCKET}"
    },
    {
      "Effect": "Allow",
      "Action": ["s3:PutObject", "s3:GetObject", "s3:DeleteObject"],
      "Resource": "arn:aws:s3:::${BUCKET}/*"
    }
  ]
}
```

El bucket NO debe tener acceso público. Las descargas se sirven via controller
(streamed) con auth, no via URL directa de S3.

## 3. PHP-FPM (`/etc/php/8.x/fpm/php.ini` o `/etc/php.ini`)

```ini
; Subir archivos grandes (antecedentes tecnicos del backoffice llegan a 100MB).
; post_max_size DEBE ser >= upload_max_filesize y sumar margenes para campos
; adicionales del formulario.
upload_max_filesize = 110M
post_max_size = 120M

; Permitir tiempo suficiente para uploads a S3 con latencia (sa-east-1 a EC2
; en la misma region tipicamente es <100ms; con cliente residencial puede ser
; varios minutos para un archivo de 100MB).
max_input_time = 180
max_execution_time = 120

; tmp_dir con permisos para www-data
upload_tmp_dir = /var/lib/php/uploads
```

Reiniciar: `sudo systemctl restart php8.x-fpm`

## 4. Nginx (`/etc/nginx/sites-available/gore.conf`)

En el `server` block:

```nginx
server {
    listen 443 ssl http2;
    server_name preprod.gore-valparaiso.cl;

    # Debe ser >= upload_max_filesize de PHP. Si no, nginx rechaza con 413
    # ANTES de que PHP vea la request, y el ciudadano ve una pagina de error
    # generica sin saber por que.
    client_max_body_size 110M;

    location ~ \.php$ {
        # Timeout extendido para uploads grandes a S3 desde EC2.
        fastcgi_read_timeout 180s;
        fastcgi_send_timeout 180s;

        # Buffers (opcional, ayuda con uploads grandes)
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 32k;
        fastcgi_busy_buffers_size 64k;

        # ... resto de la config fastcgi
    }
}
```

Reiniciar: `sudo nginx -t && sudo systemctl reload nginx`

## 5. Permisos de filesystem

Aunque en preprod los uploads van a S3, Laravel sigue escribiendo en disco
local para logs, cache, sessions y views compiladas:

```bash
sudo chown -R www-data:www-data /var/www/gore/storage /var/www/gore/bootstrap/cache
sudo chmod -R 775 /var/www/gore/storage /var/www/gore/bootstrap/cache
```

## 6. Verificación post-deploy

Ejecutar tras cada deploy:

```bash
cd /var/www/gore
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Comprueba integridad de paths/disks vs archivos en S3
php artisan storage:migrate-paths

# Health-check
curl -i https://preprod.gore-valparaiso.cl/healthz
```

El comando `storage:migrate-paths` reporta huerfanos (filas cuyo
`storage_path` no existe en el disk declarado). Si hay missing tras una
migracion local->s3, antes de promover el deploy ejecutar:

```bash
aws s3 sync /var/www/gore/storage/app/private/ s3://${AWS_BUCKET}/
php artisan storage:migrate-paths --fix-disk
```

## 7. Causas comunes del bug "no se carga el archivo"

Diagnóstico ordenado:

1. **413 Request Entity Too Large** → `client_max_body_size` insuficiente en nginx.
2. **Página de error genérica al subir** → `upload_max_filesize` o `post_max_size` insuficiente en php.ini.
3. **419 Page Expired** → CSRF expirado, subir `SESSION_LIFETIME`.
4. **Excepción AWS S3 silenciada** → resuelto en este sprint cambiando `'throw' => true` en `config/filesystems.php`. Antes los errores se tragaban y solo aparecia el redirect.
5. **`storage_path` apunta a archivo inexistente** → revisar `storage_disk` por fila vs `FILESYSTEM_DISK` actual; correr `storage:migrate-paths --fix-disk`.
6. **IAM sin `s3:PutObject`** → el upload falla con `AccessDenied` (visible en logs ahora que `report => true`).
7. **Bucket en otra region** → latencia + posibles timeouts; verificar `AWS_DEFAULT_REGION`.
