# Plataforma de Procesos Participativos Reglados — GORE Valparaíso

Plataforma web institucional para la gestión y difusión de procesos de consulta pública sobre Instrumentos de Planificación y Ordenamiento Territorial (IPT, PROT, ZUBC) del Gobierno Regional de Valparaíso.

Desarrollado por **AWNA** en modalidad llave en mano para el GORE Valparaíso, con soberanía tecnológica plena sobre código fuente, base de datos e infraestructura.

---

## Tabla de contenidos

- [Resumen del producto](#resumen-del-producto)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Requisitos previos](#requisitos-previos)
- [Instalación paso a paso](#instalación-paso-a-paso)
- [Configuración (.env)](#configuración-env)
- [Inicio rápido (dev)](#inicio-rápido-dev)
- [Credenciales seedeadas](#credenciales-seedeadas)
- [Roles y permisos](#roles-y-permisos)
- [Mapa de rutas](#mapa-de-rutas)
- [Modelo de datos](#modelo-de-datos)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Comandos útiles](#comandos-útiles)
- [Despliegue a producción (Etapa 5)](#despliegue-a-producción-etapa-5)
- [Roadmap y próximos pasos](#roadmap-y-próximos-pasos)
- [Documentación complementaria](#documentación-complementaria)
- [Equipo](#equipo)

---

## Resumen del producto

La plataforma cumple cinco funciones operativas:

1. **Portal Ciudadano** — Cualquier ciudadano puede consultar los procesos vigentes, descargar antecedentes técnicos y, si se identifica, enviar observaciones formales.
2. **Autenticación dual** — Los ciudadanos pueden identificarse vía **ClaveÚnica** (OpenID Connect oficial del Estado) o mediante un **flujo manual** con validación de RUT y verificación obligatoria por correo.
3. **Backoffice administrativo** — Los funcionarios del GORE gestionan procesos, etapas configurables (1..N por consulta), antecedentes técnicos versionados, observaciones recibidas y respuestas institucionales.
4. **Trazabilidad inalterable** — Cada observación queda registrada con timestamp inmodificable, snapshot de identidad, IP, user-agent y método de autenticación utilizado.
5. **Backups automatizados** — Cada 48 horas durante procesos activos se genera un respaldo completo de observaciones a almacenamiento persistente.

Cumplimiento normativo: D.S. N°7/2023 (ciberseguridad), Ley N°19.175 (gobierno regional), Ley N°21.078 (transparencia), Ley N°21.180 (transformación digital), Decreto N°237.

---

## Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Lenguaje backend | PHP | 8.2 (dev) / 8.3 (prod AWS) |
| Framework backend | Laravel | 12.58 |
| Motor de plantillas | Blade | (incluido) |
| Base de datos | MariaDB | 10.4 (dev) / 11.x (prod RDS) |
| Frontend CSS | Bootstrap | 5.3.3 |
| Iconos | Bootstrap Icons | 1.11 |
| Tipografía | Inter (Bunny Fonts) | 400-800 |
| Preprocesador CSS | Sass | 1.79 |
| Build frontend | Vite | 7.0 |
| Auth admin | Laravel Breeze | 2.4 |
| Auth ciudadana | Custom + OpenID Connect | — |
| Export Excel/CSV | maatwebsite/excel | 3.1.69 |
| Web server (prod) | NGINX + PHP-FPM | — |
| Sistema operativo (prod) | Ubuntu Server | 22.04 LTS |
| Cache y queues (prod) | Redis vía ElastiCache | — |
| Almacenamiento (prod) | AWS S3 con versionado | — |
| Infraestructura | AWS (EC2 + RDS + S3 + CloudFront) | — |

**Extensiones PHP requeridas**: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.

---

## Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                     CIUDADANÍA (público)                        │
│  /              /consultas       /consultas/{slug}              │
│  /ingresar      /registrarme     /auth/claveunica/redirect      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE PRESENTACIÓN                        │
│  Blade + Bootstrap 5 + Vite                                     │
│  layouts/public.blade.php    layouts/guest.blade.php            │
│  layouts/app.blade.php       resources/scss/app.scss            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE APLICACIÓN                          │
│  Controllers: Public/* (ciudadanos)  Admin/* (staff)            │
│  FormRequests con validación (RUT, fechas, MIME, etc.)          │
│  Middleware: auth, role, throttle, signed (verify email)        │
│  Services: ClaveUnicaService (OIDC)                             │
│  Mailables: ObservationSubmitted (ShouldQueue)                  │
│  Console: gore:backup-observations (cron */2 días)              │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE DATOS                               │
│  MariaDB                                                        │
│  • users (extendido: national_id, role, is_active)              │
│  • consultations / consultation_stages / consultation_documents │
│  • observations (snapshot inalterable)                          │
│  • institutional_responses                                      │
│  • cache / jobs / sessions / failed_jobs                        │
│                                                                 │
│  Storage:                                                       │
│  • dev: storage/app/private (driver local)                      │
│  • prod: AWS S3 con versionado + object lock (driver s3)        │
└─────────────────────────────────────────────────────────────────┘
```

**Decisiones arquitectónicas clave**:

- **Separación de auth staff vs ciudadano**: `/admin/login` rechaza ciudadanos; `/ingresar` rechaza funcionarios. Mismas tablas pero flujos independientes.
- **UUID públicos**: consultations y observations exponen un `public_id` UUID v4 en URLs públicas — los IDs autoincrementales NO se exponen.
- **Snapshot inalterable en observaciones**: al enviar una observación, se copia el nombre/RUT/email del usuario al momento. Si el usuario edita su perfil después, la observación conserva los datos originales.
- **Versionado de documentos**: el modelo `consultation_documents` agrupa versiones por `file_group_id` (UUID compartido) + `version` incremental. Reemplazar archiva la versión vigente y conserva el archivo físico.
- **Soft deletes en lugar de hard deletes**: consultations, documents y users se desactivan (deleted_at o is_active=false). Política de expedientes inalterables.

---

## Requisitos previos

### Para desarrollo local

- **PHP 8.2+** con las extensiones listadas en [Stack tecnológico](#stack-tecnológico)
- **Composer 2.x**
- **Node 20+** y **npm 10+**
- **MariaDB 10.4+** (o MySQL 8.0+)
- **Git**

En Windows, recomendamos **XAMPP** que provee PHP, MariaDB y Apache en un solo bundle. El proyecto está validado con XAMPP en `C:\xampp`.

### Para producción

- Cuenta **AWS** con permisos para EC2, RDS, S3, CloudFront, Route 53, ACM, ElastiCache, IAM, Secrets Manager.
- Dominio o subdominio delegable a Route 53 (ej. `participa.gorevalparaiso.cl`).
- Credenciales OIDC de **ClaveÚnica** registradas con la Unidad de Gobierno Digital.

---

## Instalación paso a paso

### 1. Clonar el repositorio

```bash
git clone <repo-url> gore-valparaiso
cd gore-valparaiso
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Habilitar extensiones PHP requeridas (Windows + XAMPP)

Editar `C:\xampp\php\php.ini` y descomentar (quitar `;` al inicio):

```ini
extension=zip
extension=gd
```

### 4. Instalar dependencias JS y construir assets

```bash
npm install
npm run build
```

### 5. Configurar entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores de la sección [Configuración](#configuración-env).

### 6. Crear base de datos

Conectarse a MariaDB y crear la base de datos:

```sql
CREATE DATABASE gore_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Correr migraciones y semilla inicial

```bash
php artisan migrate --seed
```

Esto crea las 14 tablas del sistema y semilla:
- 1 super-admin (`kevin@awna.cl`)
- 2 funcionarios (`claudio@gorevalparaiso.cl`, `gabriel@gorevalparaiso.cl`)
- 5 ciudadanos de prueba
- 1 consulta PROT activa con 2 etapas
- 3 documentos de muestra
- 5 observaciones de muestra

### 8. Crear symlink de storage

```bash
php artisan storage:link
```

### 9. Verificar la instalación

```bash
php artisan serve
```

Visitar [http://localhost:8000](http://localhost:8000) — debe cargar la landing del Portal Ciudadano.

---

## Configuración (.env)

Variables relevantes del `.env`:

```env
APP_NAME="GORE Valparaiso Participacion"
APP_ENV=local                # local | staging | production
APP_KEY=base64:...           # generado por artisan key:generate
APP_DEBUG=true               # false en prod
APP_URL=http://localhost:8000
APP_TIMEZONE=America/Santiago
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CL

# Base de datos
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gore_dev
DB_USERNAME=root
DB_PASSWORD=

# Sesión, cache, queues (dev: database; prod: redis)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Mail (dev: log; prod: AWS SES o similar)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="participa@gorevalparaiso.cl"
MAIL_FROM_NAME="${APP_NAME}"

# ClaveÚnica OIDC
CLAVEUNICA_MODE=mock         # mock (dev) | live (prod)
CLAVEUNICA_CLIENT_ID=        # entregado por digital.gob.cl
CLAVEUNICA_CLIENT_SECRET=    # ídem

# Filesystem
FILESYSTEM_DISK=local        # local (dev) | s3 (prod)

# AWS (solo prod)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

---

## Inicio rápido (dev)

Tres procesos corren en paralelo durante desarrollo:

### Terminal 1: MariaDB (XAMPP)

```powershell
# Windows
Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini" `
  -WindowStyle Hidden
```

O abrir XAMPP Control Panel y arrancar MySQL/MariaDB.

### Terminal 2: Servidor Laravel

```bash
php artisan serve
```

Disponible en [http://localhost:8000](http://localhost:8000).

### Terminal 3: Vite (assets en hot reload)

```bash
npm run dev
```

### Terminal 4 (opcional): Worker de la queue para procesar mails

```bash
php artisan queue:work
```

Sin esto, los mails de confirmación de observaciones quedan encolados en la tabla `jobs` pero no se procesan.

---

## Credenciales seedeadas

Todas las contraseñas son `password`.

| Rol | Email | RUT | Uso |
|---|---|---|---|
| Super-admin | `kevin@awna.cl` | `15.123.456-7` | Gestión de funcionarios + todo lo de funcionario |
| Funcionario | `claudio@gorevalparaiso.cl` | `12.345.678-9` | Gestión de consultas, etapas, documentos, observaciones |
| Funcionario | `gabriel@gorevalparaiso.cl` | `13.456.789-0` | Idem Claudio |
| Ciudadanos (5) | varios `@example.*` | RUTs aleatorios válidos | Para probar el flujo ciudadano |

**URL de login**:
- **Staff**: [http://localhost:8000/admin/login](http://localhost:8000/admin/login)
- **Ciudadanos**: [http://localhost:8000/ingresar](http://localhost:8000/ingresar)

---

## Roles y permisos

### Ciudadano (`ciudadano`)

- Ver listado público de consultas
- Ver ficha de cada consulta con descripción, etapas y antecedentes
- Descargar antecedentes técnicos
- Registrarse manualmente o autenticarse vía ClaveÚnica
- Enviar observaciones (requiere correo verificado)
- Recibir confirmación de observación por correo

### Funcionario (`funcionario`)

Todo lo del ciudadano más:

- Acceso al backoffice (`/admin/*`)
- CRUD de consultas (crear, editar, archivar)
- Gestionar etapas configurables (crear, reordenar, eliminar)
- Subir antecedentes técnicos con versionado
- Reemplazar versiones de documentos (archiva la anterior)
- Ver listado de observaciones con filtros (proceso, etapa, fecha, método auth, búsqueda full-text)
- Exportar observaciones a Excel o CSV (con los filtros aplicados)
- Ver detalle de cada observación con trazabilidad (IP, user agent, snapshot)
- Editar su propio perfil

### Super-admin (`super-admin`)

Todo lo del funcionario más:

- Gestión de usuarios staff (`/admin/users`)
- Crear funcionarios y otros super-admin
- Desactivar/reactivar cuentas (sin eliminar — auditoría)
- Editar datos de cualquier funcionario
- Filtrar usuarios por rol, estado, búsqueda

**Salvaguardas**:
- Ningún super-admin puede auto-desactivarse
- Ningún super-admin puede degradarse a sí mismo a funcionario
- Los ciudadanos se autogestionan; NO aparecen en el listado del backoffice
- Los usuarios nunca se eliminan físicamente — solo se desactivan (`is_active=false`)

---

## Mapa de rutas

### Públicas (sin autenticación)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Landing con consultas vigentes |
| GET | `/consultas` | Listado público con filtros |
| GET | `/consultas/{slug}` | Ficha de una consulta |
| GET | `/consultas/{slug}/antecedentes/{fileGroupId}/descargar` | Descarga anónima de antecedente |

### Auth ciudadana

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/ingresar` | Login ciudadano |
| GET/POST | `/registrarme` | Registro manual |
| GET | `/auth/claveunica/redirect` | Inicia flujo OIDC con ClaveÚnica |
| GET | `/auth/claveunica/callback` | Callback OIDC |
| POST | `/cerrar-sesion` | Logout |
| GET | `/email/verificar` | Pantalla "revisa tu correo" |
| GET | `/email/verificar/{id}/{hash}` | Link de verificación firmado |
| POST | `/email/reenviar-verificacion` | Reenviar mail (throttle 6/min) |

### Observaciones (auth requerida)

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/consultas/{slug}/observaciones` | Envía observación (throttle 5/min) |
| GET | `/consultas/{slug}/observaciones/{publicId}/exito` | Pantalla de confirmación |

### Auth staff

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/admin/login` | Login funcionario/super-admin |
| GET/POST | `/admin/forgot-password` | Reset de contraseña |
| GET/POST | `/admin/reset-password/{token}` | Definir nueva contraseña |
| POST | `/admin/logout` | Logout |

### Backoffice (auth + role:funcionario,super-admin)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/dashboard` | Dashboard inicial |
| GET | `/admin/profile` | Editar perfil propio |
| GET | `/admin/consultations` | Listado de consultas |
| GET | `/admin/consultations/create` | Crear consulta |
| GET | `/admin/consultations/{id}` | Detalle con etapas + documentos |
| GET | `/admin/consultations/{id}/edit` | Editar consulta |
| DELETE | `/admin/consultations/{id}` | Archivar (soft delete) |
| GET/POST/PATCH/DELETE | `/admin/consultations/{id}/stages/*` | CRUD de etapas |
| POST | `/admin/consultations/{id}/stages/{s}/move/{dir}` | Reordenar etapas |
| POST | `/admin/consultations/{id}/documents` | Subir antecedente |
| GET | `/admin/consultations/{id}/documents/{d}/download` | Descargar (staff) |
| POST | `/admin/consultations/{id}/documents/{d}/replace` | Nueva versión |
| DELETE | `/admin/consultations/{id}/documents/{d}` | Archivar documento |
| GET | `/admin/observations` | Listado con filtros |
| GET | `/admin/observations/{id}` | Detalle |
| GET | `/admin/observations/export/{xlsx\|csv}` | Exportar |

### Solo super-admin

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/users` | Listado de funcionarios |
| GET/POST | `/admin/users/create` | Crear funcionario |
| GET/PATCH | `/admin/users/{id}/edit` | Editar funcionario |
| POST | `/admin/users/{id}/toggle-active` | Activar/desactivar |

### Desarrollo (solo si `CLAVEUNICA_MODE=mock`)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/dev/claveunica/simulate` | Simulador local de ClaveÚnica |
| POST | `/dev/claveunica/complete` | Completa flujo mock |

---

## Modelo de datos

### Tablas del dominio

| Tabla | Propósito | Notas |
|---|---|---|
| `users` | Ciudadanos y staff | Campos extendidos: `national_id`, `last_name`, `phone`, `role`, `is_active`, `last_login_at`, `last_login_ip` |
| `consultations` | Procesos de consulta | UUID público, slug, tipo (IPT/PROT/ZUBC/OTRO), status (draft → published → active → closed → archived), `auth_methods` JSON, soft deletes |
| `consultation_stages` | Etapas configurables 1..N por consulta | `position`, `accepts_observations`, `status` (pending/active/closed) |
| `consultation_documents` | Antecedentes técnicos | Versionado por `file_group_id` (UUID) + `version` + SHA-256, soft deletes |
| `observations` | Observaciones ciudadanas | UUID público, **snapshot inalterable** (`snapshot_national_id`, `snapshot_full_name`, `snapshot_email`), `auth_method_used`, `submitted_at`, `ip_address`, `user_agent` |
| `institutional_responses` | Respuestas del GORE a observaciones | Unique por `observation_id`, `batch_id` UUID para respuestas en lote (pendiente UI) |

### Tablas de soporte (Laravel)

`cache`, `cache_locks`, `failed_jobs`, `job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `sessions`.

### Relaciones clave

```
users ───< consultations.created_by (creator)
users ───< observations.user_id
users ───< consultation_documents.uploaded_by
users ───< institutional_responses.responded_by

consultations ───< consultation_stages (cascade)
consultations ───< consultation_documents (cascade)
consultations ───< observations (restrict)

consultation_stages ───< consultation_documents (nullable)
consultation_stages ───< observations (restrict)

observations ───< institutional_responses (1:1, restrict)
```

---

## Estructura del proyecto

```
gore-valparaiso/
├── app/
│   ├── Console/Commands/
│   │   └── BackupObservations.php       Comando gore:backup-observations
│   ├── Exports/
│   │   └── ObservationsExport.php       Definición Excel/CSV
│   ├── Http/Controllers/
│   │   ├── Admin/                       Backoffice (funcionario/super-admin)
│   │   ├── Auth/                        Breeze (staff)
│   │   ├── Public/                      Portal Ciudadano
│   │   │   └── Auth/                    Login/Register/ClaveUnica ciudadano
│   │   └── Dev/                         MockClaveUnicaController
│   ├── Http/Middleware/
│   │   └── EnsureUserHasRole.php        role:funcionario,super-admin
│   ├── Http/Requests/
│   │   ├── Admin/                       FormRequests del backoffice
│   │   └── Public/                      FormRequests públicos
│   ├── Mail/
│   │   └── ObservationSubmitted.php     Mailable (ShouldQueue)
│   ├── Models/                          6 modelos del dominio
│   ├── Rules/
│   │   └── Rut.php                      Validador RUT chileno (módulo 11)
│   └── View/Components/                 PublicLayout, GuestLayout, AppLayout
├── config/
│   └── claveunica.php                   Config OIDC
├── database/
│   ├── factories/                       5 factories
│   ├── migrations/                      9 migraciones
│   └── seeders/                         DatabaseSeeder
├── docs/
│   ├── brief/                           Brief técnico original
│   └── etapa-1/                         Entregables formales Etapa 1
│       ├── 01-plan-de-trabajo-detallado.docx
│       ├── 02-documento-arquitectura-aws.docx
│       └── 03-carta-gantt-definitiva.xlsx
├── public/
│   ├── build/                           Assets compilados por Vite (gitignored)
│   └── img/brand/                       Logo institucional
├── resources/
│   ├── js/
│   ├── scss/
│   │   └── app.scss                     Design system completo
│   └── views/
│       ├── admin/                       Vistas backoffice
│       ├── auth/                        Vistas staff auth (Breeze + Bootstrap)
│       ├── components/                  Blade components reusables
│       ├── dev/                         MockClaveUnica simulator
│       ├── emails/                      Plantillas markdown
│       ├── layouts/
│       ├── profile/                     Profile edit (staff)
│       └── public/                      Vistas Portal Ciudadano
├── routes/
│   ├── auth.php                         Rutas Breeze (staff)
│   ├── console.php                      Schedule (backup 48h)
│   └── web.php                          Todo lo demás
├── storage/app/private/
│   ├── backups/observations/            Salida del comando backup
│   └── consultations/{id}/{uuid}/v{n}/  Antecedentes técnicos
└── .env.example
```

---

## Comandos útiles

### Desarrollo

```bash
# Reiniciar BD desde cero con datos de prueba
php artisan migrate:fresh --seed

# Limpiar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Listar todas las rutas
php artisan route:list

# Inspeccionar un modelo (relaciones, atributos, casts)
php artisan model:show Consultation

# Procesar la queue (mails de confirmación)
php artisan queue:work

# Tarea programada manual
php artisan schedule:work       # corre el scheduler continuo
php artisan gore:backup-observations          # backup ad-hoc
php artisan gore:backup-observations --force  # ignora "no hay activas"

# Ver schedule
php artisan schedule:list

# Crear nuevos recursos
php artisan make:controller Admin/Foo --resource --model=Foo
php artisan make:request Admin/StoreFooRequest
php artisan make:migration create_foo_table
```

### Frontend

```bash
npm run dev      # Vite con HMR
npm run build    # Build de producción
```

### Testing y QA

```bash
# Correr toda la suite Pest (~8 segundos, BD en memoria)
composer test
# o equivalente
vendor/bin/pest

# Filtrar por archivo o nombre
vendor/bin/pest tests/Feature/Admin/InstitutionalResponseTest.php
vendor/bin/pest --filter='publica un borrador'

# Load test contra el servidor local (requiere k6 instalado)
k6 run -e BASE_URL=http://localhost:8000 tests/k6/observation-submission.js
k6 run -e BASE_URL=http://localhost:8000 \
       -e EMAIL=claudio@gorevalparaiso.cl \
       -e PASSWORD=password \
       tests/k6/admin-listing.js

# OWASP ZAP baseline scan (requiere Docker)
BASE_URL=http://host.docker.internal:8000 ./scripts/owasp-zap-smoke.sh
```

### Auditoría (D20)

```bash
# Consultar últimas entradas de la bitácora
php artisan tinker
> Spatie\Activitylog\Models\Activity::with('causer')->latest()->take(10)->get();

# O navegar como super-admin a /admin/activity-log
```

### Git

```bash
# Convención: mensajes en español, commits descriptivos
git log --oneline
```

---

## Despliegue a producción (Etapa 5)

El proyecto está diseñado para correr en AWS. El despliegue completo se documenta en [docs/etapa-1/02-documento-arquitectura-aws.docx](docs/etapa-1/02-documento-arquitectura-aws.docx).

### Resumen de la arquitectura objetivo

```
Internet
    │
    ▼
Route 53 (DNS) ──► ACM (SSL) ──► CloudFront (CDN, antecedentes S3)
    │
    ▼
AWS WAF + Shield (rate limit, OWASP)
    │
    ▼
Application Load Balancer
    │
    ▼ (sticky sessions)
Auto Scaling Group de EC2 t3.medium
    │ NGINX + PHP-FPM 8.3 + Laravel
    │
    ├──► RDS MariaDB 11.x Multi-AZ
    ├──► ElastiCache Redis (queue + cache + session)
    ├──► S3 (antecedentes con versionado, backups, exportaciones)
    ├──► SES (envío de correos transaccionales)
    ├──► Secrets Manager (credenciales ClaveÚnica, BD)
    └──► CloudWatch (logs, métricas, alarmas)
```

### Variables `.env` que cambian en producción

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://participa.gorevalparaiso.cl

DB_HOST=<endpoint-rds>
DB_PASSWORD=<from-secrets-manager>

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=<endpoint-elasticache>

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<from-iam>
AWS_SECRET_ACCESS_KEY=<from-iam>
AWS_BUCKET=gore-docs-prod

MAIL_MAILER=ses

CLAVEUNICA_MODE=live
CLAVEUNICA_CLIENT_ID=<registrado-en-claveunica>
CLAVEUNICA_CLIENT_SECRET=<from-secrets-manager>
```

### Pasos de despliegue

Las cuatro fases del despliegue inicial están descritas en el [Documento de Arquitectura](docs/etapa-1/02-documento-arquitectura-aws.docx):

1. **Provisioning**: VPC, subnets, security groups, IAM roles, Secrets Manager (Infrastructure-as-Code recomendado: Terraform o CloudFormation).
2. **Capa de datos**: RDS MariaDB Multi-AZ, ElastiCache Redis, S3 buckets con KMS.
3. **Capa de aplicación**: Launch Template, Auto Scaling Group, ALB. Deploy de Laravel.
4. **Capa de borde**: CloudFront, Route 53, ACM, WAF.

---

## Roadmap y próximos pasos

### Implementado (Etapas 1-5a)

- ✓ **Etapa 1** — Planificación + 3 entregables formales en `docs/etapa-1/`
- ✓ **Etapa 2** — Sistema de diseño completo aplicado (Bootstrap 5 + variables institucionales)
- ✓ **Etapa 3** — Backoffice operativo: CRUD consultas, etapas, antecedentes, usuarios, observaciones, export
- ✓ **Etapa 4** — Portal público + auth dual (manual + ClaveÚnica mock) + envío de observaciones + backup 48h
- ✓ **D14** — Respuestas institucionales (individual + lote con `batch_id` compartido) + portal público + mail al ciudadano
- ✓ **D20** — Audit log inmutable con `spatie/laravel-activitylog` en 6 modelos + backoffice `/admin/activity-log`
- ✓ **T4.6** — Suite Pest del dominio: **46 tests verdes, 145 aserciones**
- ✓ **D21** — Hardening: `SecurityHeaders` middleware + CSP estricta (`spatie/laravel-csp`) + rate limits anti-flood + HTTPS forzado en producción
- ✓ Reporte QA completo en [docs/etapa-5/qa-report.md](docs/etapa-5/qa-report.md)

### Pendiente — Etapa 5b y 5c (Despliegue + Transferencia)

**Bloqueantes del cliente (gestiones con Lukas)**:

- [ ] Recibir credenciales OIDC reales de ClaveÚnica (`CLAVEUNICA_CLIENT_ID`/`CLIENT_SECRET`)
- [ ] Confirmar subdominio definitivo (ej. `participa.gorevalparaiso.cl`) y delegación DNS a Route 53
- [ ] Cuenta AWS habilitada con presupuesto definido
- [ ] Incorporación formal de Miguel (Unidad de Informática del GORE)
- [ ] Recibir logo institucional sobre fondo BLANCO (`public/img/brand/escudo_gore_w.png`) — actualmente solo tenemos la versión sobre fondo negro

**Tareas técnicas pendientes (estimación interna)**:

- [ ] Terraform/CloudFormation para provisioning reproducible
- [ ] Pipeline CI/CD en GitHub Actions con `deploy-prod.yml`
- [ ] Migrar comando `gore:backup-observations` a EventBridge → SSM
- [ ] Configurar SES con dominio verificado
- [ ] Smoke test post-deploy + k6 contra producción staging (scripts ya disponibles en `tests/k6/`)
- [ ] OWASP ZAP baseline contra staging (script en `scripts/owasp-zap-smoke.sh`)
- [ ] Configurar AWS WAF para rate limiting global complementario

#### Etapa 5c — Transferencia (1-2 días)
- [ ] Manual de Instalación AWS (paso a paso reproducible)
- [ ] Manual de Programación / Mantenimiento / Evolución
- [ ] Manual de Usuario del Portal Público (PDF)
- [ ] Manual de Usuario del Backoffice (PDF)
- [ ] Capacitación grabada a DIPLAD + Unidad de Informática (~2h)
- [ ] Handover de credenciales super-admin + accesos AWS + repo Git
- [ ] Acta de cierre firmada por contraparte técnica del GORE

### Mejoras post-go-live (garantía 180 días o iteraciones futuras)

- Vista "Mis observaciones" para ciudadanos
- Filtros más sofisticados en `/admin/observations` (por categoría, longitud)
- Dashboard del backoffice con gráficos reales (Chart.js sobre métricas en tiempo real)
- Notificaciones in-app al funcionario cuando llega nueva observación
- Búsqueda full-text con MySQL FULLTEXT o Meilisearch
- API REST pública para terceros (consultar observaciones agregadas con anonimato)
- Soporte para mapas interactivos (Leaflet o Mapbox) sobre la ficha de la consulta
- Importación masiva de procesos desde Excel para el lanzamiento

---

## Documentación complementaria

| Documento | Ubicación |
|---|---|
| Brief técnico original (DOCX extraído) | [docs/brief/extracted.txt](docs/brief/extracted.txt) |
| Plan de Trabajo Detallado — Etapa 1 | [docs/etapa-1/01-plan-de-trabajo-detallado.docx](docs/etapa-1/01-plan-de-trabajo-detallado.docx) |
| Documento de Arquitectura AWS — Etapa 1 | [docs/etapa-1/02-documento-arquitectura-aws.docx](docs/etapa-1/02-documento-arquitectura-aws.docx) |
| Carta Gantt Definitiva — Etapa 1 | [docs/etapa-1/03-carta-gantt-definitiva.xlsx](docs/etapa-1/03-carta-gantt-definitiva.xlsx) |
| Referencia normativa | Leyes 19.175, 21.078, 21.180, Decreto 237, D.S. N°7/2023 |
| Guía oficial ClaveÚnica | [digital.gob.cl/biblioteca/guias/claveunica](https://digital.gob.cl/biblioteca/guias/claveunica) |
| Plataforma de referencia funcional | [consultasciudadanas.mma.gob.cl](https://consultasciudadanas.mma.gob.cl) |

---

## Equipo

### AWNA — Proveedor

- **Lukas Escobar** — Director de Marketing Digital y Tecnología. Dirección de proyecto, comunicación con cliente.
- **Equipo de Desarrollo AWNA** — Ejecución técnica integral (arquitectura, desarrollo, despliegue, soporte).

### Gobierno Regional de Valparaíso — Mandante

- **Gonzalo Gómez Ángel** — Jefe Departamento de Planificación. Autorización de pagos.
- **Claudio** — Coordinador, Unidad de Ordenamiento Territorial. Contraparte técnica principal.
- **Gabriel San Martín** — Profesional UOT. Validación funcional.
- **Vicente Gajardo** — Profesional UOT. Validación funcional.
- **Miguel** — Unidad de Informática (por confirmar). Validación técnica y QA.

---

## Licencia

Software desarrollado en modalidad **llave en mano** para el Gobierno Regional de Valparaíso. Tras la recepción conforme, el código fuente, base de datos y configuraciones cloud son propiedad del GORE Valparaíso.

Durante el periodo de **garantía técnica de 180 días corridos** posteriores al go-live, AWNA cubre correcciones de bugs y soporte vía correo en horario 8x5 (lun-vie, 9-18 CLT) con SLA:
- **Críticos** (plataforma caída, brecha de seguridad): respuesta 24h hábiles, fix en 48h
- **No críticos**: respuesta 48h hábiles, fix en 5 días hábiles

---

*Última actualización: 2026-05-13 — Etapa 4 cerrada (T4.2 ClaveÚnica integrado). 15 commits en repositorio.*
