# Documentación Técnica

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**
Versión 1.0 · 5 de agosto de 2026 · AWNA

Este directorio contiene la documentación técnica de entrega del proyecto.
Cada documento existe en Markdown (fuente versionada) y en Word (`.docx`,
generado a partir del mismo Markdown).

| Documento | Para quién | Contenido |
|---|---|---|
| [01 — Manual de Despliegue y Operación](01-manual-despliegue-operacion.md) | Equipo técnico / Unidad de Informática | Ambientes, provisionamiento, variables de entorno, procedimiento de despliegue, respaldos, monitoreo, runbook de incidencias, rollback y estado de hardening |
| [02 — Manual del Administrador](02-manual-administrador.md) | Funcionarios del GORE | Uso del backoffice: consultas, antecedentes, observaciones, respuestas institucionales, exportación, usuarios y bitácora |
| [03 — Diccionario de Datos y Mapa de Rutas](03-diccionario-datos-y-rutas.md) | Equipo técnico | Modelo de entidades, todas las tablas y columnas, reglas de integridad, historial de migraciones, las 61 rutas, middleware, CSP y comandos |

## Documentación complementaria en el repositorio

| Ruta | Contenido |
|---|---|
| `README.md` (raíz) | Visión general del producto, stack, instalación local y estructura del proyecto |
| `docs/etapa-1/` | Entregables formales de la Etapa 1: plan de trabajo, arquitectura AWS y carta Gantt |
| `docs/etapa-5/qa-report.md` | Informe de aseguramiento de calidad |
| `docs/staging/gore-prod-nginx.conf` | Configuración de nginx de producción (fuente de verdad, con TLS) |
| `docs/staging/iam-provisioning-request.md` | Solicitud de acceso para aprovisionar el ambiente de staging |
| `docs/preprod/server-config.md` | Configuración de servidor del ambiente de preproducción (histórico; ese ambiente fue desmantelado) |

## Regenerar los archivos .docx

Los `.docx` se generan desde el Markdown con el script incluido:

```bash
node docs/tecnica/_build/md-to-docx.js
```

Requiere haber instalado las dependencias una vez:

```bash
npm --prefix docs/tecnica/_build install
```

El directorio `_build/node_modules` no se versiona.
