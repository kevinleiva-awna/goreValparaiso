# Acta de Entrega

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**

| | |
|---|---|
| Versión | 1.0 |
| Fecha | 29 de septiembre de 2026 |
| Emisor | AWNA |
| Destinatario | División de Planificación y Desarrollo y Unidad de Informática, Gobierno Regional de Valparaíso |
| Objeto | Entrega consolidada de los productos de las Etapas 3, 4 y 5 para su recepción conforme |

---

## 1. Resumen

Este documento consolida en un solo lugar los productos comprometidos para las Etapas 3, 4 y 5. Para cada producto se indica qué se entrega, dónde encontrarlo y su estado.

La plataforma está **operativa en producción** en su dominio oficial, con certificado de seguridad vigente, y el ambiente de pruebas (QA) está disponible con los mismos módulos. Los puntos abiertos de la Etapa 5 dependen de gestiones externas y se detallan en la sección 5.

| Ambiente | Dirección | Estado |
|---|---|---|
| Producción | `https://www.participa.gobiernovalparaiso.cl` | Operativo |
| Pruebas (QA) | `https://pruebas.participa.gobiernovalparaiso.cl` | Operativo |
| Backoffice (en ambos) | `<dirección del ambiente>/admin/login` | Operativo |

---

## 2. Etapa 3 — Desarrollo del Core (Gestor y Backoffice)

**Producto comprometido:** acceso al ambiente de pruebas (QA) con módulos operativos.

| Módulo | Qué hace | Estado |
|---|---|---|
| Gestor de procesos | Crear, publicar, activar, cerrar y archivar consultas, con su ventana de fechas y sus métodos de participación | Entregado |
| Antecedentes técnicos | Carga de documentos con versionado y huella SHA-256 | Entregado |
| Gestión de observaciones | Listado, filtros, ficha completa y archivado controlado | Entregado |
| Respuestas institucionales | Borrador, publicación, notificación y respuesta en lote | Entregado |
| Exportación | Compendio en XLSX/CSV y adjuntos en ZIP, respetando los filtros | Entregado |
| Usuarios y roles | Funcionario y super-admin, con activación y desactivación | Entregado |
| Bitácora de auditoría | Registro inalterable de las acciones del sistema | Entregado |

**Evidencia:** ambiente QA en `https://pruebas.participa.gobiernovalparaiso.cl`, Manual del Administrador e Informe de QA (carpeta `Etapa-3`).

---

## 3. Etapa 4 — Integración y Módulos de Participación

**Producto comprometido:** versión Beta de la plataforma completa en el ambiente de pruebas (QA).

| Componente | Estado |
|---|---|
| Portal público: portada, listado de consultas, ficha del proceso, descarga de antecedentes, comprobante de envío y respuestas publicadas | Entregado |
| Participación sin registro para Persona Natural, Persona Jurídica y Organización sin PJ | Entregado |
| Integración con ClaveÚnica (OpenID Connect), probada de punta a punta en QA contra el proveedor oficial: ingreso, datos del ciudadano y cierre de sesión federado | Entregado |
| Correo obligatorio como regla de validación en toda participación | Entregado |
| Envíos con hasta 20 observaciones y adjunto por observación | Entregado |
| Adaptación a teléfono móvil | Entregado |

**Evidencia:** ambiente QA, Documento de Diseño UX/UI y documentación de ClaveÚnica (carpeta `Etapa-4`).

---

## 4. Etapa 5 — Puesta en Producción y Transferencia

**Productos comprometidos:** plataforma 100% operativa, certificados de seguridad vigentes, código fuente completo y manuales finales.

| Producto | Detalle | Ubicación en la entrega | Estado |
|---|---|---|---|
| Despliegue en AWS con dominio oficial | Cuenta AWS del GORE, región us-east-1. Servidor de aplicación, base de datos MariaDB administrada (RDS) con respaldo diario y 7 días de retención, y almacenamiento privado S3 cifrado | `Etapa-5/04-Infraestructura` | Entregado |
| Certificados de seguridad vigentes | TLS de Let's Encrypt para `participa.gobiernovalparaiso.cl` y `www.participa.gobiernovalparaiso.cl`, vigente hasta el 05-12-2026 con renovación automática. QA vigente hasta el 25-11-2026 | `Etapa-5/03-Certificados` | Entregado |
| Código fuente completo | Código de la versión en producción, con la configuración de cada ambiente y las instrucciones de instalación | `Etapa-5/01-Codigo-fuente` | Entregado |
| Manuales finales | Manual de Usuario, Manual del Administrador, Manual de Despliegue y Operación, y Diccionario de Datos y Rutas | `Etapa-5/02-Manuales` | Entregado |
| Capacitación a usuarios | Sesión para los funcionarios que operarán el backoffice | — | Por coordinar |

---

## 5. Puntos abiertos y responsables

Ninguno de estos puntos impide el funcionamiento actual de la plataforma.

| # | Punto | Qué falta | Depende de |
|---|---|---|---|
| 1 | Envío de correos (Amazon SES) | Dos pasos: (a) publicar en el DNS del dominio los 3 registros DKIM que AWNA entregará actualizados, y (b) que Amazon apruebe el envío de correos de la cuenta, solicitud que AWNA está gestionando. Con ambos, AWNA activa el envío. Mientras tanto, las respuestas institucionales se publican en el portal y la notificación queda registrada en el servidor | Informática GORE (DNS) y AWNA (solicitud a Amazon) |
| 2 | ClaveÚnica en producción | La integración está activada en producción con las credenciales de producción. Queda confirmar con la Secretaría de Gobierno Digital que la certificación de la institución está aprobada, porque de ella depende que el ingreso de ciudadanos reales funcione | GORE / Gobierno Digital |
| 3 | Capacitación | Acordar fecha y participantes | GORE y AWNA |
| 4 | Mejoras de infraestructura opcionales | Base de datos en alta disponibilidad (Multi-AZ), alarmas de monitoreo y firewall de aplicación. Están descritas en el Manual de Despliegue | Decisión del GORE |
| 5 | Credenciales de acceso | Rotar las claves de acceso a AWS del usuario de AWNA al cierre de la transferencia y limitar sus permisos | Informática GORE y AWNA |

---

## 6. Contenido de la entrega

```
Entrega-Final-GORE-Valparaiso/
├── 00-Acta-de-Entrega.docx / .pdf
├── Etapa-3-Core-Gestor-Backoffice/
├── Etapa-4-Portal-y-Participacion/
├── Etapa-5-Produccion-y-Transferencia/
│   ├── 01-Codigo-fuente/
│   ├── 02-Manuales/
│   ├── 03-Certificados/
│   └── 04-Infraestructura/
└── Anexos-Etapas-1-y-2/
```

El código y la configuración de los ambientes contienen credenciales. Deben guardarse en un lugar de acceso restringido y no reenviarse por correo.

---

## 7. Recepción conforme

| | Nombre | Cargo | Firma | Fecha |
|---|---|---|---|---|
| Entrega (AWNA) | | | | |
| Recibe (DIPLAD) | | | | |
| Recibe (Informática) | | | | |
