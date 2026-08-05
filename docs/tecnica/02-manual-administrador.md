# Manual del Administrador

**Plataforma de Procesos Participativos Reglados — GORE Valparaíso**

| | |
|---|---|
| Versión | 1.0 |
| Fecha | 5 de agosto de 2026 |
| Autor | AWNA |
| Destinatario | Funcionarios y administradores del Gobierno Regional de Valparaíso |
| Documentos relacionados | `01-manual-despliegue-operacion.md`, `03-diccionario-datos-y-rutas.md` |

---

## Tabla de contenidos

1. [Qué hace la plataforma](#1-qué-hace-la-plataforma)
2. [Acceso al backoffice](#2-acceso-al-backoffice)
3. [Roles y permisos](#3-roles-y-permisos)
4. [Gestión de consultas](#4-gestión-de-consultas)
5. [Antecedentes técnicos](#5-antecedentes-técnicos)
6. [Observaciones ciudadanas](#6-observaciones-ciudadanas)
7. [Respuestas institucionales](#7-respuestas-institucionales)
8. [Exportación de datos](#8-exportación-de-datos)
9. [Gestión de funcionarios](#9-gestión-de-funcionarios)
10. [Bitácora de auditoría](#10-bitácora-de-auditoría)
11. [Qué ve el ciudadano](#11-qué-ve-el-ciudadano)
12. [Preguntas frecuentes](#12-preguntas-frecuentes)

---

## 1. Qué hace la plataforma

La plataforma permite al Gobierno Regional publicar procesos de consulta
pública sobre instrumentos de ordenamiento territorial, recibir observaciones
formales de la ciudadanía y responderlas institucionalmente, dejando registro
trazable de todo el expediente.

El sistema tiene dos caras:

- **Portal ciudadano** (público, sin necesidad de cuenta para navegar):
  listado de procesos, ficha de cada proceso, descarga de antecedentes y
  formulario de observaciones.
- **Backoffice** (`/admin`, requiere credenciales): gestión completa de
  procesos, documentos, observaciones, respuestas, usuarios y auditoría.

Principio de diseño que conviene tener presente: **las observaciones
ciudadanas son inalterables**. Nadie —ni un super-admin— puede editar el
contenido de una observación recibida. Lo único reversible es archivarla, y
esa acción queda registrada.

---

## 2. Acceso al backoffice

**URL de ingreso:** `https://www.participa.gobiernovalparaiso.cl/admin/login`

Al ingresar se llega al panel (`/admin/dashboard`). El menú lateral da acceso
a Consultas, Observaciones, Funcionarios y Bitácora, según el rol.

Recomendaciones de uso:

- Cambiar la contraseña inicial en el primer ingreso, desde **Perfil**.
- Cada funcionario debe tener su propia cuenta. Compartir credenciales rompe
  la trazabilidad: la bitácora atribuye cada acción a un usuario concreto.
- Si un funcionario deja de participar del proceso, **desactivar** su cuenta
  en lugar de eliminarla, para conservar el historial.

Si se olvida la contraseña, existe recuperación por correo
(`/admin/forgot-password`). Requiere que el envío de correo esté habilitado;
mientras SES no esté activo, un super-admin puede asignar una contraseña
nueva desde **Funcionarios → Editar**.

---

## 3. Roles y permisos

Hay tres roles. Los dos primeros acceden al backoffice; el tercero es el
ciudadano y nunca entra a `/admin`.

| Acción | Funcionario | Super-admin |
|---|:---:|:---:|
| Ver el panel | ✔ | ✔ |
| Crear, editar y publicar consultas | ✔ | ✔ |
| Archivar y restaurar consultas | ✔ | ✔ |
| Subir, reemplazar y archivar antecedentes | ✔ | ✔ |
| Ver observaciones y sus adjuntos | ✔ | ✔ |
| Exportar observaciones (XLSX / CSV) | ✔ | ✔ |
| Redactar, publicar y responder en lote | ✔ | ✔ |
| **Archivar y restaurar observaciones** | ✖ | ✔ |
| **Crear y editar funcionarios** | ✖ | ✔ |
| **Ver la bitácora de auditoría** | ✖ | ✔ |

Las tres acciones restringidas al super-admin son las que tocan la integridad
del expediente o el control de acceso. Un funcionario que intente acceder a
ellas recibe un error 403.

**Los ciudadanos no se administran desde el backoffice.** Se identifican por
ClaveÚnica o participan sin registro; no existe una pantalla para crearlos o
editarlos, por diseño.

---

## 4. Gestión de consultas

Una **consulta** (o proceso participativo) es la unidad central del sistema:
agrupa los antecedentes técnicos, define la ventana de participación y recibe
las observaciones.

### 4.1 Crear una consulta

**Consultas → Nueva consulta.** Campos del formulario:

| Campo | Obligatorio | Descripción |
|---|:---:|---|
| Título | Sí | Nombre del proceso, tal como lo verá la ciudadanía (máx. 255 caracteres) |
| Slug | No | Identificador en la URL pública. Si se deja vacío se genera desde el título con un sufijo aleatorio para garantizar unicidad. Solo letras, números, guiones y guion bajo |
| Resumen | No | Bajada breve que aparece en las tarjetas del listado (máx. 1.000 caracteres) |
| Descripción | No | Texto completo de la ficha del proceso |
| Tipo de instrumento | Sí | IPT, PROT, ZUBC u Otro |
| Estado | Sí | Ver §4.2 |
| Fecha de inicio | No | Comienzo de la ventana de participación |
| Fecha de término | No | Cierre de la ventana. Debe ser igual o posterior a la de inicio |
| Métodos de participación | Sí (al menos uno) | ClaveÚnica y/o Sin registro. Ver §4.3 |

Los cuatro tipos de instrumento se muestran al ciudadano con su nombre
completo, no con la sigla:

| Sigla | Nombre mostrado al ciudadano |
|---|---|
| IPT | Instrumento de Planificación Territorial |
| PROT | Plan Regional de Ordenamiento Territorial |
| ZUBC | Zonificación de Uso del Borde Costero |
| OTRO | Otro instrumento |

Una consulta nueva se propone por defecto en estado **Borrador** y con ambos
métodos de participación habilitados.

### 4.2 Estados y ciclo de vida

| Estado | Visible al ciudadano | Recibe observaciones |
|---|:---:|:---:|
| **Borrador** | No | No |
| **Publicada** | Sí | No — se anuncia como próxima |
| **Activa** | Sí | Sí, si la fecha actual está dentro de la ventana |
| **Cerrada** | Sí, como proceso concluido | No |
| **Archivada** | No | No |

Ciclo habitual:

```
Borrador ──► Publicada ──► Activa ──► Cerrada
   │            (se anuncia)  (recibe      (queda como
   │                          observaciones) expediente)
   └──────────────────────────────────────► Archivada
```

**La fecha manda sobre el estado.** Aunque una consulta quede en *Activa* por
un cierre manual pendiente, si su fecha de término ya pasó el portal la
muestra como cerrada y rechaza nuevos envíos. De la misma forma, una consulta
*Activa* cuya fecha de inicio aún no llega se presenta como próxima. Esto
evita que aparezcan procesos "en curso" con cero días restantes.

En consecuencia: **basta con configurar bien las fechas**; no es necesario
entrar a cambiar el estado el mismo día del cierre.

### 4.3 Métodos de participación

Cada consulta define quién puede enviar observaciones:

- **ClaveÚnica** — el ciudadano se identifica con el sistema oficial del
  Estado. Solo identifica personas naturales chilenas.
- **Sin registro** — el ciudadano se identifica declarando sus datos en el
  propio formulario, sin crear cuenta. Es la vía por la que participan
  personas jurídicas y organizaciones sin personalidad jurídica.

Se puede habilitar uno, otro o ambos. Al menos uno es obligatorio.

> **Estado actual:** ClaveÚnica está **desactivada a nivel de sistema**
> mientras el GORE no complete el registro del cliente OIDC ante la Unidad de
> Gobierno Digital. Con la integración apagada, una consulta configurada
> **solo** con ClaveÚnica muestra el mensaje "participación no disponible por
> ahora". Mientras dure esa situación, habilitar **Sin registro** en las
> consultas que deban recibir observaciones. La configuración por consulta se
> conserva intacta y volverá a operar cuando se active la integración.

### 4.4 Archivar y restaurar consultas

**Archivar** una consulta la retira del listado público y del backoffice sin
destruir nada: se conservan sus antecedentes, sus observaciones y sus
respuestas. Es la acción correcta para procesos de prueba o duplicados.

Para recuperarla: **Consultas → filtro "Archivadas" → Restaurar**.

Las observaciones de una consulta archivada siguen siendo consultables desde
el listado de observaciones, y su ficha sigue mostrando a qué proceso
pertenecen.

### 4.5 Buscar y filtrar consultas

El listado permite filtrar por estado, por tipo de instrumento y buscar por
título o slug, además de alternar entre activas y archivadas. Muestra el
número de observaciones recibidas por cada proceso.

---

## 5. Antecedentes técnicos

Son los documentos que respaldan el proceso: memorias explicativas,
ordenanzas, planos, informes ambientales, cartografía.

Se administran desde la **ficha de la consulta**, sección Antecedentes.

### 5.1 Subir un documento

Se indica un **título** (el nombre con que lo verá la ciudadanía), una
**descripción** opcional y el archivo. Al subirlo, el sistema registra
automáticamente el nombre original, el tipo MIME, el tamaño, el usuario que lo
subió y un **hash SHA-256** que permite verificar más adelante que el archivo
no fue alterado.

El tope de tamaño en servidor es de 110 MB por archivo.

### 5.2 Reemplazo versionado

Al reemplazar un antecedente, el sistema **no sobrescribe**: archiva la
versión vigente y crea una versión nueva con número incremental, ambas
asociadas al mismo documento lógico. Así queda reconstruible el historial
completo de qué versión estuvo publicada y cuándo.

La descarga pública siempre entrega la versión vigente.

### 5.3 Descarga y seguridad

Los archivos **no son públicos en el almacenamiento**. Toda descarga pasa por
la aplicación, que verifica el contexto antes de entregar el archivo. Esto
significa que no existen enlaces directos al bucket que puedan circular fuera
del portal.

### 5.4 Archivar un documento

Archivar un antecedente lo retira de la vista, pero **el archivo se conserva
en el almacenamiento**, conforme a la política de expedientes inalterables.

---

## 6. Observaciones ciudadanas

**Observaciones** en el menú lateral muestra todo lo recibido, ordenado de más
reciente a más antigua.

### 6.1 Qué se registra por cada observación

Además del texto, cada observación guarda una **fotografía de la identidad**
del participante al momento del envío, que ya no cambia aunque después se
modifique cualquier otro dato:

- Código público único (UUID) e identificador interno
- Fecha y hora de envío
- Proceso al que corresponde
- Tema, asunto y cuerpo
- Método de identificación usado (ClaveÚnica o sin registro)
- Tipo de participante y sus datos declarados
- Archivo adjunto, si lo hubo
- Dirección IP y navegador de origen

### 6.2 Tipos de participante

| Tipo | Cómo participa | Datos obligatorios | Datos opcionales |
|---|---|---|---|
| **Persona Natural** | ClaveÚnica o sin registro | Nombre, correo, tipo de identificación (RUT o pasaporte) y número | Teléfono, comuna, edad |
| **Persona Jurídica** | Solo sin registro | Razón social, correo, RUT de la entidad | Nombre de fantasía, teléfono, dirección |
| **Organización sin PJ** | Solo sin registro | Razón social, correo, RUT de la entidad | Nombre de fantasía, teléfono, dirección |

Las personas jurídicas y las organizaciones **nunca ingresan por ClaveÚnica**:
ese servicio solo identifica personas naturales. El sistema lo impide a nivel
de datos, no solo de formulario.

En el listado y en la ficha, la identidad de una persona jurídica se muestra
por su razón social y el RUT de la entidad; la de una persona natural, por su
nombre y su RUT o pasaporte.

### 6.3 Temas disponibles

El ciudadano clasifica cada observación con uno de estos temas: Uso de suelo,
Vialidad, Áreas verdes, Patrimonio, Equipamiento, Riesgo natural, Otro.

### 6.4 Envíos con varias observaciones

Un ciudadano puede enviar **hasta 20 observaciones en una sola participación**,
cada una con su propio tema, asunto, texto y adjunto. Todas quedan agrupadas
bajo un mismo identificador de envío, lo que permite ver juntas las
observaciones de una misma persona en un mismo trámite.

### 6.5 Filtros y búsqueda

| Filtro | Uso |
|---|---|
| Proceso | Aísla las observaciones de una consulta |
| Método de identificación | ClaveÚnica o sin registro |
| Desde / Hasta | Rango de fechas de envío |
| Búsqueda libre | Busca en asunto, cuerpo, RUT, nombre, razón social, nombre de fantasía, RUT de entidad, correo y código público |
| Archivadas | Muestra la papelera en lugar del listado normal |

Los filtros se conservan al paginar y **el archivo exportado respeta
exactamente los filtros aplicados en pantalla**.

### 6.6 Adjuntos ciudadanos

El ciudadano puede adjuntar un archivo por observación, de hasta **10 MB**, en
formatos PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX, ODT, ODS o TXT.

Desde la ficha de la observación se descarga con su nombre original. Igual que
los antecedentes, no es un archivo público: la descarga exige sesión de
funcionario.

### 6.7 Archivar observaciones (solo super-admin)

Sirve para retirar del listado y del export lo que claramente no corresponde
al proceso: spam, duplicados evidentes, envíos de prueba.

- Es **reversible**: se restaura desde el filtro "Archivadas".
- **No borra nada**: la observación permanece en la base de datos.
- **No permite editar el contenido**: archivar es lo único que se puede hacer
  sobre una observación recibida.
- Queda registrada en la bitácora, con el usuario que la ejecutó.

Criterio recomendado: ante la duda, **no archivar**. Una observación fuera de
tema sigue siendo una observación ciudadana legítima y forma parte del
expediente.

---

## 7. Respuestas institucionales

Cada observación admite **una** respuesta institucional.

### 7.1 Borrador y publicación

El flujo tiene dos momentos deliberadamente separados:

1. **Borrador** — se redacta y guarda sin que el ciudadano lo vea. Se puede
   editar cuantas veces sea necesario, o descartar.
2. **Publicada** — se hace visible y se notifica al ciudadano por correo, al
   mismo que declaró al enviar su observación.

**Una respuesta publicada es inmutable:** no se puede editar ni eliminar. Es
una decisión de diseño para dar certeza jurídica a lo comunicado. Revisar el
texto en borrador antes de publicar.

> **Estado actual:** mientras el envío de correo no esté habilitado (pendiente
> de verificación del dominio en Amazon SES), publicar una respuesta la marca
> como publicada y **registra** la notificación en el log del servidor en
> lugar de enviarla. La funcionalidad está completa; solo falta activar el
> transporte de correo. Ver el manual de despliegue, §15.2.

### 7.2 Respuesta en lote

Cuando muchas observaciones plantean lo mismo, se pueden responder todas con
un texto común:

1. Seleccionar las observaciones en el listado.
2. Usar la acción de respuesta en lote.
3. Redactar el texto una sola vez y confirmar.

Todas las respuestas del lote quedan vinculadas entre sí por un identificador
de lote común, de modo que después se puede saber que fueron parte de la misma
resolución. El formulario advierte cuáles de las observaciones seleccionadas
ya tenían respuesta, para no duplicar.

---

## 8. Exportación de datos

Desde **Observaciones → Exportar** se descarga el listado en **XLSX** o
**CSV**. El archivo se llama `observaciones-gore-<fecha>_<hora>.<formato>`.

El export **aplica los mismos filtros que estén activos en pantalla**: si se
filtró por un proceso y un rango de fechas, eso es lo que se exporta.

Columnas del archivo:

| # | Columna | Contenido |
|---|---|---|
| 1 | ID | Identificador interno |
| 2 | Código público | UUID de la observación |
| 3 | Fecha de envío | Formato `dd/mm/aaaa HH:mm` |
| 4 | Proceso (consulta) | Título del proceso |
| 5 | Slug del proceso | Identificador en la URL |
| 6 | Tipo de instrumento | IPT / PROT / ZUBC / OTRO |
| 7 | Asunto | Asunto declarado |
| 8 | Categoría | Tema |
| 9 | Cuerpo de la observación | Texto completo |
| 10 | Método de identificación | ClaveÚnica / Sin registro |
| 11 | Tipo de participante | Persona Natural / Persona Jurídica / Organización sin PJ |
| 12 | RUT (persona o entidad) | RUT o pasaporte de la persona, o RUT de la entidad |
| 13 | Nombre / Razón social | Nombre de la persona o razón social |
| 14 | Nombre de fantasía | Solo PJ y organizaciones |
| 15 | Correo | Correo declarado al enviar |
| 16 | Archivo adjunto | Nombre original del archivo |
| 17 | Descarga del adjunto | Enlace al backoffice (requiere sesión de funcionario) |
| 18 | IP de origen | Dirección desde la que se envió |
| 19 | Navegador | User-agent del navegador |

La exportación procesa los registros por bloques, de modo que funciona sin
problemas con volúmenes altos.

> El archivo contiene datos personales de los participantes. Tratarlo conforme
> a la normativa de protección de datos: no publicarlo íntegro ni distribuirlo
> por canales no institucionales.

---

## 9. Gestión de funcionarios

Solo el **super-admin** accede a **Funcionarios**.

### 9.1 Crear una cuenta

| Campo | Obligatorio | Validación |
|---|:---:|---|
| RUT | Sí | Se valida el dígito verificador y se normaliza el formato. No puede repetirse |
| Nombre | Sí | Máx. 100 caracteres |
| Apellido | Sí | Máx. 100 caracteres |
| Correo | Sí | Formato válido y único en el sistema |
| Teléfono | No | Máx. 20 caracteres |
| Contraseña | Sí | Mínimo 8 caracteres, con confirmación |
| Rol | Sí | Funcionario o Super-admin |
| Activo | No | Activo por defecto |

Las cuentas creadas desde aquí quedan **verificadas de inmediato**: se asume
que el super-admin ya validó la identidad de la persona y le entrega las
credenciales directamente.

### 9.2 Activar y desactivar

El interruptor **Activo/Inactivo** habilita o bloquea el ingreso sin borrar la
cuenta. Es la forma correcta de dar de baja a un funcionario: conserva la
trazabilidad de todo lo que hizo mientras estuvo a cargo.

### 9.3 Filtros

El listado permite filtrar por rol y por estado (activo / inactivo), y buscar
por nombre, apellido, correo o RUT. Muestra únicamente personal: los
ciudadanos no aparecen aquí.

---

## 10. Bitácora de auditoría

**Bitácora** (solo super-admin) registra automáticamente las acciones
relevantes sobre el sistema. Es de **solo lectura**: no se puede editar ni
borrar ninguna entrada.

### 10.1 Qué queda registrado

| Ámbito | Se registra |
|---|---|
| Consultas | Creación y cambios de título, slug, estado, tipo de instrumento, fechas y métodos de participación |
| Antecedentes | Creación y cambios de título, nombre de archivo, versión y hash |
| Observaciones | **Solo la creación.** El contenido nunca se modifica; el archivado también deja registro |
| Usuarios | Cambios de nombre, apellido, correo, rol y estado activo |

**Nunca se registran contraseñas ni datos sensibles.** Los cambios de
contraseña no se guardan en la bitácora.

### 10.2 Filtros

Por ámbito (consulta, documento, observación, usuario), por tipo de evento
(creación, modificación, eliminación), por usuario responsable y por rango de
fechas.

Cada entrada muestra qué se cambió, quién lo hizo y cuándo.

---

## 11. Qué ve el ciudadano

Contexto útil para entender el efecto de la configuración del backoffice.

| Página | Contenido |
|---|---|
| **Inicio** | Cifras del sistema (procesos activos, observaciones recibidas, procesos cerrados) y los 3 procesos más recientes que estén publicados o activos dentro de su ventana |
| **Consultas** | Listado de procesos con su tipo de instrumento, estado efectivo y días restantes |
| **Ficha del proceso** | Descripción, fechas, antecedentes descargables y —si el proceso está abierto— el formulario de observaciones |
| **Confirmación** | Tras enviar, se muestra el código público del envío como comprobante |

Detalles de comportamiento que conviene conocer:

- Los meses se muestran en español y, cuando el proceso cierra el mismo día,
  se indica **"Finaliza hoy"** en vez de "0 días restantes".
- En móvil, los archivos del proceso se muestran **antes** del formulario, para
  que se lean los antecedentes antes de observar.
- Un proceso cuya fecha de término ya pasó **nunca** aparece como en curso,
  aunque su estado almacenado siga siendo *Activa*.
- Un ciudadano que participó sin registro puede volver a su comprobante con el
  enlace que recibió; ese enlace lleva un código secreto y no es adivinable.

Límite anti-abuso: un máximo de **5 envíos por minuto** desde una misma
dirección IP.

---

## 12. Preguntas frecuentes

**¿Puedo corregir el texto de una observación mal escrita por el ciudadano?**
No. El contenido de una observación es inalterable por diseño. Si es spam o
una prueba, un super-admin puede archivarla; si el ciudadano quiere corregir,
debe enviar una nueva observación mientras el proceso siga abierto.

**¿Puedo eliminar definitivamente una consulta?**
No desde la interfaz. Archivar la retira de la vista conservando el
expediente completo, que es lo que corresponde a un proceso reglado.

**¿Qué pasa si publico una respuesta con un error?**
Una respuesta publicada no se puede editar ni borrar. Revisar siempre en
borrador antes de publicar. Si ya se publicó, corresponde emitir una
comunicación complementaria por la vía institucional que el GORE defina.

**Cerré la consulta antes de tiempo por error, ¿se perdieron observaciones?**
No. Cambiar el estado no borra nada. Al reabrirla —ajustando estado y
fechas— vuelve a recibir observaciones y las anteriores siguen ahí.

**¿Por qué una consulta configurada con ClaveÚnica no acepta observaciones?**
Porque la integración con ClaveÚnica está desactivada a nivel de sistema
mientras el GORE no complete el registro del cliente OIDC. Habilitar
"Sin registro" en esa consulta mientras tanto. Ver §4.3.

**¿Por qué el ciudadano no recibe el correo con la respuesta?**
Porque el transporte de correo aún no está habilitado (pendiente de
verificación del dominio en Amazon SES). La respuesta queda publicada y
registrada; solo falta activar el envío. Ver §7.1.

**¿Cuántos archivos puede adjuntar un ciudadano?**
Uno por observación, de hasta 10 MB. Como puede enviar hasta 20 observaciones
en una participación, puede acompañar hasta 20 archivos en un mismo trámite.

**¿Se pierden los datos si falla el servidor?**
No. La base de datos tiene respaldos automáticos con 7 días de retención, los
archivos viven en almacenamiento redundante de AWS y, adicionalmente, cada 48
horas se genera un respaldo en planilla de las observaciones de los procesos
activos.

**¿Cómo verifico que un antecedente publicado es el mismo que subí?**
Cada documento guarda su hash SHA-256 al momento de la carga. El equipo
técnico puede comparar ese valor con el del archivo descargado.
