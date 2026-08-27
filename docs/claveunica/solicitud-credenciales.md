# Datos técnicos para la solicitud de credenciales ClaveÚnica

**Sistema:** Plataforma de Consultas Públicas — Gobierno Regional de Valparaíso
**Proveedor de desarrollo:** AWNA
**Tipo de integración:** OpenID Connect — Authorization Code Flow + PKCE (S256)
**Scopes solicitados:** `openid run name`
**Fecha:** agosto 2026

---

## 1. REDIRECT URI's (callback)

| Ambiente | URI |
|---|---|
| Producción | `https://www.participa.gobiernovalparaiso.cl/auth/claveunica/callback` |
| QA | `https://pruebas.participa.gobiernovalparaiso.cl/auth/claveunica/callback` |
| Sandbox / Testing | `https://pruebas.participa.gobiernovalparaiso.cl/auth/claveunica/callback` |

## 2. LOGOUT URI's

| Ambiente | URI |
|---|---|
| Producción | `https://www.participa.gobiernovalparaiso.cl/auth/claveunica/logout` |
| QA | `https://pruebas.participa.gobiernovalparaiso.cl/auth/claveunica/logout` |
| Sandbox | `https://pruebas.participa.gobiernovalparaiso.cl/auth/claveunica/logout` |

Se usan como parámetro `redirect` del endpoint
`https://accounts.claveunica.gob.cl/api/v1/accounts/app/logout/?redirect=<logout_uri>`.

### Cumplimiento del formato exigido

- Solo esquema + autoridad + path. Sin `query`, sin `fragment`, sin puertos.
- Sin direcciones IP.
- HTTPS en los tres ambientes.
- El path corresponde a rutas reales ya implementadas en la aplicación
  (`routes/web.php`, controlador `App\Http\Controllers\Public\Auth\ClaveUnicaController`).
  Se prefirió declarar la ruta real de la app antes que copiar el `/sso/cu/callback`
  del ejemplo, para no arrastrar una ruta ficticia difícil de cambiar después.

> **Importante:** modificar cualquiera de estas URI después de emitidas las credenciales
> exige un ticket en la Mesa de Servicios de ClaveÚnica ("Actualización de Redirect_URI
> de Credenciales de Integración ClaveÚnica"). Conviene confirmar los dominios **antes**
> de enviar el formulario.

---

## 3. Puntos que el GORE debe resolver antes de enviar el formulario

### 3.1 Dominio de producción y la exigencia `.gob.cl` (bloqueante potencial)

El propio formulario advierte:

> "Es requisito obligatorio que la URI de redirección de producción use su dominio `.gob.cl`,
> según la Norma Técnica sobre sistemas y sitio web de los órganos de la administración del
> estado (MINSEGPRES), capítulo II, artículo 13. Este requisito será evaluado una vez enviada
> la solicitud."

El dominio productivo actual es **`participa.gobiernovalparaiso.cl`**, que es `.cl`, **no `.gob.cl`**.
El equipo certificador de ClaveÚnica evalúa este punto y puede observar o rechazar la solicitud
de producción.

Caminos posibles (decisión del GORE, no del proveedor):

1. **Usar un subdominio bajo el `.gob.cl` institucional** (ej. `participa.gorevalparaiso.gob.cl`).
   Es el camino que cumple la norma sin negociación.
2. **Enviar con `gobiernovalparaiso.cl` y esperar el pronunciamiento del certificador.**
   Riesgo: observación y reproceso; se pierde el tiempo del ciclo de certificación.
3. **Consultar previamente a la Mesa de Servicios** si aceptan el dominio actual, dado que
   contiene el nombre de la institución.

Impacto en la plataforma si finalmente cambia el dominio: es acotado — registro DNS nuevo,
certificado TLS (Let's Encrypt), `server_name` de Nginx, `APP_URL` y actualización de las
URI vía Mesa de Servicios. Lo caro no es el cambio técnico, es rehacer la solicitud.

### 3.2 Subdominio de pruebas (bloqueante para QA/Sandbox)

Hoy el ambiente de staging responde **solo por IP** (`3.227.228.33`) y sin certificado TLS.
ClaveÚnica **no acepta direcciones IP** en las URI. Para poder declarar las URI de QA y
Sandbox se necesita:

1. Que el administrador del DNS de `gobiernovalparaiso.cl` cree el registro:

   | Tipo | Nombre | Valor |
   |---|---|---|
   | A | `pruebas.participa.gobiernovalparaiso.cl` | `3.227.228.33` |

2. Con el DNS propagado, AWNA emite el certificado TLS (Let's Encrypt) y deja el ambiente
   en HTTPS. Es cosa de minutos una vez exista el registro.

Si el GORE prefiere otro nombre para el ambiente de pruebas, se ajusta antes de enviar
el formulario — después implica ticket.

---

## 4. Pendientes del lado de AWNA

> **Las credenciales llegaron en agosto de 2026.** Lo que sigue en esta tabla quedó
> obsoleto; el estado vigente, el procedimiento de activación por ambiente y la
> checklist de certificación están en [puesta-en-marcha.md](puesta-en-marcha.md).

| # | Ítem | Estado |
|---|---|---|
| 1 | Flujo OIDC real (`authorize` → `token` → `userinfo`) con PKCE | Implementado, sin probar contra el IdP real |
| 2 | Ruta `GET /auth/claveunica/logout` (aterrizaje del logout federado) | Implementado |
| 3 | Redirección al endpoint de logout de ClaveÚnica al cerrar sesión | Implementado (solo en `CLAVEUNICA_MODE=live`) |
| 4 | Emisión de TLS para `pruebas.participa.gobiernovalparaiso.cl` | Bloqueado por el DNS (3.2) |
| 5 | Cargar `CLAVEUNICA_CLIENT_ID` / `CLAVEUNICA_CLIENT_SECRET` y pasar `CLAVEUNICA_MODE=live` | Bloqueado por la entrega de credenciales |

Mientras no lleguen las credenciales, la plataforma opera con ClaveÚnica desactivada
(`CLAVEUNICA_ENABLED=false`) y las observaciones ciudadanas se reciben en modo invitado,
tal como está hoy en producción.

---

## 5. Datos adicionales que suele pedir el formulario

Por si el trámite requiere más campos que los dos bloques de URI:

- **Institución:** Gobierno Regional de Valparaíso
- **Nombre del sistema:** Plataforma de Consultas Públicas / Participación Ciudadana
- **URL del sistema:** https://www.participa.gobiernovalparaiso.cl
- **Tipo de integración:** OIDC Authorization Code + PKCE
- **Scopes:** `openid`, `run`, `name`
- **Contacto técnico:** (completar con el nombre, correo y teléfono del responsable técnico
  designado por AWNA)
- **Contacto administrativo / contraparte institucional:** (lo define el GORE)
