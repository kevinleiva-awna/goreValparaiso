# ClaveÚnica — puesta en marcha de las credenciales

Referencia: **Manual de Integración / Guía Técnica ClaveÚnica v5.5** (enero 2025),
Secretaría de Gobierno Digital.
Complementa a [solicitud-credenciales.md](solicitud-credenciales.md), que documenta
lo que se declaró al pedir las credenciales.

---

## 1. Qué llegó y qué habilita

La aprobación entrega **tres pares** `client_id` / `client_secret`:

| Par | Para qué sirve | Estado al recibirlo |
|---|---|---|
| **Sandbox** | Probar la integración. Solo acepta 4 RUN de prueba. | Operativo de inmediato |
| **QA** | Idéntico a sandbox, otro ambiente de la plataforma. Mismos 4 RUN. | Operativo de inmediato |
| **Producción** | Autentica ciudadanos reales. | **Desactivado** hasta aprobar la certificación |

> Si se configuran las credenciales de producción antes de certificar, el login de
> ClaveÚnica muestra *"La institución no está habilitada en ClaveÚnica"*. No es un
> error de la plataforma: es el estado normal previo a la certificación.

### RUN de prueba de sandbox y QA

Todos con contraseña `testing`:

```
44.444.444-4
55.555.555-5
88.888.888-8
99.999.999-9
```

Los cuatro son RUT válidos módulo 11, así que pasan la regla `App\Rules\Rut`.

---

## 2. Configuración por ambiente

Las credenciales **no van al repositorio**. Se cargan en el `.env` de cada servidor
(requisito explícito de certificación: *"Client_id y Client_secret ocultos"*).

| Ambiente | `CLAVEUNICA_ENABLED` | `CLAVEUNICA_MODE` | Par de credenciales |
|---|---|---|---|
| Local (XAMPP) | `true` | `mock` | ninguna — simulador |
| Staging (`i-044e3f43201359d9a`) | `true` | `live` | **sandbox** o **QA** |
| Producción (`i-099343b5b7dffc94f`) | `true` | `live` | **producción** (post-certificación) |

Al editar el `.env` por consola:

```bash
printf '\nCLAVEUNICA_MODE=live\n' >> .env && php artisan config:cache
```

> **Gotcha conocido:** `echo >> .env` pega la línea nueva a la última existente
> (el archivo no termina en salto de línea) e invalida el parseo de *dotenv*
> completo. Usar `printf` como arriba. Después de tocar el `.env` **siempre**
> `php artisan config:cache`, o los valores viejos siguen cacheados.

Poner `CLAVEUNICA_MODE=live` en staging tiene un efecto lateral deseado: las rutas
`/dev/claveunica/*` del simulador dejan de registrarse (ver `routes/web.php`), así
que no conviven un login real y uno falsificable en el mismo host.

---

## 3. Lo que hace la aplicación (para la evidencia de certificación)

Todo el flujo vive en `app/Http/Controllers/Public/Auth/ClaveUnicaController.php`.

| Paso de la guía | Implementación |
|---|---|
| 1. Token anti-falsificación | `redirect()` — `Str::random(40)` por sesión, guardado en `claveunica.state` |
| 2. Solicitud de autenticación | `GET https://accounts.claveunica.gob.cl/openid/authorize/` con `client_id`, `response_type=code`, `scope=openid run name`, `redirect_uri`, `state` |
| 3. Confirmar el state | `callback()` compara el `state` recibido con el de sesión antes de tocar el IdP |
| 4. Code → access token | `POST https://accounts.claveunica.gob.cl/openid/token/`, `x-www-form-urlencoded`, desde el backend |
| 5. Autenticar usuario | Se lee `access_token` de la respuesta |
| 6. Datos del ciudadano | `POST https://accounts.claveunica.gob.cl/openid/userinfo/` con `Authorization: Bearer` |
| 7. Cierre de sesión | `logout()` destruye la sesión local y rebota a `/api/v1/accounts/app/logout/?redirect=…` |

Las pruebas que fijan este contrato están en
`tests/Feature/Public/ClaveUnicaLiveFlowTest.php` y
`tests/Feature/Public/ClaveUnicaLogoutTest.php`.

### Forma de la respuesta de `userinfo`

```json
{
  "sub": "1234567",
  "RolUnico": { "DV": "9", "numero": 12345678, "tipo": "RUN" },
  "name": {
    "apellidos": ["Del Río", "Gonzalez"],
    "nombres": ["María", "Carmen"]
  }
}
```

Dos trampas que costaron un bug antes de la primera conexión real:

- `name` es un **objeto con dos arreglos**, no un string.
- `apellidos` cuelga de `name`, no de la raíz del JSON.

La llave del ciudadano en la base es **`RolUnico.numero`**, nunca `sub` — la guía lo
prohíbe explícitamente. El JSON documentado **no trae `email`**: la app genera
`<run>@claveunica.local` como placeholder.

### Sobre PKCE

No se envía. El documento de descubrimiento del IdP
(`accounts.claveunica.gob.cl/openid/.well-known/openid-configuration`) no declara
`code_challenge_methods_supported`, y la guía técnica no lo menciona en ningún paso.
Un `code_challenge` que el proveedor ignora no agrega seguridad y sí agrega un
parámetro no documentado a una petición que el equipo certificador revisa. El
anti-CSRF que la guía exige es el `state`, que sí va y sí se verifica.

---

## 4. Certificación para habilitar producción

Se pide por ticket en la Mesa de Servicios. Requisitos y estado:

| # | Requisito | Estado |
|---|---|---|
| 1 | **Botón oficial de ClaveÚnica** según lineamientos de marca | ❌ **Pendiente** — hoy hay un `btn-primary` de Bootstrap con ícono `bi-shield-check` en `layouts/public.blade.php`, `welcome.blade.php` y `public/consultas/show.blade.php` |
| 2 | **HTTPS** en el ambiente de producción | ✅ Let's Encrypt sobre `www.participa.gobiernovalparaiso.cl` |
| 3 | **Llamada a pantalla completa**, sin iframe ni popup, barra de direcciones visible | ✅ Es un `<a href>` de navegación normal |
| 4 | **State dinámico** por sesión | ✅ `Str::random(40)` en cada `redirect()` |
| 5 | **Secuencia OIDC completa** y todos los endpoints bajo `accounts.claveunica.gob.cl` | ✅ Ver tabla del punto 3 |
| 6 | **token/ y userinfo/ llamados desde el backend** — se pide captura del bloque de código | ✅ `fetchUserInfoLive()`, ambos por POST con la URL literal |
| 7 | **`client_id` y `client_secret` fuera del código fuente** — se pide evidencia visual | ✅ `config/claveunica.php` los lee de variables de entorno |
| 8 | **Cierre de sesión** con link o botón visible, y llamada al endpoint de logout | ✅ `citizen.logout` (POST con CSRF) → logout federado |

El único bloqueante de código es el **#1**.

---

## 5. Pendientes fuera del código

| # | Ítem | Responsable |
|---|---|---|
| 1 | Confirmar **qué se registró como Logout URI**. El formulario pide solo la *autoridad* (el host), pero en la solicitud se declararon rutas completas. Si quedó registrado algo que no calza, el `redirect` posterior al logout simplemente no ocurre. | AWNA (verificar) / Mesa de Servicios |
| 2 | ~~DNS + TLS de staging~~ **✅ HECHO 27-ago-2026** (ver punto 7) | — |
| 3 | Dominio de producción: `participa.gobiernovalparaiso.cl` no es `.gob.cl`. La certificación evalúa este punto (Norma Técnica MINSEGPRES, cap. II art. 13) | GORE |
| 4 | ~~Botón oficial~~ **✅ HECHO 27-ago-2026** — `<x-claveunica-button>` | — |

---

## 7. Staging en HTTPS (hecho el 27-ago-2026)

ClaveÚnica no acepta direcciones IP en los redirect/logout URI, así que staging
no podía probar sandbox mientras respondiera solo por `3.227.228.33`.

- DNS `pruebas.participa.gobiernovalparaiso.cl` → `3.227.228.33` (creado por el GORE).
- Certificado **Let's Encrypt** emitido por webroot (HTTP-01, `/var/www/gore/public`),
  certbot 1.21.0 de apt. Expira el **2026-11-25**; renovación automática por
  `certbot.timer` con deploy-hook `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh`.
- Se registró **sin correo de contacto** (`--register-unsafely-without-email`), así
  que Let's Encrypt no enviará avisos de expiración. Si se quiere el aviso:
  `certbot update_account --email <correo>`.
- Nginx: todo HTTP hace 301 al dominio; la IP cruda deja de ser vía de acceso.
  Config versionada en [`docs/staging/gore-staging-nginx.conf`](../staging/gore-staging-nginx.conf).
- `APP_URL=https://pruebas.participa.gobiernovalparaiso.cl` + `config:cache`.
  Backups en el server: `.env.bak-20260827` y `gore.conf.bak-20260827`.

Verificado: `http → 301 → https`, `https → 200`, y `route('citizen.claveunica.callback')`
resuelve a `https://pruebas.participa.gobiernovalparaiso.cl/auth/claveunica/callback`,
que es exactamente la URI declarada en la solicitud de credenciales.

---

## 6. Orden sugerido

1. Cargar el par **sandbox** en el `.env` de staging con `CLAVEUNICA_MODE=live`.
2. Probar el ciclo completo con los 4 RUN de prueba: ingreso → observación → cierre
   de sesión → reingreso (debe volver a pedir credenciales).
3. Implementar el botón oficial.
4. Guardar la evidencia que pide la certificación (capturas de `fetchUserInfoLive()`
   y de `config/claveunica.php`).
5. Abrir el ticket de certificación.
6. Con las credenciales de producción ya habilitadas, cargarlas en el `.env` de prod.
