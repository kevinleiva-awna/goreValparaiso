# Diccionario de Datos y Mapa de Rutas

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**

| | |
|---|---|
| Versión | 1.0 |
| Fecha | 5 de agosto de 2026 |
| Autor | AWNA |
| Destinatario | Equipo técnico / Unidad de Informática, Gobierno Regional de Valparaíso |
| Motor de base de datos | MariaDB 10.11 (producción) / 10.4 (desarrollo) |
| Documentos relacionados | `01-manual-despliegue-operacion.md`, `02-manual-administrador.md` |

---

## Tabla de contenidos

1. [Modelo de entidades](#1-modelo-de-entidades)
2. [Convenciones](#2-convenciones)
3. [Tabla `users`](#3-tabla-users)
4. [Tabla `consultations`](#4-tabla-consultations)
5. [Tabla `consultation_documents`](#5-tabla-consultation_documents)
6. [Tabla `observations`](#6-tabla-observations)
7. [Tabla `institutional_responses`](#7-tabla-institutional_responses)
8. [Tabla `activity_log`](#8-tabla-activity_log)
9. [Tablas de infraestructura](#9-tablas-de-infraestructura)
10. [Reglas de integridad a nivel de aplicación](#10-reglas-de-integridad-a-nivel-de-aplicación)
11. [Historial de migraciones](#11-historial-de-migraciones)
12. [Mapa de rutas](#12-mapa-de-rutas)
13. [Middleware y límites de tasa](#13-middleware-y-límites-de-tasa)
14. [Política de seguridad de contenidos (CSP)](#14-política-de-seguridad-de-contenidos-csp)
15. [Comandos de consola](#15-comandos-de-consola)

---

## 1. Modelo de entidades

```
      ┌──────────────────┐
      │      users       │
      │ ciudadano /      │
      │ funcionario /    │
      │ super-admin      │
      └───────┬──────────┘
              │ created_by / updated_by / uploaded_by / responded_by
              │ user_id (autor ciudadano, opcional)
              ▼
   ┌────────────────────────┐        1     N   ┌──────────────────────────┐
   │     consultations      │─────────────────►│ consultation_documents   │
   │  proceso participativo │                  │  antecedentes versionados│
   └───────────┬────────────┘                  └──────────────────────────┘
               │ 1
               │
               │ N
   ┌───────────▼────────────┐        1     0..1 ┌──────────────────────────┐
   │      observations      │──────────────────►│ institutional_responses  │
   │  inalterables + snapshot                   │  borrador / publicada    │
   └────────────────────────┘                   └──────────────────────────┘

   ┌────────────────────────┐
   │      activity_log      │  polimórfica: audita las cuatro entidades
   └────────────────────────┘
```

Relaciones y su comportamiento ante borrado:

| Relación | Cardinalidad | Al borrar el padre |
|---|---|---|
| `consultations` → `consultation_documents` | 1 : N | `CASCADE` |
| `consultations` → `observations` | 1 : N | `RESTRICT` |
| `observations` → `institutional_responses` | 1 : 0..1 | `RESTRICT` |
| `users` → `observations` (autor) | 1 : N | `RESTRICT`, `user_id` nulo para participación sin registro |
| `users` → `consultations` (`created_by`, `updated_by`) | 1 : N | `SET NULL` |
| `users` → `consultation_documents` (`uploaded_by`) | 1 : N | `SET NULL` |
| `users` → `institutional_responses` (`responded_by`) | 1 : N | `RESTRICT` |

`RESTRICT` sobre las observaciones es intencional: impide destruir un
expediente por un borrado en cascada accidental.

---

## 2. Convenciones

- **Motor y codificación:** InnoDB, `utf8mb4`.
- **Claves primarias:** `id`, `BIGINT UNSIGNED AUTO_INCREMENT`.
- **Identificadores públicos:** las entidades expuestas al ciudadano llevan
  además un `public_id` de tipo UUID. Nunca se expone el `id` interno en una
  URL pública.
- **Marcas de tiempo:** `created_at` y `updated_at` en todas las tablas
  (`TIMESTAMP NULL`). La zona horaria de la aplicación es `America/Santiago`.
- **Borrado lógico:** las tablas con `deleted_at` (`TIMESTAMP NULL`) usan
  borrado lógico. Una fila con `deleted_at` poblado está *archivada*: no
  aparece en consultas normales, pero el registro y sus archivos se conservan.
- **Nomenclatura `snapshot_*`:** columnas que congelan un valor al momento del
  hecho. No se actualizan aunque cambie el dato de origen. Son la base de la
  trazabilidad inalterable.

---

## 3. Tabla `users`

Personas del sistema: ciudadanos identificados por ClaveÚnica, funcionarios y
super-administradores.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `national_id` | VARCHAR(12) | Sí | RUT/RUN normalizado. Único. Validado con dígito verificador |
| `name` | VARCHAR(255) | No | Nombre |
| `last_name` | VARCHAR(100) | Sí | Apellido |
| `email` | VARCHAR(255) | No | Correo. Único |
| `phone` | VARCHAR(20) | Sí | Teléfono de contacto |
| `email_verified_at` | TIMESTAMP | Sí | Fecha de verificación del correo |
| `password` | VARCHAR(255) | No | Hash bcrypt. Nunca se registra en la bitácora |
| `role` | ENUM | No | `ciudadano` \| `funcionario` \| `super-admin`. Por defecto `ciudadano` |
| `is_active` | BOOLEAN | No | `true` por defecto. En `false` el ingreso queda bloqueado |
| `remember_token` | VARCHAR(100) | Sí | Token de "recordarme" |
| `last_login_at` | TIMESTAMP | Sí | Último ingreso exitoso |
| `last_login_ip` | VARCHAR(45) | Sí | IP del último ingreso (soporta IPv6) |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |

**Índices:** `PRIMARY(id)`, `UNIQUE(national_id)`, `UNIQUE(email)`,
`INDEX(role)`, `INDEX(is_active)`.

**Notas.** El RUN entregado por ClaveÚnica y el RUT declarado son el mismo
número para personas naturales chilenas, por lo que se normalizan en una sola
columna; el método concreto de identificación se registra por observación, no
por usuario. Un usuario puede no tener `national_id` en escenarios donde la
identificación provenga de otra vía.

---

## 4. Tabla `consultations`

Proceso participativo sobre un instrumento de ordenamiento territorial.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `public_id` | CHAR(36) UUID | No | Identificador público. Único. Se genera automáticamente |
| `slug` | VARCHAR(191) | No | Identificador legible en la URL pública. Único |
| `title` | VARCHAR(255) | No | Título del proceso |
| `summary` | TEXT | Sí | Bajada breve (máx. 1.000 caracteres por validación) |
| `description` | LONGTEXT | Sí | Descripción completa de la ficha |
| `instrument_type` | ENUM | No | `IPT` \| `PROT` \| `ZUBC` \| `OTRO`. Por defecto `IPT` |
| `status` | ENUM | No | `draft` \| `published` \| `active` \| `closed` \| `archived`. Por defecto `draft` |
| `starts_at` | TIMESTAMP | Sí | Inicio de la ventana de participación. Nulo = sin límite inferior |
| `ends_at` | TIMESTAMP | Sí | Término de la ventana. Nulo = sin límite superior |
| `auth_methods` | JSON | Sí | Métodos habilitados: `["claveunica","guest"]` |
| `map_image_url` | VARCHAR(255) | Sí | Imagen cartográfica de apoyo |
| `map_geojson` | JSON | Sí | Geometría del área del instrumento |
| `created_by` | BIGINT UNSIGNED | Sí | FK a `users`. `SET NULL` |
| `updated_by` | BIGINT UNSIGNED | Sí | FK a `users`. `SET NULL` |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |
| `deleted_at` | TIMESTAMP | Sí | Archivado lógico |

**Índices:** `PRIMARY(id)`, `UNIQUE(public_id)`, `UNIQUE(slug)`,
`INDEX(status, instrument_type)`, `INDEX(starts_at)`, `INDEX(ends_at)`.

### Estados

| Valor | Significado | Público | Recibe observaciones |
|---|---|:---:|:---:|
| `draft` | En preparación | No | No |
| `published` | Anunciado, aún no abierto | Sí | No |
| `active` | Abierto a participación | Sí | Sí, dentro de la ventana |
| `closed` | Concluido | Sí | No |
| `archived` | Retirado | No | No |

### Estado efectivo

La vista pública **no** usa el valor almacenado sin más: lo corrige con la
fecha real. Si el estado es `active` pero `ends_at` ya pasó, se presenta como
`closed`; si `starts_at` aún no llega, se presenta como `published`. Un
proceso solo acepta observaciones cuando su estado es `active` **y** el
instante actual cae dentro de `[starts_at, ends_at]`.

### Métodos de participación

| Valor en `auth_methods` | Significado |
|---|---|
| `claveunica` | Identificación por ClaveÚnica (solo personas naturales) |
| `guest` | Participación sin registro, con datos autodeclarados |

El valor histórico `manual` (registro con correo y contraseña) fue eliminado
del sistema en junio de 2026 y depurado del dato existente por la migración
`2026_06_02_130000`.

---

## 5. Tabla `consultation_documents`

Antecedentes técnicos de cada proceso, con versionado e integridad
verificable.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `consultation_id` | BIGINT UNSIGNED | No | FK a `consultations`. `CASCADE` |
| `title` | VARCHAR(255) | No | Título visible del documento |
| `description` | VARCHAR(255) | Sí | Descripción breve |
| `original_filename` | VARCHAR(255) | No | Nombre del archivo tal como se subió |
| `mime_type` | VARCHAR(191) | No | Tipo MIME informado por el cliente |
| `size_bytes` | BIGINT UNSIGNED | No | Tamaño en bytes |
| `storage_path` | VARCHAR(255) | No | Ruta dentro del disco de almacenamiento |
| `storage_disk` | VARCHAR(20) | No | Disco donde reside: `local` o `s3` |
| `file_group_id` | CHAR(36) UUID | No | Agrupa todas las versiones de un mismo documento lógico |
| `version` | INT UNSIGNED | No | Número de versión, desde 1 |
| `sha256` | CHAR(64) | Sí | Hash del contenido, calculado en la carga |
| `uploaded_by` | BIGINT UNSIGNED | Sí | FK a `users`. `SET NULL` |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |
| `deleted_at` | TIMESTAMP | Sí | Archivado lógico (el archivo se conserva) |

**Índices:** `PRIMARY(id)`, `INDEX(consultation_id)`, `INDEX(file_group_id)`.

**Versionado.** Reemplazar un documento no sobrescribe: se archiva
lógicamente la fila vigente y se inserta una nueva con el mismo
`file_group_id` y `version + 1`. La versión vigente de un grupo es la fila no
archivada con el mayor `version`. La descarga pública se resuelve por
`file_group_id`, no por `id`, de modo que un enlace publicado sigue sirviendo
la versión actual.

**`storage_disk` por fila** permite migrar el almacenamiento global de `local`
a `s3` sin perder los archivos previos: cada descarga usa el disco con el que
esa fila fue creada.

---

## 6. Tabla `observations`

Observación ciudadana. Es el registro central del expediente y su contenido es
inalterable.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `public_id` | CHAR(36) UUID | No | Código público, comprobante del ciudadano. Único |
| `consultation_id` | BIGINT UNSIGNED | No | FK a `consultations`. `RESTRICT` |
| `submission_group_id` | CHAR(36) UUID | Sí | Agrupa las observaciones enviadas en una misma participación |
| `user_id` | BIGINT UNSIGNED | Sí | FK a `users`. `RESTRICT`. Nulo en participación sin registro |
| `snapshot_actor_type` | VARCHAR(10) | Sí | `natural` \| `pj` \| `org` (restringido por CHECK) |
| `snapshot_id_type` | VARCHAR(10) | Sí | `rut` \| `pasaporte` (restringido por CHECK). Solo aplica a persona natural |
| `subject` | VARCHAR(255) | Sí | Asunto declarado |
| `body` | LONGTEXT | No | Texto de la observación (10 a 10.000 caracteres por validación) |
| `category` | VARCHAR(100) | Sí | Tema. Ver lista más abajo |
| `attachment_path` | VARCHAR(255) | Sí | Ruta del adjunto en el disco |
| `attachment_disk` | VARCHAR(20) | Sí | Disco del adjunto: `local` o `s3` |
| `attachment_original_name` | VARCHAR(255) | Sí | Nombre original del adjunto |
| `attachment_mime_type` | VARCHAR(100) | Sí | Tipo MIME del adjunto |
| `attachment_size_bytes` | INT UNSIGNED | Sí | Tamaño del adjunto en bytes |
| `auth_method_used` | ENUM | No | `claveunica` \| `guest` (`manual` histórico, ya depurado) |
| `snapshot_national_id` | VARCHAR(12) | Sí | RUT o pasaporte de la persona natural |
| `snapshot_business_id` | VARCHAR(12) | Sí | RUT de la persona jurídica u organización |
| `snapshot_phone` | VARCHAR(20) | Sí | Teléfono declarado |
| `snapshot_address` | VARCHAR(255) | Sí | Dirección declarada (PJ / organización) |
| `snapshot_comuna` | VARCHAR(100) | Sí | Comuna declarada (persona natural) |
| `snapshot_age` | TINYINT UNSIGNED | Sí | Edad declarada (persona natural, 14 a 120) |
| `snapshot_full_name` | VARCHAR(255) | Sí | Nombre de la persona natural |
| `snapshot_legal_name` | VARCHAR(200) | Sí | Razón social de la PJ u organización |
| `snapshot_trade_name` | VARCHAR(200) | Sí | Nombre de fantasía |
| `snapshot_email` | VARCHAR(255) | No | Correo declarado. Destino de la respuesta institucional |
| `submitted_at` | TIMESTAMP | No | Momento del envío. Por defecto el instante actual |
| `ip_address` | VARCHAR(45) | Sí | IP de origen (soporta IPv6) |
| `user_agent` | VARCHAR(500) | Sí | Navegador de origen |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |
| `deleted_at` | TIMESTAMP | Sí | Archivado lógico (papelera, solo super-admin) |

**Índices:** `PRIMARY(id)`, `UNIQUE(public_id)`, `INDEX(consultation_id)`,
`INDEX(submission_group_id)`, `INDEX(user_id)`, `INDEX(submitted_at)`,
`INDEX(snapshot_national_id)`, `INDEX(snapshot_business_id)`,
`INDEX(snapshot_actor_type, consultation_id)` (`idx_obs_actor_consult`).

**Restricciones CHECK:**

| Nombre | Regla |
|---|---|
| `chk_obs_actor_type` | `snapshot_actor_type IS NULL OR snapshot_actor_type IN ('natural','pj','org')` |
| `chk_obs_id_type` | `snapshot_id_type IS NULL OR snapshot_id_type IN ('rut','pasaporte')` |

Se usó `VARCHAR` con `CHECK` en lugar de `ENUM` deliberadamente: incorporar un
valor nuevo en el futuro no requiere un `ALTER TABLE` con bloqueo de tabla.

### Tipos de participante

| `snapshot_actor_type` | Columnas de identidad relevantes | Vía de ingreso |
|---|---|---|
| `natural` | `snapshot_full_name`, `snapshot_national_id`, `snapshot_id_type`, `snapshot_comuna`, `snapshot_age` | ClaveÚnica o sin registro |
| `pj` | `snapshot_legal_name`, `snapshot_business_id`, `snapshot_trade_name`, `snapshot_address` | Solo sin registro |
| `org` | Igual que `pj` | Solo sin registro |

Toda lectura de identidad debe resolverse por tipo de actor:
`snapshot_full_name` o, si está vacío, `snapshot_legal_name`;
`snapshot_national_id` o, si está vacío, `snapshot_business_id`. Leer
`snapshot_full_name` directamente produce filas de PJ sin nombre visible.

### Temas (`category`)

`Uso de suelo` · `Vialidad` · `Áreas verdes` · `Patrimonio` · `Equipamiento` ·
`Riesgo natural` · `Otro`

La lista se define una sola vez en el modelo `Observation` y la comparten el
formulario público y la validación del servidor.

---

## 7. Tabla `institutional_responses`

Respuesta del Gobierno Regional a una observación.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `observation_id` | BIGINT UNSIGNED | No | FK a `observations`. `RESTRICT`. **Único** |
| `content` | LONGTEXT | No | Texto de la respuesta |
| `batch_id` | CHAR(36) UUID | Sí | Agrupa respuestas emitidas en un mismo lote |
| `responded_by` | BIGINT UNSIGNED | No | FK a `users`. `RESTRICT` |
| `responded_at` | TIMESTAMP | No | Momento de redacción. Por defecto el instante actual |
| `status` | ENUM | No | `draft` \| `published`. Por defecto `draft` |
| `published_at` | TIMESTAMP | Sí | Momento de publicación |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |

**Índices:** `PRIMARY(id)`, `UNIQUE(observation_id)`, `INDEX(batch_id)`,
`INDEX(status, published_at)`.

La unicidad de `observation_id` garantiza a nivel de base de datos que una
observación no pueda tener dos respuestas institucionales vigentes.

**Ciclo de vida:** `draft` (editable y descartable) → `published` (inmutable;
dispara la notificación por correo al `snapshot_email` de la observación).

---

## 8. Tabla `activity_log`

Bitácora de auditoría, provista por `spatie/laravel-activitylog`. Es de solo
lectura desde la aplicación.

| Columna | Tipo | Nulo | Descripción |
|---|---|:---:|---|
| `id` | BIGINT UNSIGNED | No | Clave primaria |
| `log_name` | VARCHAR(255) | Sí | Ámbito: `consultation`, `consultation_document`, `observation`, `user` |
| `description` | TEXT | No | Descripción del evento |
| `subject_type` / `subject_id` | VARCHAR / BIGINT | Sí | Entidad afectada (relación polimórfica) |
| `event` | VARCHAR(255) | Sí | `created` \| `updated` \| `deleted` |
| `causer_type` / `causer_id` | VARCHAR / BIGINT | Sí | Usuario responsable |
| `properties` | JSON | Sí | Valores anteriores y nuevos de los atributos auditados |
| `batch_uuid` | CHAR(36) | Sí | Agrupa eventos de una misma operación |
| `created_at` / `updated_at` | TIMESTAMP | Sí | Marcas de tiempo |

### Qué se audita por entidad

| Entidad | Eventos | Atributos registrados |
|---|---|---|
| `Consultation` | creación, modificación | `title`, `slug`, `status`, `instrument_type`, `starts_at`, `ends_at`, `auth_methods` |
| `ConsultationDocument` | creación, modificación | `consultation_id`, `title`, `original_filename`, `version`, `file_group_id`, `sha256` |
| `Observation` | **solo creación** | `public_id`, `consultation_id`, `submission_group_id`, `subject`, `category`, `auth_method_used` |
| `User` | creación, modificación | `name`, `last_name`, `email`, `role`, `is_active` |

`Observation` limita la auditoría al evento de creación de forma explícita: no
existen modificaciones de contenido que auditar. Las contraseñas y los datos
sensibles quedan excluidos del registro por configuración del modelo `User`.

---

## 9. Tablas de infraestructura

Provistas por el framework; se documentan porque son relevantes en operación.

| Tabla | Función |
|---|---|
| `sessions` | Sesiones activas (`SESSION_DRIVER=database`) |
| `cache`, `cache_locks` | Caché de aplicación y bloqueos de tareas programadas |
| `jobs`, `job_batches` | Cola de trabajos pendientes (envío de correos) |
| `failed_jobs` | Trabajos que agotaron sus reintentos. **Revisar si los correos no llegan** |
| `password_reset_tokens` | Tokens de recuperación de contraseña |
| `migrations` | Control de migraciones aplicadas |

---

## 10. Reglas de integridad a nivel de aplicación

Invariantes que **no** están expresados como restricciones de base de datos y
deben respetarse en cualquier intervención directa sobre los datos.

| # | Regla | Dónde se aplica |
|---|---|---|
| 1 | Una PJ u organización **nunca** tiene `user_id`: solo participan sin registro | Excepción en el modelo `Observation` al crear |
| 2 | `snapshot_full_name` es obligatorio para `snapshot_actor_type = 'natural'` | Validación del formulario y modelo |
| 3 | `snapshot_legal_name` y `snapshot_business_id` son obligatorios para `pj` y `org` | Validación del formulario |
| 4 | Una observación solo puede crearse si la consulta está `active` y dentro de su ventana | Validación contextual del formulario |
| 5 | El método de identificación usado debe estar habilitado en `auth_methods` de la consulta | Validación contextual del formulario |
| 6 | Una respuesta publicada no puede editarse ni eliminarse | Controlador de respuestas |
| 7 | Archivar y restaurar observaciones está restringido a `super-admin` | Middleware más verificación en el controlador |
| 8 | El contenido de una observación no se modifica por ninguna vía de la aplicación | Ausencia de acción de edición |
| 9 | `public_id` y `file_group_id` se generan automáticamente si vienen vacíos | Modelos correspondientes |
| 10 | `submitted_at` se completa con el instante actual si viene vacío | Modelo `Observation` |

---

## 11. Historial de migraciones

| Archivo | Qué hace |
|---|---|
| `0001_01_01_000000_create_users_table` | Tablas base de usuarios, sesiones y recuperación de contraseña |
| `0001_01_01_000001_create_cache_table` | Caché y bloqueos |
| `0001_01_01_000002_create_jobs_table` | Colas y trabajos fallidos |
| `2026_05_12_155017_add_gore_fields_to_users` | RUT, apellido, teléfono, rol, estado activo y datos de último ingreso |
| `2026_05_12_155018_create_consultations_table` | Procesos participativos |
| `2026_05_12_155019_create_consultation_stages_table` | Etapas del proceso *(eliminada después)* |
| `2026_05_12_155020_create_consultation_documents_table` | Antecedentes técnicos versionados |
| `2026_05_12_155021_create_observations_table` | Observaciones ciudadanas |
| `2026_05_12_155022_create_institutional_responses_table` | Respuestas institucionales |
| `2026_05_15_035344..46_activity_log` | Bitácora de auditoría (tabla, columna `event`, columna `batch_uuid`) |
| `2026_05_28_180000_add_attachment_to_observations` | Adjunto ciudadano por observación |
| `2026_05_28_181000_relax_observations_for_guest_mode` | Habilita participación sin registro: `user_id` y RUT opcionales |
| `2026_06_02_120000_add_storage_disk_to_consultation_documents` | Disco por documento, con relleno de datos existentes |
| `2026_06_02_120100_add_attachment_disk_to_observations` | Disco por adjunto, con relleno de datos existentes |
| `2026_06_02_130000_cleanup_manual_auth_method` | Elimina el registro manual: reclasifica el dato histórico y reduce el ENUM |
| `2026_06_02_140000_add_actor_fields_to_observations` | Tipo de participante (natural / PJ / organización) y sus datos |
| `2026_06_02_140100_backfill_snapshot_actor_type` | Rellena el tipo de participante en filas previas y aplica NOT NULL |
| `2026_06_02_140200_relax_snapshot_full_name_for_pj_org` | Permite nombre nulo para PJ y organizaciones |
| `2026_06_23_120000_remove_consultation_stages` | **Destructiva.** Elimina las etapas del proceso |
| `2026_06_23_130000_add_submission_group_id_to_observations` | Agrupa las observaciones de un mismo envío |
| `2026_07_03_200000_add_soft_deletes_to_observations` | Papelera de observaciones (archivar y restaurar) |

### Advertencias de operación

- **`2026_06_23_120000` es destructiva:** elimina la tabla de etapas y la
  columna asociada. Su reversión recrea la estructura pero **no los datos**.
  Tomar un snapshot de RDS antes de aplicarla en un ambiente con datos reales.
- **Error 1553 en MariaDB.** Soltar un índice compuesto que respalda una clave
  foránea falla. El patrón correcto, ya aplicado en la migración anterior, es
  crear primero el índice simple sobre la columna de la FK y recién después
  soltar el compuesto.
- **Migraciones de relleno separadas.** Las parejas
  `add_actor_fields` / `backfill_snapshot_actor_type` están divididas a
  propósito para no mantener un bloqueo de copia de tabla prolongado sobre una
  tabla que puede crecer mucho.

---

## 12. Mapa de rutas

61 rutas registradas. Todas bajo el grupo `web` (sesión, CSRF, cookies
cifradas, cabeceras de seguridad y CSP).

### 12.1 Públicas — sin autenticación

| Método | URI | Nombre | Controlador |
|---|---|---|---|
| GET | `/` | `home` | Cierre en `routes/web.php` |
| GET | `/healthz` | `healthz` | Cierre — verifica base de datos y almacenamiento |
| GET | `/consultas` | `public.consultations.index` | `Public\ConsultationController@index` |
| GET | `/consultas/{slug}` | `public.consultations.show` | `Public\ConsultationController@show` |
| GET | `/consultas/{slug}/antecedentes/{fileGroupId}/descargar` | `public.consultations.documents.download` | `Public\ConsultationController@download` |
| POST | `/consultas/{consultation}/observaciones` | `public.observations.store` | `Public\ObservationController@store` |
| GET | `/consultas/{slug}/observaciones/{publicId}/exito` | `public.observations.success` | `Public\ObservationController@success` |

**Autorización del envío de observaciones.** La ruta no lleva middleware
`auth` a propósito; la decisión la toma la validación del formulario:

- Con usuario autenticado: debe estar activo, ser ciudadano y tener el correo
  verificado.
- Sin usuario: la consulta debe admitir participación sin registro.
- Cualquier otro caso se rechaza con 403.

**Autorización de la página de confirmación.** Tampoco lleva `auth`: si la
observación tiene autor, solo su autor la ve (404 para el resto); si es una
participación sin registro, el acceso lo da el UUID secreto de la URL.

### 12.2 Identificación ciudadana

| Método | URI | Nombre | Notas |
|---|---|---|---|
| GET | `/auth/claveunica/redirect` | `citizen.claveunica.redirect` | Solo invitados. 10 peticiones/min |
| GET | `/auth/claveunica/callback` | `citizen.claveunica.callback` | Solo invitados |
| POST | `/cerrar-sesion` | `citizen.logout` | Requiere sesión |

Con `CLAVEUNICA_ENABLED=false` estas rutas responden 404 y la entrada por
ClaveÚnica se oculta del portal.

**Rutas de simulación** (`/dev/claveunica/simulate` y `/complete`) se
registran **únicamente** cuando `CLAVEUNICA_MODE=mock`. En producción no
existen.

### 12.3 Autenticación del personal

Prefijo `/admin`, provistas por Laravel Breeze.

| Método | URI | Nombre |
|---|---|---|
| GET / POST | `/admin/login` | `login` |
| POST | `/admin/logout` | `logout` |
| GET / POST | `/admin/forgot-password` | `password.request` / `password.email` |
| GET / POST | `/admin/reset-password[/{token}]` | `password.reset` / `password.store` |
| PUT | `/admin/password` | `password.update` |
| GET / POST | `/admin/confirm-password` | `password.confirm` |
| GET | `/admin/verify-email[/{id}/{hash}]` | `verification.notice` / `verification.verify` |
| POST | `/admin/email/verification-notification` | `verification.send` |

### 12.4 Backoffice — `auth` + `role:funcionario,super-admin`

| Método | URI | Nombre |
|---|---|---|
| GET | `/admin/dashboard` | `dashboard` |
| GET / PATCH / DELETE | `/admin/profile` | `profile.edit` / `profile.update` / `profile.destroy` |
| GET | `/admin/consultations` | `admin.consultations.index` |
| GET | `/admin/consultations/create` | `admin.consultations.create` |
| POST | `/admin/consultations` | `admin.consultations.store` |
| GET | `/admin/consultations/{consultation}` | `admin.consultations.show` |
| GET | `/admin/consultations/{consultation}/edit` | `admin.consultations.edit` |
| PUT/PATCH | `/admin/consultations/{consultation}` | `admin.consultations.update` |
| DELETE | `/admin/consultations/{consultation}` | `admin.consultations.destroy` (archiva) |
| PUT | `/admin/consultations/{consultation}/restore` | `admin.consultations.restore` |
| POST | `/admin/consultations/{consultation}/documents` | `admin.consultations.documents.store` |
| GET | `/admin/consultations/{consultation}/documents/{document}/download` | `admin.consultations.documents.download` |
| POST | `/admin/consultations/{consultation}/documents/{document}/replace` | `admin.consultations.documents.replace` |
| DELETE | `/admin/consultations/{consultation}/documents/{document}` | `admin.consultations.documents.destroy` |
| GET | `/admin/observations` | `admin.observations.index` |
| GET | `/admin/observations/export/{format}` | `admin.observations.export` (`xlsx` \| `csv`) |
| GET | `/admin/observations/batch` | `admin.observations.batch.create` |
| POST | `/admin/observations/batch` | `admin.observations.batch.store` |
| GET | `/admin/observations/{observation}` | `admin.observations.show` |
| GET | `/admin/observations/{observation}/attachment` | `admin.observations.attachment.download` |
| POST | `/admin/observations/{observation}/response` | `admin.observations.response.store` |
| PUT | `/admin/observations/{observation}/response` | `admin.observations.response.update` |
| POST | `/admin/observations/{observation}/response/publish` | `admin.observations.response.publish` |
| DELETE | `/admin/observations/{observation}/response` | `admin.observations.response.destroy` |

> El orden de declaración importa: `/admin/observations/batch` se registra
> **antes** de `/admin/observations/{observation}` para que `batch` no se
> interprete como un identificador.

### 12.5 Backoffice — `role:super-admin`

| Método | URI | Nombre |
|---|---|---|
| DELETE | `/admin/observations/{observation}/archive` | `admin.observations.archive` |
| PUT | `/admin/observations/{observation}/restore` | `admin.observations.restore` |
| GET | `/admin/users` | `admin.users.index` |
| GET | `/admin/users/create` | `admin.users.create` |
| POST | `/admin/users` | `admin.users.store` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` |
| POST | `/admin/users/{user}/toggle-active` | `admin.users.toggle-active` |
| GET | `/admin/activity-log` | `admin.activity-log.index` |

---

## 13. Middleware y límites de tasa

### Middleware propio

| Clase | Función |
|---|---|
| `EnsureUserHasRole` (alias `role`) | Restringe el acceso a los roles indicados |
| `SecurityHeaders` | Inyecta las cabeceras de seguridad en toda respuesta web |

### Cabeceras emitidas por `SecurityHeaders`

| Cabecera | Valor |
|---|---|
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), camera=(), microphone=(), payment=(), interest-cohort=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — solo en producción sobre HTTPS |

La CSP no se emite aquí: se delega al paquete `spatie/laravel-csp` para
mantener la política versionada.

### Límites de tasa

| Ruta | Límite |
|---|---|
| `POST /consultas/{consultation}/observaciones` | 5 por minuto |
| `GET /auth/claveunica/redirect` | 10 por minuto |
| `GET /healthz` | 30 por minuto |

### Límites de carga de archivos

| Concepto | Límite |
|---|---|
| Adjunto ciudadano | 10 MB; PDF, JPG, JPEG, PNG, WEBP, DOC, DOCX, XLS, XLSX, ODT, ODS, TXT |
| Observaciones por envío | 20 |
| Cuerpo de una observación | 10 a 10.000 caracteres |
| Antecedente técnico | 110 MB (límite de PHP y nginx) |

---

## 14. Política de seguridad de contenidos (CSP)

Definida en `App\Support\Csp\GoreCspPolicy` y registrada en `config/csp.php`.
Se habilita con `CSP_ENABLED` (activa por defecto).

| Directiva | Valor |
|---|---|
| `base-uri` | `'self'` |
| `default-src` | `'self'` |
| `connect-src` | `'self'` |
| `form-action` | `'self'` |
| `frame-ancestors` | `'none'` |
| `object-src` | `'none'` |
| `script-src` | `'self'` |
| `style-src` | `'self'`, `'unsafe-inline'`, `https://fonts.bunny.net` |
| `font-src` | `'self'`, `https://fonts.bunny.net` |
| `img-src` | `'self'`, `data:` |
| `media-src` | `'self'` |
| `upgrade-insecure-requests` | Activa en producción cuando `APP_URL` es HTTPS |

> **Consecuencia práctica para el desarrollo:** `script-src 'self'` **sin
> nonce** significa que **los `<script>` en línea no se ejecutan**. Todo el
> JavaScript de una página debe vivir en un archivo externo bajo `public/js/`.
> Es la causa habitual de que una confirmación o un comportamiento
> interactivo funcione en local y no en el servidor.

`'unsafe-inline'` permanece en `style-src` por los atributos `style="..."` de
los componentes Blade existentes; está señalado en el código como deuda a
eliminar gradualmente.

---

## 15. Comandos de consola

### Comandos propios

| Comando | Descripción |
|---|---|
| `php artisan gore:backup-observations [--force]` | Genera un XLSX con las observaciones de las consultas activas en `backups/observations/`. Sin consultas activas no hace nada, salvo que se use `--force`. Programado cada 48 h a las 02:00 |
| `php artisan storage:migrate-paths [--fix-disk]` | Verifica que cada `storage_path` exista en el disco declarado y reporta huérfanos. Con `--fix-disk` corrige el disco declarado por fila |

### Comandos habituales de operación

| Comando | Uso |
|---|---|
| `php artisan migrate --force` | Aplica migraciones en un ambiente productivo |
| `php artisan migrate:status` | Verifica qué migraciones están aplicadas |
| `php artisan config:cache` | Regenera la caché de configuración tras editar el `.env` |
| `php artisan route:cache` | Regenera la caché de rutas |
| `php artisan view:clear && php artisan view:cache` | Recompila las vistas Blade |
| `php artisan queue:work` | Procesa la cola (en el servidor lo ejecuta `gore-queue.service`) |
| `php artisan queue:failed` | Lista los trabajos fallidos |
| `php artisan tinker` | Consola interactiva |

### Pruebas automatizadas

```bash
php artisan test
```

Suite Pest con cobertura sobre: autorización del backoffice, bitácora de
auditoría, respuestas institucionales, gestión de observaciones, navegación
pública, ciclo de vida de las consultas, tipos de participante en
participación sin registro, cabeceras de seguridad y validación de RUT.
