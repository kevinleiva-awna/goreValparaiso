# Evidencia para el formulario de certificación ClaveÚnica

Material para el formulario de solicitud de habilitación de producción de la
Unidad de Gobierno Digital. Generado desde el código real del repositorio —
cada bloque muestra el archivo y las líneas exactas.

> **Observación del 15-sep-2026.** Gobierno Digital rechazó la primera entrega:
> la evidencia 3 mostraba `.env.example` con las claves **vacías**, y lo que pide
> la página 30 del Manual de Integración es el archivo de entorno del ambiente de
> **producción** con los valores cargados, más los métodos donde se consumen.
> La evidencia 3 se rehízo con eso; las evidencias 1 y 2 no fueron observadas y
> se mantienen igual.

## Qué sube en cada campo del formulario

| Campo del formulario | Archivo |
|---|---|
| URL para acceder al sitio | `www.participa.gobiernovalparaiso.cl` |
| Evidencia Configuración Endpoint **Token** con la llamada al dominio accounts.claveunica.gob.cl | [`evidencia-1-endpoint-token.png`](evidencia-1-endpoint-token.png) |
| Evidencia Configuración Endpoint **UserInfo** con la llamada al dominio accounts.claveunica.gob.cl | [`evidencia-2-endpoint-userinfo.png`](evidencia-2-endpoint-userinfo.png) |
| Evidencia de credenciales (**client_id** y **client_secret**) en variables de entorno o similar | [`evidencia-3-credenciales-en-entorno.png`](evidencia-3-credenciales-en-entorno.png) |

Los PNG pesan menos de 600 KB cada uno (el límite del formulario es 3 MB).

## Qué muestra ahora la evidencia 3

Cinco paneles, en el orden en que el certificador los va a leer:

1. **El `.env` del servidor de producción** — `APP_ENV=production`, la URL del
   sitio y las cuatro variables `CLAVEUNICA_*` con el par de credenciales de
   producción cargado. Es el panel que faltaba.
2. `config/claveunica.php:37-38` — el código resuelve las credenciales con
   `env()` y no contiene ningún valor.
3. `ClaveUnicaController::redirect()` — método que consume el `client_id` para
   armar la URL de `authorize/`.
4. `ClaveUnicaController::fetchUserInfoLive()` — método que envía `client_id` y
   `client_secret` por POST a `token/`.
5. `.gitignore` — el `.env` no entra al control de versiones.

Los puntos 3 y 4 cubren lo que el manual llama *"métodos en los cuales se
consumen dichas variables"*, que la primera entrega tampoco mostraba en este
campo.

## Información adicional (recuadro de 500 caracteres)

Texto sugerido — **496 caracteres**:

> Integración OIDC Authorization Code con ClaveÚnica, Guía Técnica v5.5. URLs en
> config/claveunica.php y flujo en ClaveUnicaController.php: authorize/, token/ y
> userinfo/ apuntan a accounts.claveunica.gob.cl. Token y UserInfo se llaman por
> POST desde el backend, nunca desde el navegador. State aleatorio por sesión,
> validado en el callback. El client_id y el client_secret de producción se cargan
> como variables de entorno en el .env del servidor, fuera del repositorio. Cierre
> de sesión federado.

*(el recuadro cuenta caracteres, no palabras: pegar en una sola línea, sin saltos)*

## Cómo regenerar los PNG

### 1. Poner las credenciales de producción donde el script las lea

El `.env` de producción no está en el repositorio — es justamente lo que se
certifica — así que los valores se pasan por un archivo local que **no se
versiona** (está en `.gitignore`):

```
docs/claveunica/evidencia/credenciales.local.json
```

```json
{
  "prompt": "ubuntu@gore-prod:~$",
  "app_env": "production",
  "app_url": "https://www.participa.gobiernovalparaiso.cl",
  "enabled": "true",
  "mode": "live",
  "client_id": "...",
  "client_secret": "..."
}
```

Copiar ahí la salida **real** del servidor (`grep '^CLAVEUNICA' /var/www/gore/.env`),
incluido el prompt tal como aparece en la consola. Es evidencia de una
certificación: lo que muestre la captura tiene que ser lo que el servidor
contesta. Sin este archivo el script deja marcadores `<<FALTA ...>>` visibles,
para que no se suba una captura a medias.

### 2. Generar

Requiere Node y Microsoft Edge (o cualquier Chromium):

```bash
node docs/claveunica/evidencia/generar-evidencia.cjs
```

Eso reescribe los `.html` intermedios leyendo los archivos fuente actuales; luego
se capturan con Edge headless a 2x y se recortan con ImageMagick. El comando
completo está al final de `generar-evidencia.cjs`.

Si cambian las líneas de `config/claveunica.php` o de `ClaveUnicaController.php`,
hay que ajustar los rangos declarados en el script antes de regenerar.

### 3. Después de subir

Los `.png`, los `.html` y el `credenciales.local.json` quedan fuera de git: las
capturas muestran el `client_secret` de producción en pantalla. Borrarlos del
equipo una vez enviado el formulario.

## Contexto

El estado de los ocho requisitos de certificación está en
[`../puesta-en-marcha.md`](../puesta-en-marcha.md), punto 4. Estas capturas cubren
los requisitos 6 (token/ y userinfo/ llamados desde el backend) y 7 (`client_id` y
`client_secret` fuera del código fuente).
