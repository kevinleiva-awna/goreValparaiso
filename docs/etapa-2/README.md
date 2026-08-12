# Etapa 2 — Diseño UX/UI

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**
Versión 1.0 · 12 de agosto de 2026 · AWNA

Entregables formales de la Etapa 2 según las Especificaciones Técnicas de
Referencia: *Documento de arquitectura + Mockups aprobados + Documento de
requerimientos*.

## Correspondencia con las EETT

| Entregable exigido | Documento en esta carpeta |
|---|---|
| Documento de requerimientos | [01-documento-requerimientos.docx](01-documento-requerimientos.docx) |
| Documento de arquitectura | [02-documento-arquitectura-aws.docx](02-documento-arquitectura-aws.docx) |
| Mockups aprobados | [03-documento-diseno-ux-ui.docx](03-documento-diseno-ux-ui.docx) |

## Contenido

| Documento | Contenido |
|---|---|
| [01 — Documento de Requerimientos](01-documento-requerimientos.md) | Requisitos funcionales y no funcionales con su estado de implementación, actores, reglas de negocio, cambios de alcance respecto del brief original, dependencias del mandante y trazabilidad requisito → componente |
| 02 — Documento de Arquitectura AWS | Arquitectura de la solución sobre AWS: capas, componentes, dimensionamiento y decisiones de infraestructura |
| [03 — Documento de Diseño UX/UI y Maquetas](03-documento-diseno-ux-ui.md) | Criterios de diseño, sistema de diseño (paleta, tipografía, forma, movimiento), arquitectura de información, maquetas de todas las pantallas del portal y del backoffice, accesibilidad y registro de validación con la contraparte |

Las maquetas del documento 03 son capturas de la plataforma construida, no
bocetos. Se encuentran en [`img/`](img/).

## Formatos

Cada documento existe en Markdown (fuente versionada) y en Word (`.docx`,
generado a partir del mismo Markdown). El documento de arquitectura existe solo
en Word, por ser anterior a esta tubería de generación.

Regenerar los `.docx`:

```bash
node docs/tecnica/_build/md-to-docx.js
```

Requiere haber instalado las dependencias una vez:

```bash
npm --prefix docs/tecnica/_build install
```

## Nota sobre la ubicación del documento de arquitectura

El Documento de Arquitectura AWS se elaboró durante la Etapa 1 y quedó archivado
en `docs/etapa-1/`. Según las EETT, el entregable de la Etapa 1 es el Plan de
Trabajo Detallado más la Carta Gantt definitiva; el documento de arquitectura
corresponde a la Etapa 2. Se reubicó en esta carpeta para que la entrega refleje
la estructura contractual.
