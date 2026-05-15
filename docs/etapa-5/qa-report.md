# Reporte de QA - Etapa 5a

**Proyecto**: Plataforma de Procesos Participativos Reglados — GORE Valparaíso
**Periodo**: D2–D5 (14–17 mayo 2026)
**Responsable**: Kevin Leiva (AWNA)
**Alcance**: Cierre Etapa 4 residual + entrada a Etapa 5a (QA y hardening).

---

## 1. Tareas cubiertas

| ID | Descripción | Estado |
|---|---|---|
| D14 | Gestión de respuestas institucionales (individual + lote + portal público + mail al ciudadano) | ✅ |
| D20 | Audit log inmutable con `spatie/laravel-activitylog` en 6 modelos | ✅ |
| T4.6 | Suite Pest de dominio (46 tests) | ✅ |
| D21 | Hardening (headers, CSP estricta, rate limits, HTTPS forzado en producción) | ✅ |
| D21 | Scripts k6 (carga) y OWASP ZAP (baseline) preparados para Etapa 5b | ✅ |

---

## 2. Suite automatizada (Pest)

Ejecutar con `composer test` o `vendor/bin/pest`.

```
PASS  Tests\Feature\Admin\AuditLogTest                  8 tests
PASS  Tests\Feature\Admin\AuthorizationTest             6 tests
PASS  Tests\Feature\Admin\InstitutionalResponseTest     9 tests
PASS  Tests\Feature\Admin\ObservationManagementTest     5 tests (+1 skipped)
PASS  Tests\Feature\Public\ConsultationBrowsingTest     4 tests
PASS  Tests\Feature\Security\SecurityHeadersTest        4 tests
PASS  Tests\Unit\Rules\RutTest                         10 tests
─────────────────────────────────────────────────────────────
Tests:    46 passed, 1 skipped (145 assertions)
Duration: ~8s
```

### Cobertura crítica

- **Respuestas institucionales (D14)**: alta autoría, batch con `batch_id` compartido, inmutabilidad post-publicación, notificación por correo, rechazo de duplicados, restricción por rol.
- **Audit log (D20)**: causer correcto, omisión de campos sensibles (password, RUT, email, IP), creación/actualización auditadas, Observation solo en `created`, backoffice restringido a super-admin.
- **Autorización**: ciudadano → 403 en `/admin/*`, funcionario inactivo → 403, super-admin con acceso completo.
- **Hardening**: headers OWASP, CSP estricta con `default-src 'self'`, rate limit anti-flood en registro.
- **Validación de RUT**: módulo 11, casos válidos, formatos múltiples, K mayúscula y minúscula.

---

## 3. Hardening de seguridad (D21)

### 3.1 Cabeceras HTTP inyectadas globalmente

Middleware: `App\Http\Middleware\SecurityHeaders`. Aplicado a todas las rutas web.

| Header | Valor | Propósito |
|---|---|---|
| `X-Frame-Options` | `DENY` | Anti-clickjacking |
| `X-Content-Type-Options` | `nosniff` | Anti MIME-sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Privacidad de origen |
| `Permissions-Policy` | `geolocation=(), camera=(), microphone=(), payment=(), interest-cohort=()` | Bloqueo de APIs sensibles |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Solo en producción HTTPS |

### 3.2 Content-Security-Policy

Policy: `App\Support\Csp\GoreCspPolicy`. Aplicada vía `spatie/laravel-csp`.

```
default-src 'self';
script-src 'self';
style-src 'self' 'unsafe-inline' https://fonts.bunny.net;
font-src 'self' https://fonts.bunny.net;
img-src 'self' data:;
connect-src 'self';
form-action 'self';
frame-ancestors 'none';
object-src 'none';
base-uri 'self';
media-src 'self';
upgrade-insecure-requests; (solo en producción)
```

**Notas**:
- `unsafe-inline` está habilitado solo para `style-src` por compatibilidad con los estilos inline existentes en componentes Blade. `script-src` NO usa `unsafe-inline`, manteniendo la protección anti-XSS reflexivo.
- Bunny Fonts está whitelisted explícitamente (CSS + woff2 de Inter).
- Una iteración futura puede eliminar `unsafe-inline` migrando todos los `style="..."` a clases CSS o nonces.

### 3.3 Rate limiting

Configurado en `routes/web.php`:

| Endpoint | Límite |
|---|---|
| `POST /registrarme` | 5 req/min/IP |
| `POST /ingresar` (citizen) | 10 req/min/IP |
| `GET /auth/claveunica/redirect` | 10 req/min/IP |
| `POST /consultas/{slug}/observaciones` | 5 req/min/usuario (existente desde Etapa 4) |

### 3.4 HTTPS y cookies

- `AppServiceProvider::boot()` fuerza `URL::forceScheme('https')` en producción.
- `config/session.php` (default Laravel): `secure=env('SESSION_SECURE_COOKIE', true)`, `same_site=lax`.
- `TrustProxies` se complementa en Etapa 5b con la IP del ELB de AWS.

---

## 4. Test de carga (k6)

Scripts preparados en `tests/k6/`. Requieren `k6` instalado localmente.

### 4.1 Lectura pública (`observation-submission.js`)

```bash
k6 run -e BASE_URL=http://localhost:8000 tests/k6/observation-submission.js
```

- 50 VUs con ramp-up de 30s + sostenido 1min + ramp-down 30s
- Endpoints: `/`, `/consultas`, `/consultas/{slug}`
- Thresholds: `p95<300ms` en lectura, error rate `<1%`

### 4.2 Backoffice autenticado (`admin-listing.js`)

```bash
k6 run -e BASE_URL=http://localhost:8000 \
       -e EMAIL=claudio@gorevalparaiso.cl \
       -e PASSWORD=password \
       tests/k6/admin-listing.js
```

- 10 VUs durante 1 minuto contra `/admin/observations`
- Thresholds: `p95<500ms`, error rate `<1%`

**Pendiente Etapa 5b**: ejecutar contra entorno staging AWS y registrar percentiles reales.

---

## 5. OWASP ZAP baseline

Script: `scripts/owasp-zap-smoke.sh` (requiere Docker).

```bash
BASE_URL=http://host.docker.internal:8000 ./scripts/owasp-zap-smoke.sh
```

Salida en `docs/etapa-5/zap-baseline-report.html`. Falla en High; Medium se documentan acá.

**Pendiente Etapa 5b**: ejecutar contra staging post-despliegue AWS.

---

## 6. Cumplimiento DS 7/2023

| Requisito | Implementación |
|---|---|
| Trazabilidad inalterable de identidad | `Observation` con snapshot RUT/email/nombre + audit log con `causer` |
| Audit log de cambios | `activity_log` (spatie) en 6 modelos con campos sensibles excluidos |
| Cifrado en tránsito | HTTPS forzado en producción + HSTS |
| Headers anti-XSS / clickjacking | SecurityHeaders + CSP estricta |
| Rate limit anti-flood | `throttle:5,1` y `throttle:10,1` por endpoint sensible |
| Cookies seguras | `secure=true` + `same_site=lax` en producción |

---

## 7. Próximos pasos (Etapa 5b y 5c)

| Bloque | Tarea | Días |
|---|---|---|
| 5b | Provisioning AWS (Terraform + GitHub Actions) | 2 |
| 5b | Despliegue inicial + smoke test en staging | 1 |
| 5b | Ejecutar k6 + ZAP contra staging y actualizar este reporte | 0.5 |
| 5c | Manuales de usuario + capacitación funcionarios | 1 |
| 5c | Handover y traspaso de credenciales | 0.5 |

Estado al cierre de Etapa 5a: **contrato al 100% en código**. Margen vs. hito 11/06/26: holgado.
