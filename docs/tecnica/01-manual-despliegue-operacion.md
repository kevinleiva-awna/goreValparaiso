# Manual de Despliegue y Operación

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**

| | |
|---|---|
| Versión | 1.0 |
| Fecha | 5 de agosto de 2026 |
| Autor | AWNA |
| Destinatario | Equipo técnico / Unidad de Informática, Gobierno Regional de Valparaíso |
| Documentos relacionados | `02-manual-administrador.md`, `03-diccionario-datos-y-rutas.md`, `README.md` |

---

## Tabla de contenidos

1. [Alcance](#1-alcance)
2. [Ambientes](#2-ambientes)
3. [Arquitectura de despliegue](#3-arquitectura-de-despliegue)
4. [Requisitos del servidor](#4-requisitos-del-servidor)
5. [Provisionamiento desde cero](#5-provisionamiento-desde-cero)
6. [Variables de entorno](#6-variables-de-entorno)
7. [Procedimiento de despliegue](#7-procedimiento-de-despliegue)
8. [Servicios en segundo plano](#8-servicios-en-segundo-plano)
9. [Almacenamiento de archivos y S3](#9-almacenamiento-de-archivos-y-s3)
10. [TLS y renovación de certificados](#10-tls-y-renovación-de-certificados)
11. [Respaldos y restauración](#11-respaldos-y-restauración)
12. [Monitoreo y health-check](#12-monitoreo-y-health-check)
13. [Runbook de incidencias](#13-runbook-de-incidencias)
14. [Rollback](#14-rollback)
15. [Seguridad aplicada y pendientes de hardening](#15-seguridad-aplicada-y-pendientes-de-hardening)

---

## 1. Alcance

Este documento describe cómo desplegar, operar y recuperar la plataforma en
la infraestructura AWS del Gobierno Regional de Valparaíso. Cubre el
procedimiento real verificado en los ambientes vigentes (staging y
producción), no un procedimiento teórico.

Queda **fuera de alcance**: la operación funcional del backoffice (ver
`02-manual-administrador.md`) y el detalle del modelo de datos (ver
`03-diccionario-datos-y-rutas.md`).

---

## 2. Ambientes

Ambos ambientes viven en la **cuenta AWS del cliente `184758133903`**, región
`us-east-1`. Cada instancia EC2 sigue su propia rama de Git y el despliegue
consiste en sincronizar el repositorio con esa rama.

| | Staging | Producción |
|---|---|---|
| Prefijo de recursos | `gore-staging-*` | `gore-prod-*` |
| Rama Git | `dev` | `prod` |
| Instancia EC2 | `i-044e3f43201359d9a` | `i-099343b5b7dffc94f` |
| Tipo / SO | t3.small, Ubuntu 22.04, 30 GB gp3 | t3.small, Ubuntu 22.04, 30 GB gp3 |
| IP elástica | `3.227.228.33` | `3.84.105.83` |
| URL | `http://3.227.228.33` | `https://www.participa.gobiernovalparaiso.cl` |
| VPC | `vpc-067ee7bfbc51a2c35` (10.20.0.0/16) | `vpc-091574e3a5852c68e` (10.30.0.0/16) |
| Base de datos (RDS MariaDB 10.11) | `gore-staging-db`, esquema `gore_staging` | `gore-prod-db`, esquema `gore_prod` |
| Bucket S3 | `gore-staging-uploads-184758133903` | `gore-prod-uploads-184758133903` |
| Rol de instancia | `gore-staging-ec2-role` | `gore-prod-ec2-role` |
| Secretos | SSM `/gore/staging/db-password` | SSM `/gore/prod/db-password` |
| `APP_DEBUG` | `false` | `false` |
| ClaveÚnica | desactivada | desactivada |
| Correo saliente | `log` (no envía) | `log` (hasta verificar SES) |

**Flujo de promoción:** trabajar en `dev` → validar en staging →
`git merge dev` sobre `prod` → desplegar producción.

**Acceso a las instancias:** exclusivamente por **AWS Systems Manager Session
Manager**. No hay SSH abierto (el security group solo publica 80 y 443).

```bash
aws ssm start-session --target i-099343b5b7dffc94f --profile gore
```

---

## 3. Arquitectura de despliegue

```
                    Internet
                       │
                       ▼
        ┌──────────────────────────────┐
        │  EIP 3.84.105.83             │
        │  ┌────────────────────────┐  │
        │  │ EC2 gore-prod-web      │  │
        │  │  nginx (80/443, TLS)   │  │
        │  │   └─ PHP-FPM 8.2       │  │
        │  │       └─ Laravel 12    │  │
        │  │  systemd gore-queue    │  │
        │  │  cron → schedule:run   │  │
        │  └────────────────────────┘  │
        │      VPC 10.30.0.0/16        │
        │   SG web 80/443 · SG db 3306 │
        └───────┬──────────────┬───────┘
                │              │
                ▼              ▼
     ┌─────────────────┐  ┌──────────────────────────┐
     │ RDS MariaDB     │  │ S3 (privado, AES256)     │
     │ gore-prod-db    │  │ antecedentes, adjuntos,  │
     │ privado, 7d bkp │  │ respaldos, deploy/.env   │
     └─────────────────┘  └──────────────────────────┘
```

Notas de diseño relevantes para la operación:

- **La base de datos no es accesible desde Internet.** Solo el security group
  de la web puede alcanzar el puerto 3306.
- **El bucket S3 no tiene acceso público.** Todas las descargas se sirven por
  streaming a través de la aplicación, respetando la sesión y el rol del
  usuario. No se generan URLs firmadas ni públicas.
- **Las credenciales de S3 no viven en el `.env`**: la instancia usa su
  *instance profile* IAM.

---

## 4. Requisitos del servidor

| Componente | Versión mínima | Instalado en los ambientes |
|---|---|---|
| PHP | 8.2 | 8.2.31 (+ FPM) |
| Extensiones PHP | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `intl`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip` | ✔ |
| Composer | 2.x | 2.10.2 |
| Node.js | 20 LTS | 20.20.2 |
| nginx | 1.18 | 1.18 |
| MariaDB (cliente) | 10.x | `mariadb-client` |
| MariaDB (servidor, RDS) | 10.11 | 10.11.x |

`zip` e `intl` son obligatorias: `maatwebsite/excel` no funciona sin ellas y
la exportación de observaciones fallará en tiempo de ejecución, no en el
despliegue.

---

## 5. Provisionamiento desde cero

Esta secuencia reconstruye una instancia web completa. Ejecutar como `root`
vía SSM.

### 5.1 Paquetes base

```bash
apt-get update
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y nginx php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl \
  mariadb-client unzip git curl
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs
HOME=/root php -r "copy('https://getcomposer.org/installer','/tmp/ci.php');"
HOME=/root php /tmp/ci.php --install-dir=/usr/local/bin --filename=composer
```

> **Gotcha SSM desde Windows:** la salida de `add-apt-repository` y de
> NodeSource contiene caracteres no ASCII (`→`) que rompen el cliente
> `aws.exe` v2 con un error `charmap`. Redirigir el ruido a un archivo de log
> en la instancia y devolver solo salida filtrada:
> `... >>/tmp/install.log 2>&1; tail -5 /tmp/install.log | tr -cd '[:print:]\t\n'`.
> Además, exportar `HOME=/root` o el instalador de Composer falla.

### 5.2 Parámetros de PHP

Editar `/etc/php/8.2/fpm/php.ini`:

```ini
; Los antecedentes técnicos del backoffice llegan a 100 MB.
; post_max_size DEBE ser >= upload_max_filesize, con margen para el resto
; de los campos del formulario.
upload_max_filesize = 110M
post_max_size = 120M

max_input_time = 180
max_execution_time = 120
```

```bash
systemctl restart php8.2-fpm
```

### 5.3 Código de la aplicación

```bash
mkdir -p /var/www
git clone https://github.com/<org>/gore-valparaiso.git /var/www/gore
cd /var/www/gore
git checkout prod          # o dev en staging

# El .env NO está en el repositorio: se descarga de S3 con el instance role
aws s3 cp s3://gore-prod-uploads-184758133903/deploy/.env /var/www/gore/.env

composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate     # solo si el .env no trae APP_KEY
php artisan migrate --force

chown -R www-data:www-data /var/www/gore
chmod -R 775 /var/www/gore/storage /var/www/gore/bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> **Importante:** el repositorio en `/var/www/gore` pertenece a `www-data`.
> Ejecutar `git` como `root` falla con *dubious ownership*. Ver §7.

### 5.4 nginx

La configuración de producción está **versionada** en
`docs/staging/gore-prod-nginx.conf` — es la fuente de verdad y permite
reconstruir el servidor. Copiarla a `/etc/nginx/sites-available/gore.conf`,
enlazarla en `sites-enabled` y recargar.

Valores que no se pueden omitir:

```nginx
server {
    listen 443 ssl http2;
    server_name www.participa.gobiernovalparaiso.cl;
    root /var/www/gore/public;

    # Debe ser >= upload_max_filesize de PHP; si no, nginx responde 413
    # ANTES de que PHP vea la petición y el usuario ve un error genérico.
    client_max_body_size 110M;

    location ~ \.php$ {
        fastcgi_read_timeout 180s;
        fastcgi_send_timeout 180s;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 32k;
        fastcgi_busy_buffers_size 64k;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        # ... resto de la configuración fastcgi estándar
    }
}
```

```bash
nginx -t && systemctl reload nginx
```

nginx **no** emite la cabecera HSTS: la emite la aplicación (middleware
`SecurityHeaders`). Duplicarla en nginx provoca cabeceras repetidas.

### 5.5 Primer usuario administrador

La base recién migrada no trae usuarios. El primer super-admin se crea una
única vez por consola; los siguientes se crean desde el backoffice.

```bash
cd /var/www/gore
sudo -u www-data php artisan tinker
```

```php
$p = \Illuminate\Support\Str::password(20);
\App\Models\User::create([
    'national_id' => '18765432-7',
    'name' => 'Administrador',
    'last_name' => 'GORE',
    'email' => 'admin@gorevalparaiso.cl',
    'password' => $p,
    'role' => 'super-admin',
    'is_active' => true,
    'email_verified_at' => now(),
]);
echo $p;   // anotar y entregar por canal seguro; cambiar en el primer ingreso
```

El acceso del personal es `/admin/login`.

---

## 6. Variables de entorno

El archivo `.env` **no se versiona**. La copia de referencia de cada ambiente
vive en `s3://<bucket>/deploy/.env`. Al modificarlo en el servidor, subir la
copia actualizada a S3 para que la instancia sea reconstruible.

```dotenv
APP_NAME="Participa GORE Valparaíso"
APP_ENV=production                 # staging en el otro ambiente
APP_KEY=base64:...                 # php artisan key:generate
APP_DEBUG=false                    # NUNCA true en un ambiente accesible
APP_URL=https://www.participa.gobiernovalparaiso.cl
APP_TIMEZONE=America/Santiago      # sin esto los timestamps quedan en UTC (+4h)
APP_LOCALE=es

LOG_CHANNEL=daily
LOG_LEVEL=info

DB_CONNECTION=mariadb
DB_HOST=gore-prod-db.cmhkw622si29.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=gore_prod
DB_USERNAME=gore_app
DB_PASSWORD=<desde SSM /gore/prod/db-password>

# Sesiones: 4 horas. Un formulario de observación con adjunto grande sobre
# conexión lenta supera los 120 min por defecto y produce 419 Page Expired.
SESSION_DRIVER=database
SESSION_LIFETIME=240

CACHE_STORE=database
QUEUE_CONNECTION=database

# Archivos en S3. SIN claves: la instancia usa su instance profile IAM.
FILESYSTEM_DISK=s3
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=gore-prod-uploads-184758133903
AWS_USE_PATH_STYLE_ENDPOINT=false

MAIL_MAILER=log                    # cambiar a ses cuando el dominio esté verificado
MAIL_FROM_ADDRESS=no-reply@participa.gobiernovalparaiso.cl
MAIL_FROM_NAME="Participa GORE Valparaíso"

# ClaveÚnica: apagada mientras el GORE no tenga registrado el cliente OIDC.
CLAVEUNICA_ENABLED=false
CLAVEUNICA_MODE=live
CLAVEUNICA_CLIENT_ID=
CLAVEUNICA_CLIENT_SECRET=
```

### Notas por variable crítica

| Variable | Consecuencia de un valor incorrecto |
|---|---|
| `APP_DEBUG=true` | Expone trazas con credenciales de base de datos en cualquier error 500. |
| `APP_TIMEZONE` | Ausente, todas las observaciones se graban en UTC y se muestran con 4 horas de desfase. |
| `SESSION_LIFETIME` | Bajo, produce 419 *Page Expired* al enviar observaciones con adjunto. |
| `FILESYSTEM_DISK` | Si vuelve a `local`, los archivos nuevos quedan en el disco de la EC2 y se pierden si la instancia se reconstruye. |
| `CLAVEUNICA_ENABLED` | En `true` sin credenciales, el ciudadano ve un botón de ingreso que falla. |

> **Gotcha al editar el `.env` por consola:** agregar una línea con
> `echo >> .env` la pega a la última línea existente (el archivo no termina en
> salto de línea) e invalida el parseo de *dotenv* completo. Usar
> `printf '\nVARIABLE=valor\n' >> .env` y ejecutar `php artisan config:cache`
> después.

---

## 7. Procedimiento de despliegue

El despliegue es *pull* desde GitHub. **El push a `origin/<rama>` se hace
antes, desde el entorno local.**

### 7.1 Secuencia

```bash
# 1. Sincronizar el código (SIEMPRE como www-data)
sudo -u www-data git -C /var/www/gore fetch origin --prune
sudo -u www-data git -C /var/www/gore reset --hard origin/prod

cd /var/www/gore

# 2. Dependencias, solo si cambió composer.lock
sudo -u www-data composer install --no-dev --optimize-autoloader

# 3. Migraciones, solo si hay archivos nuevos en database/migrations
sudo -u www-data php artisan migrate --force

# 4. Assets, solo si cambiaron resources/js, resources/css o package.json
sudo -u www-data npm ci && sudo -u www-data npm run build

# 5. Cachés según lo que se haya tocado
sudo -u www-data php artisan view:clear  && sudo -u www-data php artisan view:cache    # vistas Blade
sudo -u www-data php artisan config:cache                                              # config o .env
sudo -u www-data php artisan route:cache                                               # rutas

# 6. Reiniciar el worker para que tome el código nuevo
systemctl restart gore-queue
```

### 7.2 Cuándo se puede omitir cada paso

| Cambio | Pasos necesarios |
|---|---|
| Solo plantillas Blade | 1, 5 (`view:*`) |
| Solo clases utilitarias de Bootstrap | 1, 5 (`view:*`) — **no** requiere `npm run build`; Bootstrap se importa completo |
| CSS/JS propio, Sass | 1, 4, 5 |
| Rutas o configuración | 1, 5 (`config:cache` y `route:cache`) |
| Modelo de datos | 1, 3, 5, 6 |
| Dependencias PHP | 1, 2, 5, 6 |

### 7.3 Verificación post-despliegue

```bash
curl -sS -o /dev/null -w '%{http_code}\n' https://www.participa.gobiernovalparaiso.cl/
curl -sS https://www.participa.gobiernovalparaiso.cl/healthz | head -c 400

cd /var/www/gore
sudo -u www-data php artisan storage:migrate-paths     # integridad de rutas de archivos
sudo -u www-data php artisan migrate:status | tail -5
systemctl is-active gore-queue nginx php8.2-fpm
```

`storage:migrate-paths` reporta *huérfanos*: filas cuyo `storage_path` no
existe en el disco declarado en `storage_disk`. Si aparecen después de migrar
de `local` a `s3`:

```bash
aws s3 sync /var/www/gore/storage/app/private/ s3://${AWS_BUCKET}/
sudo -u www-data php artisan storage:migrate-paths --fix-disk
```

---

## 8. Servicios en segundo plano

### 8.1 Worker de colas

Los correos de respuesta institucional se envían con `Mail::queue()` sobre
`QUEUE_CONNECTION=database`. **Sin el worker corriendo, los correos quedan
encolados y nunca salen.**

`/etc/systemd/system/gore-queue.service`:

```ini
[Unit]
Description=GORE queue worker
After=network.target

[Service]
User=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/gore/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload
systemctl enable --now gore-queue
```

`--max-time=3600` recicla el proceso cada hora para que absorba el código
nuevo tras un despliegue. Para efecto inmediato: `systemctl restart gore-queue`.

### 8.2 Planificador (scheduler)

```cron
* * * * * cd /var/www/gore && php artisan schedule:run >> /dev/null 2>&1
```

Instalar en el crontab de `www-data` (`crontab -u www-data -e`). Es la única
entrada necesaria: Laravel resuelve internamente qué tarea corresponde a cada
minuto.

Tareas programadas (`routes/console.php`):

| Tarea | Frecuencia | Qué hace |
|---|---|---|
| `gore:backup-observations` | `0 2 */2 * *` (02:00, cada 2 días) | Respaldo XLSX de las observaciones de las consultas activas |

---

## 9. Almacenamiento de archivos y S3

Todo archivo subido —antecedentes técnicos del backoffice y adjuntos
ciudadanos— se guarda en S3 cuando `FILESYSTEM_DISK=s3`.

**Cada fila recuerda su propio disco** (`consultation_documents.storage_disk`,
`observations.attachment_disk`). Eso permite migrar de `local` a `s3` sin
perder los archivos históricos: la descarga usa el disco de la fila, no el
disco global.

### Política IAM mínima del bucket

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": ["s3:ListBucket"],
      "Resource": "arn:aws:s3:::gore-prod-uploads-184758133903"
    },
    {
      "Effect": "Allow",
      "Action": ["s3:PutObject", "s3:GetObject", "s3:DeleteObject"],
      "Resource": "arn:aws:s3:::gore-prod-uploads-184758133903/*"
    }
  ]
}
```

El bucket tiene **cifrado AES256 en reposo** y bloqueo de acceso público
activo. No debe habilitarse hosting estático ni política pública de lectura.

Prueba de conectividad end-to-end:

```bash
cd /var/www/gore && sudo -u www-data php artisan tinker
```
```php
Storage::disk('s3')->put('healthz/probe.txt', 'ok');
Storage::disk('s3')->get('healthz/probe.txt');
Storage::disk('s3')->delete('healthz/probe.txt');
```

---

## 10. TLS y renovación de certificados

Producción sirve HTTPS con **Let's Encrypt** (certbot 1.21.0 desde APT),
certificado SAN para el ápice y `www`, emitido por **webroot** (desafío
HTTP-01 sobre `/var/www/gore/public`).

- Dominio canónico: `www.participa.gobiernovalparaiso.cl`.
- El ápice y todo el tráfico HTTP hacen **301** al canónico.
- Renovación automática: `certbot.timer` (habilitado y activo) más un
  *deploy hook* en `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh`
  que recarga nginx tras cada renovación.

Verificación:

```bash
systemctl status certbot.timer
certbot certificates
curl -sI https://www.participa.gobiernovalparaiso.cl | grep -i strict-transport
```

> `certbot renew --dry-run` puede quedarse colgado contra el ACME de pruebas.
> No es indicador de falla: verificar en su lugar que el path
> `/.well-known/acme-challenge/` sea servible por nginx y que `certbot.timer`
> esté activo.

Si la institución prefiere su propio certificado (por ejemplo un *wildcard* de
su CA), basta reemplazar `ssl_certificate` / `ssl_certificate_key` en el
vhost y deshabilitar `certbot.timer`.

---

## 11. Respaldos y restauración

### 11.1 Capas de respaldo

| Capa | Mecanismo | Retención | Cubre |
|---|---|---|---|
| Base de datos | Snapshots automáticos de RDS | 7 días | Todo el esquema |
| Observaciones | `gore:backup-observations` cada 48 h | Indefinida (S3) | XLSX de observaciones de consultas activas |
| Archivos | Objetos en S3 (cifrados) | Indefinida | Antecedentes y adjuntos |
| Código | Repositorio Git (ramas `dev` y `prod`) | Indefinida | Aplicación |
| Configuración | `s3://<bucket>/deploy/.env` + `docs/staging/gore-prod-nginx.conf` | Indefinida | `.env` y vhost |

El respaldo de observaciones responde a la exigencia del brief de generar un
respaldo cada 48 horas durante procesos activos. Se puede forzar en cualquier
momento:

```bash
cd /var/www/gore && sudo -u www-data php artisan gore:backup-observations --force
```

Queda en `backups/observations/observations-backup-<fecha>.xlsx`.

### 11.2 Restauración de la base de datos

```bash
# 1. Restaurar el snapshot en una instancia nueva
aws rds restore-db-instance-from-db-snapshot \
  --db-instance-identifier gore-prod-db-restore \
  --db-snapshot-identifier <snapshot-id> \
  --profile gore

# 2. Apuntar la aplicación al endpoint restaurado
#    (editar DB_HOST en /var/www/gore/.env)
cd /var/www/gore && sudo -u www-data php artisan config:cache

# 3. Verificar
curl -s https://www.participa.gobiernovalparaiso.cl/healthz
```

Restaurar **no** repone los archivos: esos viven en S3 y son independientes
del ciclo de vida de la base.

### 11.3 Reconstrucción total de la instancia web

1. Lanzar EC2 Ubuntu 22.04 con el instance profile `gore-prod-ec2-role`.
2. Ejecutar §5.1 a §5.4.
3. Reasociar la EIP `3.84.105.83` (`--allow-reassociation`).
4. Reinstalar el certificado (`certbot --nginx` o restaurar `/etc/letsencrypt`).
5. Verificar según §7.3.

---

## 12. Monitoreo y health-check

`GET /healthz` devuelve JSON y no requiere autenticación (limitado a 30
peticiones por minuto por IP). Verifica conectividad real a la base de datos y
al disco de almacenamiento configurado.

```json
{
  "status": "ok",
  "checks": {
    "database": { "ok": true, "error": null },
    "storage":  { "ok": true, "disk": "s3", "error": null }
  },
  "app_env": "production",
  "elapsed_ms": 42
}
```

- `200` → `status: ok`
- `503` → `status: degraded` con el detalle de qué comprobación falló

Es el endpoint adecuado para un *health check* de balanceador o de un
monitor externo. `/up` (que Laravel provee por defecto) solo confirma que el
framework arranca, sin tocar base ni almacenamiento.

Logs a revisar ante un incidente:

```bash
tail -100 /var/www/gore/storage/logs/laravel-$(date +%F).log
tail -100 /var/log/nginx/error.log
journalctl -u gore-queue -n 100 --no-pager
journalctl -u php8.2-fpm -n 50 --no-pager
```

---

## 13. Runbook de incidencias

### 13.1 "No se carga el archivo" al subir un antecedente o adjunto

Diagnóstico en orden de probabilidad:

| Síntoma | Causa | Solución |
|---|---|---|
| `413 Request Entity Too Large` | `client_max_body_size` insuficiente en nginx | Subirlo a 110M y `systemctl reload nginx` |
| Página de error genérica al subir | `upload_max_filesize` / `post_max_size` insuficientes | Ajustar `php.ini` y reiniciar PHP-FPM |
| `419 Page Expired` | Sesión CSRF vencida durante una carga larga | Subir `SESSION_LIFETIME` |
| `AccessDenied` en el log | IAM sin `s3:PutObject` | Revisar la política del rol de instancia |
| Archivo sube pero no descarga | `storage_path` apunta a un disco distinto del actual | `php artisan storage:migrate-paths --fix-disk` |
| Lentitud o *timeouts* | Bucket en otra región | Verificar `AWS_DEFAULT_REGION` |

Los errores de S3 ya **no se silencian**: `config/filesystems.php` tiene
`'throw' => true`, de modo que cualquier fallo queda registrado en el log de
Laravel con contexto (usuario, consulta, disco, tamaño, MIME).

### 13.2 Migración falla con error 1553 en MariaDB

Ocurre al soltar un índice compuesto que respalda una clave foránea. El caso
conocido (`2026_06_23_120000_remove_consultation_stages`) ya está resuelto en
el repositorio: se crea primero el índice simple sobre `consultation_id` y
recién después se suelta el compuesto. Si aparece en una migración nueva,
aplicar el mismo patrón.

### 13.3 Los correos no llegan

1. `systemctl is-active gore-queue` — sin worker, nada sale.
2. `MAIL_MAILER` — mientras esté en `log`, los correos se escriben en
   `storage/logs/` y **no se envían**. Es el estado actual y es intencional.
3. Con `MAIL_MAILER=ses`: verificar que el dominio esté verificado en SES y
   que la cuenta tenga *production access* (fuera del sandbox).
4. Revisar la tabla `jobs` (pendientes) y `failed_jobs` (fallidos).

### 13.4 Error 500 general

1. `tail` del log de Laravel del día.
2. Verificar permisos: `storage/` y `bootstrap/cache/` deben ser
   `www-data:www-data` y `775`. Un `composer install` o un `git` corrido como
   `root` los rompe.
3. `php artisan config:clear && php artisan config:cache` — una caché de
   configuración desactualizada tras editar el `.env` es causa frecuente.

### 13.5 `git` responde "dubious ownership"

Se está ejecutando como `root` sobre un repositorio de `www-data`. Usar
siempre `sudo -u www-data git -C /var/www/gore ...`.

### 13.6 Timestamps con 4 horas de desfase

Falta `APP_TIMEZONE=America/Santiago` en el `.env` (o falta el `config:cache`
posterior). Corregirlo **no** reescribe los registros ya grabados en UTC.

---

## 14. Rollback

El despliegue es un `reset --hard` a un *commit*, así que revertir es volver
al *commit* anterior:

```bash
# Identificar el commit previo
sudo -u www-data git -C /var/www/gore log --oneline -5

sudo -u www-data git -C /var/www/gore reset --hard <commit-anterior>
cd /var/www/gore
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:clear && sudo -u www-data php artisan view:cache
systemctl restart gore-queue
```

**Las migraciones no se revierten automáticamente y algunas son
destructivas.** `2026_06_23_120000_remove_consultation_stages` elimina una
tabla y una columna; su `down()` recrea la estructura pero **no restaura los
datos**. Antes de desplegar una migración destructiva, tomar un snapshot
manual de RDS:

```bash
aws rds create-db-snapshot \
  --db-instance-identifier gore-prod-db \
  --db-snapshot-identifier gore-prod-pre-deploy-$(date +%Y%m%d) \
  --profile gore
```

---

## 15. Seguridad aplicada y pendientes de hardening

### 15.1 Medidas ya implementadas

| Medida | Implementación |
|---|---|
| Cabeceras de seguridad | Middleware `SecurityHeaders`: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS en HTTPS productivo |
| Content Security Policy | Paquete `spatie/laravel-csp`, política versionada en `config/csp.php` |
| Cifrado en tránsito | TLS con redirección 301 desde HTTP |
| Cifrado en reposo | S3 AES256; RDS con almacenamiento cifrado |
| Aislamiento de red | RDS sin acceso público; EC2 solo por SSM; SG restringidos |
| Control de acceso | Middleware `role:funcionario,super-admin` sobre `/admin`; acciones sensibles restringidas a `super-admin` |
| Trazabilidad | `spatie/laravel-activitylog` sobre consultas, documentos, observaciones y usuarios; nunca registra contraseñas |
| Inalterabilidad | Las observaciones solo auditan el evento `created`; su contenido no es editable desde ninguna interfaz |
| Descargas controladas | Streaming por controlador con verificación de sesión y rol; sin URLs públicas de S3 |
| Rate limiting | 5 envíos/min por IP en observaciones, 10/min en el redirect de ClaveÚnica, 30/min en `/healthz` |
| Validación de archivos | Tipos MIME permitidos y tope de 10 MB por adjunto ciudadano |
| Integridad | SHA-256 calculado y almacenado por cada antecedente técnico |

Marco normativo de referencia: D.S. N°7/2023 (ciberseguridad), Ley N°19.175,
Ley N°21.078, Ley N°21.180 y Decreto N°237.

### 15.2 Pendientes conocidos

Se listan explícitamente para que el GORE los priorice; no bloquean la
operación actual.

| # | Pendiente | Depende de |
|---|---|---|
| 1 | **Amazon SES**: publicar los 3 CNAME de DKIM en el DNS del dominio y esperar la aprobación de *production access*; luego cambiar `MAIL_MAILER=ses` en producción | Cliente (DNS) + AWS |
| 2 | **ClaveÚnica**: registrar el cliente OIDC ante la Unidad de Gobierno Digital y activar `CLAVEUNICA_ENABLED=true` con las credenciales | Cliente (trámite) |
| 3 | **Alta disponibilidad**: habilitar Multi-AZ en RDS | Decisión de costo |
| 4 | **Monitoreo**: alarmas CloudWatch sobre CPU, espacio en disco, health-check y cola de trabajos | GORE / AWNA |
| 5 | **WAF / CDN** frente a la instancia | Decisión de costo |
| 6 | **IAM de mínimo privilegio**: el usuario `dev-team-awna` conserva permisos amplios | AWNA |
| 7 | **Rotación de claves de acceso** del usuario `dev-team-awna` | AWNA |

Mientras el pendiente 1 no se resuelva, la publicación de una respuesta
institucional **registra** el correo en el log en lugar de enviarlo. La
funcionalidad está completa y probada; solo falta habilitar el transporte.
