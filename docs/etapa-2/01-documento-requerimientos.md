# Documento de Requerimientos

**Proyecto:** Plataforma de Procesos Participativos Reglados
**Mandante:** Gobierno Regional de Valparaíso — Unidad de Ordenamiento Territorial
**Proveedor:** AWNA
**Entregable:** Etapa 2 — Diseño UX/UI
**Versión:** 1.1 · 12 de agosto de 2026

---

## 1. Objeto del documento

Este documento consolida los requerimientos funcionales y no funcionales de la
plataforma de consultas públicas del GORE Valparaíso, y deja constancia del
estado de implementación de cada uno al cierre del desarrollo.

Cumple dos propósitos. Como **especificación**, fija qué debe hacer el sistema y
bajo qué reglas. Como **registro de trazabilidad**, permite a la contraparte
técnica verificar requisito por requisito qué quedó construido, qué cambió
respecto de lo especificado originalmente y por qué, y qué permanece pendiente
junto con su causa.

Se redacta sobre el estado real del sistema desplegado, no sobre una
especificación teórica. Cada requisito implementado es verificable en el código
fuente y en la plataforma en producción.

### 1.1 Fuentes

| Fuente | Aporte |
|---|---|
| Especificaciones Técnicas de Referencia (EETT) del GORE | Stack obligatorio, etapas, entregables y normativa aplicable |
| Brief Técnico de Proyecto (12 de mayo de 2026) | Módulos funcionales, arquitectura propuesta, roles y puntos críticos de kick-off |
| Acta de Observaciones del GORE (junio de 2026) | Ajustes funcionales solicitados por la contraparte sobre la entrega parcial |
| Observaciones de la contraparte (2 y 3 de julio de 2026) | Correcciones de identidad de participantes y de comportamiento de fechas |
| Código fuente y ambiente productivo | Estado efectivo de implementación |

### 1.2 Convenciones

Los requisitos se identifican como `RF-nn` (funcionales) y `RNF-nn` (no
funcionales). La columna **Estado** usa cuatro valores:

| Estado | Significado |
|---|---|
| **Implementado** | Construido, probado y operativo en producción |
| **Implementado con cambio** | Construido, con una variación respecto de la especificación original. La variación se detalla en la sección 9 |
| **Bloqueado** | Construido en la aplicación, pero inoperante hasta que se resuelva una dependencia externa (sección 11) |
| **No implementado** | Fuera del alcance efectivamente ejecutado. Se detalla en la sección 10 |

---

## 2. Alcance del sistema

La plataforma gestiona el ciclo completo de una consulta pública sobre
instrumentos de ordenamiento territorial: publicación del proceso y sus
antecedentes técnicos, recepción de observaciones ciudadanas con identidad
verificable, y respuesta institucional a cada observación.

**Dentro del alcance:** portal público de consulta y participación, backoffice
de administración, identificación por ClaveÚnica, participación sin cuenta con
identidad obligatoria, gestión documental versionada, exportación de la base de
observaciones, respuestas institucionales, auditoría y respaldos.

**Fuera del alcance:** integración con sistemas de expedientes del GORE, firma
electrónica avanzada, georreferenciación interactiva de observaciones, y
notificaciones por canales distintos del correo electrónico.

---

## 3. Actores del sistema

| Actor | Descripción | Forma de acceso |
|---|---|---|
| **Ciudadano** | Persona natural que consulta procesos y presenta observaciones | Público; se identifica al participar |
| **Persona Jurídica** | Empresa o entidad con RUT que presenta observaciones a través de un representante | Participación sin cuenta, con datos de la entidad |
| **Organización sin personalidad jurídica** | Junta de vecinos, agrupación u organización comunitaria sin RUT propio | Participación sin cuenta, con datos de la organización |
| **Funcionario GORE** | Profesional de la Unidad de Ordenamiento Territorial que administra procesos y responde observaciones | Credenciales institucionales en `/admin` |
| **Super-administrador** | Perfil con todas las capacidades del funcionario, más gestión de usuarios, bitácora de auditoría y papelera | Credenciales institucionales en `/admin` |

La separación entre acceso ciudadano y acceso de personal es estricta: el
backoffice rechaza a los ciudadanos y el portal ciudadano no expone acceso al
backoffice.

---

## 4. Requisitos funcionales — Portal Ciudadano

| ID | Requisito | Estado |
|---|---|---|
| RF-01 | El portal muestra el listado de procesos de consulta pública, distinguiendo los vigentes de los finalizados | Implementado |
| RF-02 | El listado permite filtrar por tipo de instrumento (IPT, PROT, ZUBC, Otro), por estado y por búsqueda de texto libre | Implementado |
| RF-03 | La portada destaca los procesos más recientes que están publicados o activos dentro de su ventana de fechas, y excluye los vencidos | Implementado |
| RF-04 | La portada resume el estado de cada proceso destacado: plazo restante y número de observaciones recibidas | Implementado |
| RF-05 | Cada proceso tiene una ficha con título, tipo de instrumento en su denominación completa, resumen, descripción, fechas y estado | Implementado |
| RF-06 | La ficha muestra el plazo restante en días, con leyenda diferenciada cuando el proceso finaliza el mismo día | Implementado |
| RF-07 | La ficha lista los antecedentes técnicos del proceso y permite descargarlos | Implementado |
| RF-08 | La ficha publica las respuestas institucionales emitidas sobre las observaciones del proceso | Implementado |
| RF-09 | La ficha muestra el mapa del instrumento consultado | No implementado |
| RF-10 | Las URLs públicas de procesos y observaciones no exponen identificadores incrementales de base de datos | Implementado |
| RF-11 | El portal es navegable en dispositivos móviles, presentando los antecedentes del proceso antes del formulario de participación | Implementado |

---

## 5. Requisitos funcionales — Identificación y participación

| ID | Requisito | Estado |
|---|---|---|
| RF-12 | El ciudadano puede identificarse mediante ClaveÚnica, usando el protocolo OpenID Connect con flujo Authorization Code y PKCE | Bloqueado |
| RF-13 | Los datos solicitados a ClaveÚnica se limitan a los scopes mínimos `openid`, `run` y `name` | Bloqueado |
| RF-14 | El RUN entregado por ClaveÚnica es el identificador único del ciudadano en el sistema | Bloqueado |
| RF-15 | El cierre de sesión de un ciudadano identificado por ClaveÚnica cierra también la sesión en el proveedor de identidad | Bloqueado |
| RF-16 | El ciudadano puede participar sin crear una cuenta, entregando obligatoriamente sus datos de identidad | Implementado con cambio |
| RF-17 | La participación admite tres tipos de participante: persona natural, persona jurídica y organización sin personalidad jurídica | Implementado |
| RF-18 | La persona natural se identifica con RUT validado por dígito verificador, o con pasaporte para participantes extranjeros | Implementado |
| RF-19 | La persona jurídica declara razón social, nombre de fantasía y RUT de la entidad | Implementado |
| RF-20 | Ninguna observación puede ser anónima: toda participación queda asociada a una identidad con correo electrónico obligatorio | Implementado |
| RF-21 | El backoffice define, por cada proceso, qué métodos de participación admite | Implementado |
| RF-22 | Una persona jurídica u organización sin personalidad jurídica nunca se asocia a una cuenta de usuario, dado que ClaveÚnica identifica únicamente personas naturales | Implementado |
| RF-23 | No existe límite de observaciones por participante en un mismo proceso | Implementado |

---

## 6. Requisitos funcionales — Observaciones

| ID | Requisito | Estado |
|---|---|---|
| RF-24 | El participante presenta observaciones sobre un proceso abierto, indicando asunto, categoría temática y cuerpo | Implementado |
| RF-25 | Las categorías temáticas disponibles son: uso de suelo, vialidad, áreas verdes, patrimonio, equipamiento, riesgo natural y otro | Implementado |
| RF-26 | Un mismo envío puede contener varias observaciones de distintas categorías, agrupadas bajo un identificador de envío común | Implementado |
| RF-27 | Cada observación admite un archivo adjunto, con validación de tipo y tamaño | Implementado |
| RF-28 | Al registrarse la observación, el sistema captura un snapshot inalterable de la identidad del participante, junto con fecha y hora, dirección IP, agente de usuario y método de identificación empleado | Implementado |
| RF-29 | El snapshot es independiente de la ficha del usuario: si el participante modifica sus datos posteriormente, la observación conserva los datos vigentes al momento del envío | Implementado |
| RF-30 | El contenido de una observación no puede modificarse después de registrado | Implementado |
| RF-31 | Tras el envío, el sistema entrega confirmación visual con el identificador de la observación | Implementado |
| RF-32 | Tras el envío, el sistema envía confirmación por correo electrónico al participante | Bloqueado |
| RF-33 | La página de confirmación es accesible por el autor de la observación y, en la participación sin cuenta, mediante el identificador reservado incluido en la URL | Implementado |
| RF-34 | El sistema aplica límite de frecuencia al envío de observaciones para prevenir saturación | Implementado |
| RF-35 | Un proceso fuera de su ventana de fechas no acepta observaciones, aunque su cierre administrativo esté pendiente | Implementado |

---

## 7. Requisitos funcionales — Backoffice

### 7.1 Gestión de procesos

| ID | Requisito | Estado |
|---|---|---|
| RF-36 | El funcionario crea, edita, publica, cierra y archiva procesos de consulta | Implementado |
| RF-37 | Cada proceso define tipo de instrumento, fechas de inicio y término, estado y métodos de participación admitidos | Implementado |
| RF-38 | Los estados posibles de un proceso son: borrador, publicado, activo, cerrado y archivado | Implementado |
| RF-39 | El archivado de un proceso es reversible: los procesos archivados se consultan en una papelera y pueden restaurarse | Implementado |
| RF-40 | Un proceso podía dividirse en etapas configurables de participación | Implementado con cambio |

### 7.2 Antecedentes técnicos

| ID | Requisito | Estado |
|---|---|---|
| RF-41 | El funcionario carga antecedentes técnicos asociados a cada proceso | Implementado |
| RF-42 | Los antecedentes se versionan: al reemplazar un archivo, la versión vigente se archiva y el histórico se conserva | Implementado |
| RF-43 | Las descargas se sirven por streaming desde la aplicación, verificando sesión y rol, sin exponer URLs públicas del almacenamiento | Implementado |
| RF-44 | El almacenamiento de antecedentes aplica bloqueo de objetos que impida su eliminación física | No implementado |

### 7.3 Observaciones y respuestas

| ID | Requisito | Estado |
|---|---|---|
| RF-45 | El funcionario consulta las observaciones recibidas con filtros, búsqueda y paginación | Implementado |
| RF-46 | El funcionario accede al detalle de cada observación, incluyendo la identidad registrada y el adjunto | Implementado |
| RF-47 | El funcionario exporta la base completa de observaciones en formato CSV y Excel, incluyendo las columnas de adjunto | Implementado |
| RF-48 | El funcionario emite una respuesta institucional sobre una observación individual | Implementado |
| RF-49 | El funcionario emite una respuesta institucional sobre un lote de observaciones, quedando todas asociadas al mismo identificador de lote | Implementado |
| RF-50 | Una respuesta institucional publicada no admite modificación posterior | Implementado |
| RF-51 | La emisión de una respuesta notifica por correo al participante | Bloqueado |
| RF-52 | El super-administrador archiva observaciones para retirar del listado duplicados, pruebas o contenido inadecuado, de forma reversible y sin destruir el registro | Implementado |

### 7.4 Usuarios, auditoría y respaldo

| ID | Requisito | Estado |
|---|---|---|
| RF-53 | El super-administrador crea, edita y desactiva usuarios funcionarios | Implementado |
| RF-54 | El sistema distingue tres roles con capacidades diferenciadas: ciudadano, funcionario y super-administrador | Implementado |
| RF-55 | El sistema mantiene una bitácora de auditoría inmutable que registra quién creó, modificó o cerró cada entidad relevante | Implementado |
| RF-56 | La bitácora excluye de su registro los campos sensibles: contraseñas, RUT, correo y dirección IP | Implementado |
| RF-57 | La bitácora de auditoría es accesible únicamente para el super-administrador | Implementado |
| RF-58 | El sistema genera respaldos automáticos de las observaciones cada 48 horas durante los procesos activos | Implementado |
| RF-59 | El backoffice presenta un panel con métricas de observaciones: total, observaciones por proceso y observaciones por día | Implementado |

---

## 8. Requisitos no funcionales

### 8.1 Disponibilidad y rendimiento

| ID | Requisito | Estado |
|---|---|---|
| RNF-01 | Disponibilidad mínima del 99% del aplicativo durante los períodos de consulta | Implementado |
| RNF-02 | El sistema soporta varios miles de observaciones durante un período de consulta sin degradación | Implementado |
| RNF-03 | El sistema expone un endpoint de verificación de salud que valida conectividad a base de datos y almacenamiento | Implementado |
| RNF-04 | La base de datos cuenta con réplica en zona de disponibilidad alternativa | No implementado |
| RNF-05 | La capa web admite escalado horizontal tras un balanceador de carga | No implementado |

**Alcance de la verificación.** RNF-01 y RNF-02 están respaldados por el
dimensionamiento de la infraestructura y por la ausencia de operaciones costosas
en los flujos de alta frecuencia, no por una medición sobre el ambiente
productivo. Los scripts de prueba de carga están construidos y versionados en el
repositorio (`tests/k6/`), con umbrales definidos para lectura pública y
backoffice autenticado. Su ejecución contra el ambiente productivo, con registro
de percentiles reales, queda disponible como actividad de verificación a
solicitud de la contraparte técnica. Se recomienda ejecutarla antes del primer
proceso de consulta con afluencia masiva.

### 8.2 Seguridad

| ID | Requisito | Estado |
|---|---|---|
| RNF-06 | Todo el tráfico se sirve sobre HTTPS, con redirección forzada desde HTTP y certificados vigentes | Implementado |
| RNF-07 | El sistema aplica la cabecera HSTS en producción | Implementado |
| RNF-08 | El sistema aplica una política de seguridad de contenido estricta, sin permitir la ejecución de scripts en línea | Implementado |
| RNF-09 | El sistema aplica cabeceras contra clickjacking, MIME-sniffing y fuga de origen, y bloquea APIs sensibles del navegador | Implementado |
| RNF-10 | Todos los formularios están protegidos contra falsificación de petición entre sitios | Implementado |
| RNF-11 | Las contraseñas se almacenan con función de hash adaptativa | Implementado |
| RNF-12 | Las entradas se validan y sanean en el servidor, sin confiar en la validación del navegador | Implementado |
| RNF-13 | Los endpoints públicos sensibles aplican límite de frecuencia por IP | Implementado |
| RNF-14 | Las cookies de sesión se emiten con marca de seguridad y política de mismo sitio | Implementado |
| RNF-15 | El almacenamiento de archivos no tiene acceso público y aplica cifrado en reposo | Implementado |
| RNF-16 | El acceso administrativo a los servidores no expone SSH a Internet | Implementado |
| RNF-17 | Las credenciales de acceso al almacenamiento no residen en archivos de configuración de la aplicación | Implementado |
| RNF-18 | El sistema cumple los estándares del D.S. N°7/2023 y la normativa aplicable: leyes 19.175, 21.078, 21.180 y Decreto 237 | Implementado |

### 8.3 Usabilidad y accesibilidad

| ID | Requisito | Estado |
|---|---|---|
| RNF-19 | La interfaz aplica las normas gráficas institucionales del GORE y los criterios del Kit Digital del Estado | Implementado |
| RNF-20 | La interfaz aplica los criterios de accesibilidad WCAG 2.1 nivel AA | Implementado |
| RNF-21 | La interfaz es responsive y opera en resoluciones de escritorio, tableta y teléfono | Implementado |
| RNF-22 | La redacción de la interfaz usa lenguaje claro, evitando siglas técnicas sin explicación | Implementado |
| RNF-23 | El backoffice es operable por funcionarios sin asistencia del proveedor | Implementado |
| RNF-24 | La duración de la sesión permite completar el envío de una observación con adjunto sin expiración prematura | Implementado |

**Alcance de la verificación.** RNF-20 refleja la aplicación sistemática de los
criterios de accesibilidad durante el diseño y la construcción: estructura
semántica, contraste, textos alternativos, navegación por teclado, etiquetado de
formularios y mensajes de error asociados a su campo. No se ejecutó una auditoría
formal de conformidad por un tercero certificador. Si el GORE la requiere como
parte de la recepción, es una actividad acotada que puede realizarse sobre la
plataforma ya desplegada.

### 8.4 Mantenibilidad y transferencia

| ID | Requisito | Estado |
|---|---|---|
| RNF-25 | El código fuente se entrega completo, estructurado y versionado en un repositorio Git transferible | Implementado |
| RNF-26 | El modelo de datos se documenta con sus reglas de validación explícitas | Implementado |
| RNF-27 | El sistema cuenta con una suite de pruebas automatizadas del dominio | Implementado |
| RNF-28 | Se entrega documentación técnica de despliegue, operación y administración | Implementado |
| RNF-29 | La plataforma opera con soberanía tecnológica plena del GORE sobre código, datos e infraestructura | Implementado |

---

## 9. Cambios de alcance respecto de la especificación original

Los siguientes requisitos se implementaron con una variación respecto del brief
técnico del 12 de mayo de 2026. Todos fueron solicitados o validados por la
contraparte del GORE y quedan aquí registrados para su trazabilidad.

### 9.1 Sustitución del registro manual por participación sin cuenta (RF-16)

**Especificación original.** Autenticación dual: ClaveÚnica o registro manual
con RUT, nombres, apellidos y correo, creando una cuenta de usuario con
verificación por correo electrónico.

**Implementación.** La creación de cuentas ciudadanas se eliminó. Quien no use
ClaveÚnica participa directamente desde el formulario de observación, entregando
los mismos datos obligatorios de identidad.

**Origen.** Acta de Observaciones del GORE, junio de 2026, punto 2.

**Fundamento.** El registro con verificación por correo introducía un paso
intermedio que abandonaba una fracción relevante de los participantes antes de
enviar su observación. La exigencia de fondo del mandante —que ninguna
observación sea anónima— se mantiene íntegra: los datos de identidad siguen
siendo obligatorios y quedan registrados en el snapshot inalterable. Lo que se
eliminó es la cuenta de usuario, no la identificación.

**Consecuencia.** El participante sin ClaveÚnica no dispone de un historial
consolidado de sus observaciones previas. Accede a la confirmación de cada
observación mediante el identificador reservado que recibe en la URL de
confirmación.

### 9.2 Eliminación de las etapas configurables por proceso (RF-40)

**Especificación original.** Cada proceso podía dividirse en una, dos o tres
instancias de participación, con el modelo de datos soportando etapas
configurables.

**Implementación.** El concepto de etapa se retiró del sistema. La consulta es
la unidad de participación: un proceso, una ventana de fechas, un conjunto de
observaciones.

**Origen.** Decisión de diseño validada con la contraparte durante junio de 2026.

**Fundamento.** Ningún proceso de los definidos para el lanzamiento requería más
de una instancia de participación. La estructura de etapas duplicaba la
navegación del portal —el ciudadano debía elegir proceso y luego etapa— y
agregaba un nivel de configuración en el backoffice que los funcionarios debían
resolver en cada creación sin obtener contrapartida funcional. La necesidad de
fondo, que un mismo instrumento admita varias instancias de consulta, se resuelve
publicando procesos sucesivos.

**Consecuencia.** Un instrumento con más de una instancia de consulta se modela
como procesos independientes. Las observaciones no se agrupan automáticamente
entre esos procesos.

### 9.3 Ampliación de los tipos de participante (RF-17, RF-19)

**Especificación original.** El brief contemplaba únicamente personas naturales.

**Implementación.** El sistema distingue persona natural, persona jurídica y
organización sin personalidad jurídica, con campos de identidad propios de cada
tipo y su correspondiente reflejo en el backoffice y en la exportación.

**Origen.** Acta de Observaciones del GORE, junio de 2026, punto 3, y
observaciones de la contraparte del 2 de julio de 2026.

**Fundamento.** Una parte sustantiva de las observaciones sobre instrumentos de
ordenamiento territorial proviene de juntas de vecinos, agrupaciones
comunitarias y empresas, no de personas naturales actuando a título individual.
Registrarlas como personas naturales distorsionaba el análisis posterior de las
observaciones.

### 9.4 Observaciones múltiples por envío (RF-26)

**Especificación original.** Sin límite de observaciones por usuario, cada una
como registro independiente.

**Implementación.** Se conserva el registro independiente por observación y se
agrega la posibilidad de presentar varias observaciones de distintas categorías
en un mismo envío, agrupadas por un identificador de envío común.

**Fundamento.** Es habitual que un participante tenga observaciones sobre
materias distintas del mismo instrumento. Obligarlo a repetir el formulario
completo por cada una penalizaba la participación y fragmentaba la trazabilidad
de un mismo acto de participación.

---

## 10. Requisitos no implementados

Los siguientes requisitos figuraban en la especificación original y no forman
parte del alcance efectivamente ejecutado. Ninguno de ellos impide la operación
de un proceso de consulta pública completo.

| ID | Requisito | Situación |
|---|---|---|
| RF-09 | Visor de mapa del instrumento en la ficha pública | El modelo de datos contempla los campos de imagen y geometría del instrumento, y el backoffice puede almacenarlos. No se construyó el visor en el portal. Los antecedentes cartográficos se publican como documentos descargables |
| RF-44 | Bloqueo de objetos en el almacenamiento | El almacenamiento es privado y cifrado, y la inalterabilidad del expediente se garantiza a nivel de aplicación: el contenido de una observación no es modificable y la eliminación es lógica, nunca física. No se habilitó el bloqueo de objetos a nivel de infraestructura |
| RNF-04 | Réplica de base de datos en zona de disponibilidad alternativa | Requiere habilitar alta disponibilidad en el motor de base de datos administrado. Es una decisión de costo del mandante, no una restricción técnica |
| RNF-05 | Balanceador de carga y escalado horizontal | La arquitectura desplegada usa una instancia única dimensionada para la carga proyectada. El diseño de la aplicación no impide incorporar el balanceador cuando el volumen lo justifique |
| — | Interfaz de programación REST interna de interoperabilidad | El portal público consume los datos directamente desde la capa de aplicación. No se expuso una interfaz de programación separada |
| — | Sala de espera para picos de concurrencia | Mecanismo previsto como reutilización de un desarrollo previo. La carga proyectada no lo hizo necesario |

---

## 11. Dependencias del mandante

Los siguientes requisitos están construidos en la aplicación pero permanecen
inoperantes hasta que se resuelva una gestión externa al proveedor.

| Requisitos | Dependencia | Responsable |
|---|---|---|
| RF-12 a RF-15 | Credenciales de integración OpenID Connect de ClaveÚnica, emitidas por la Unidad de Gobierno Digital. Requiere definir el dominio de producción bajo `.gob.cl` y habilitar un subdominio para el ambiente de pruebas | GORE, con apoyo técnico de AWNA |
| RF-32, RF-51 | Habilitación del servicio de correo saliente: publicación de los registros de firma de dominio en el DNS institucional y aprobación de acceso productivo | GORE (DNS) y proveedor de nube |
| RNF-04 | Decisión de habilitar alta disponibilidad en la base de datos | GORE |

Mientras la integración con ClaveÚnica permanezca bloqueada, la plataforma opera
con la participación sin cuenta como único método, lo que preserva la exigencia
de identidad obligatoria y no interrumpe los procesos de consulta.

---

## 12. Trazabilidad

Correspondencia entre los grupos de requisitos y los componentes del sistema que
los implementan, para verificación por la contraparte técnica.

| Requisitos | Componente | Ubicación |
|---|---|---|
| RF-01 a RF-11 | Portal ciudadano | `app/Http/Controllers/Public/ConsultationController.php`, `resources/views/public/` |
| RF-12 a RF-15 | Integración ClaveÚnica | `app/Http/Controllers/Public/Auth/ClaveUnicaController.php`, `config/claveunica.php` |
| RF-16 a RF-23 | Participación e identidad | `app/Http/Requests/Public/StoreObservationRequest.php`, `app/Rules/Rut.php` |
| RF-24 a RF-35 | Observaciones | `app/Http/Controllers/Public/ObservationController.php`, `app/Models/Observation.php` |
| RF-36 a RF-40 | Gestión de procesos | `app/Http/Controllers/Admin/ConsultationController.php`, `app/Models/Consultation.php` |
| RF-41 a RF-44 | Antecedentes técnicos | `app/Http/Controllers/Admin/ConsultationDocumentController.php` |
| RF-45 a RF-52 | Observaciones y respuestas en backoffice | `app/Http/Controllers/Admin/ObservationController.php`, `app/Http/Controllers/Admin/InstitutionalResponseController.php`, `app/Exports/ObservationsExport.php` |
| RF-53 a RF-59 | Usuarios, auditoría y respaldo | `app/Http/Controllers/Admin/UserController.php`, `app/Http/Controllers/Admin/ActivityLogController.php`, `app/Console/Commands/BackupObservations.php` |
| RNF-06 a RNF-18 | Seguridad | `app/Http/Middleware/SecurityHeaders.php`, `app/Support/Csp/GoreCspPolicy.php`, `routes/web.php` |
| RNF-25 a RNF-28 | Documentación y pruebas | `docs/tecnica/`, `tests/` |

El detalle de tablas, columnas, reglas de integridad y el mapa completo de rutas
se encuentra en el documento *Diccionario de Datos y Mapa de Rutas*
(`docs/tecnica/03-diccionario-datos-y-rutas.md`).

---

## 13. Criterios de aceptación

Un requisito se considera aceptado cuando se cumplen las tres condiciones:

1. **Verificable en la plataforma productiva.** La funcionalidad es ejercitable
   por la contraparte en el ambiente de producción, con datos reales.
2. **Cubierto por la documentación de entrega.** Su operación está descrita en el
   Manual del Administrador o en el Manual de Despliegue y Operación, según
   corresponda.
3. **Respaldado por prueba automatizada**, en los requisitos que involucran
   reglas de negocio: validación de identidad, inalterabilidad de observaciones y
   respuestas, control de acceso por rol, comportamiento de fechas y cabeceras de
   seguridad.

Los requisitos marcados como **Bloqueado** se aceptan contra la verificación de
su comportamiento en el ambiente de pruebas una vez resuelta la dependencia
externa, sin requerir desarrollo adicional.
