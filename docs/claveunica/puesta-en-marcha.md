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

> **Gotcha del caché de rutas:** ese registro condicional depende de
> `config('claveunica.mode')`, y `route:cache` **congela la decisión al momento de
> cachear**. Si se cambia `CLAVEUNICA_MODE` hay que correr `route:clear && route:cache`;
> no basta con `config:cache`. Por eso los servidores se dejaron con
> `CLAVEUNICA_MODE=live` y su caché de rutas ya reconstruida: cargar después el
> `client_id` y el `client_secret` solo requiere `config:cache`.

### Cargar las credenciales en un servidor

Las claves ya están declaradas y vacías en el `.env`, así que se rellenan en su sitio
(y no se duplican, que es lo que pasaría al hacer `>>` con una clave ya presente:
*dotenv* se queda con la primera aparición y la segunda se ignora en silencio).

```bash
cd /var/www/gore
sudo -u www-data sed -i 's|^CLAVEUNICA_CLIENT_ID=.*|CLAVEUNICA_CLIENT_ID=EL_ID|' .env
sudo -u www-data sed -i 's|^CLAVEUNICA_CLIENT_SECRET=.*|CLAVEUNICA_CLIENT_SECRET=EL_SECRET|' .env
sudo -u www-data sed -i 's|^CLAVEUNICA_ENABLED=.*|CLAVEUNICA_ENABLED=true|' .env
sudo -u www-data php artisan config:cache
```

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
| 7. Cierre de sesión | `logout()` destruye la sesión local y entrega la pantalla de tránsito, que llama al endpoint del IdP y devuelve al home (ver abajo) |

> **El endpoint de logout responde `204 No Content`**, sin cabecera `Location` —
> con y sin el parámetro `redirect`. Un `redirect()->away()` del servidor hacia
> allí hace que el navegador emita la petición y **se quede donde estaba**: al
> ciudadano no le pasa nada visible, vuelve a apretar "Cerrar sesión", y ese
> segundo POST llega con el token CSRF de la sesión ya destruida → **419 PAGE
> EXPIRED**. Ocurrió en staging el 28-ago-2026.
>
> Por eso se aplica el **Método 2** de la guía: `signingOut()` entrega una
> pantalla de tránsito y `public/js/claveunica-logout.js` navega al endpoint (la
> petición lleva las cookies del IdP y cierra su sesión) y, pasado 1,5 s, vuelve
> al home. El 204 juega a favor: como el navegador no se mueve, el temporizador
> sigue vivo. La guía **prohíbe** llamar al endpoint desde un popup o un iframe
> — provoca un error de CORS y la sesión de ClaveÚnica queda abierta.

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
| 1 | **Botón oficial de ClaveÚnica** según lineamientos de marca | ✅ `<x-claveunica-button>` — marcado y CSS oficiales, verificado en staging el 28-ago-2026 |
| 2 | **HTTPS** en el ambiente de producción | ✅ Let's Encrypt sobre `www.participa.gobiernovalparaiso.cl` |
| 3 | **Llamada a pantalla completa**, sin iframe ni popup, barra de direcciones visible | ✅ Es un `<a href>` de navegación normal |
| 4 | **State dinámico** por sesión | ✅ `Str::random(40)` en cada `redirect()` |
| 5 | **Secuencia OIDC completa** y todos los endpoints bajo `accounts.claveunica.gob.cl` | ✅ Ver tabla del punto 3 |
| 6 | **token/ y userinfo/ llamados desde el backend** — se pide captura del bloque de código | ✅ `fetchUserInfoLive()`, ambos por POST con la URL literal |
| 7 | **`client_id` y `client_secret` fuera del código fuente** — se pide evidencia visual | ✅ `config/claveunica.php` los lee de variables de entorno |
| 8 | **Cierre de sesión** con link o botón visible, y llamada al endpoint de logout | ✅ `citizen.logout` (POST con CSRF) → pantalla de tránsito → endpoint del IdP. **Probado extremo a extremo en staging el 28-ago-2026: al reingresar, ClaveÚnica vuelve a pedir credenciales**, que es la comprobación que hace el certificador |

Los ocho requisitos están cumplidos y verificados contra el IdP real en staging.
Lo que queda para pedir la certificación no es código, sino la evidencia que
solicita la Mesa de Servicios (capturas de `fetchUserInfoLive()` y de
`config/claveunica.php`) y el punto del dominio `.gob.cl`.

---

## 5. Pendientes fuera del código

| # | Ítem | Responsable |
|---|---|---|
| 1 | ~~Confirmar qué se registró como Logout URI~~ **✅ RESUELTO 28-ago-2026**: el cierre federado se probó y funciona. La implementación no depende de que ClaveÚnica honre el parámetro `redirect` — la pantalla de tránsito vuelve al home por su cuenta — así que la duda sobre el formato registrado deja de ser bloqueante | — |
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

## 8. Estado de cada ambiente (27-ago-2026)

| | Staging | Producción |
|---|---|---|
| Host | `https://pruebas.participa.gobiernovalparaiso.cl` | `https://www.participa.gobiernovalparaiso.cl` |
| Instancia | `i-044e3f43201359d9a` (rama `dev`) | `i-099343b5b7dffc94f` (rama `prod`) |
| Código desplegado | `a7cb5e0` ✅ | pendiente de desplegar `a7cb5e0` |
| Assets (`npm run build`) | ✅ reconstruidos | pendiente |
| `CLAVEUNICA_MODE` | `live` ✅ | `live` (ya estaba) |
| Simulador `/dev/claveunica/*` | fuera (404) ✅ | nunca estuvo (`APP_ENV=production`) |
| `client_id` / `client_secret` | **vacíos — los carga el GORE/AWNA** | **vacíos — cargar el par de producción** |
| `CLAVEUNICA_ENABLED` | `false` — activar al cargar sandbox | `false` — activar solo tras certificar |

El despliegue de producción sigue el patrón documentado en
`docs/tecnica/01-manual-despliegue-operacion.md`: `git fetch` + avance a `origin/prod`,
`npm ci && npm run build` (obligatorio: cambió el SCSS), y luego
`config:cache`, `route:cache`, `view:cache` y `systemctl restart gore-queue`.
Recordar que el `.env` de producción también vive en
`s3://gore-prod-uploads-184758133903/deploy/.env`.

---

## 9. Orden sugerido

1. Cargar el par **sandbox** en el `.env` de staging con `CLAVEUNICA_MODE=live`.
2. Probar el ciclo completo con los 4 RUN de prueba: ingreso → observación → cierre
   de sesión → reingreso (debe volver a pedir credenciales).
3. Implementar el botón oficial.
4. Guardar la evidencia que pide la certificación (capturas de `fetchUserInfoLive()`
   y de `config/claveunica.php`).
5. Abrir el ticket de certificación.
6. Con las credenciales de producción ya habilitadas, cargarlas en el `.env` de prod.
