# Capacitación de usuarios — preparación en local

Hoja de apoyo para presentar la plataforma desde el notebook, con datos de demostración. Todo lo que aparece en pantalla es ficticio y no sale de este equipo.

## Antes de empezar

1. Levantar MariaDB (XAMPP no la deja como servicio):

   ```bash
   powershell -Command "Start-Process 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini','--standalone' -WindowStyle Hidden"
   ```

2. Levantar la app aceptando conexiones de la red, para que los asistentes participen desde el celular. La primera vez Windows pide permiso de firewall para `php.exe`: permitir en **redes privadas**.

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

3. Abrir dos navegadores distintos (por ejemplo Edge y Chrome):
   - **Navegador 1:** el backoffice, `http://localhost:8000/admin/login`.
   - **Navegador 2:** el portal ciudadano, `http://localhost:8000`. Tiene que ser otro navegador: con una sesión de funcionario abierta, la ficha muestra «Cuenta institucional» y no deja participar.

## Cuentas (solo existen en este equipo)

| Rol | Correo | Contraseña |
|---|---|---|
| Super-admin (quien presenta) | `kevin@awna.cl` | la del `DatabaseSeeder` |
| Funcionaria (autora de la demo) | `daniela.fuentes@example.com` | la del `DatabaseSeeder` |

Para mostrar qué no ve un funcionario, entrar con la funcionaria en una ventana InPrivate: no aparecen Usuarios ni Bitácora, ni el botón Archivar.

## Qué hay cargado

| Consulta | Estado | Para qué sirve |
|---|---|---|
| Zonificación del Borde Costero — Litoral Central (ZUBC) | Activa hasta el 30-oct | La consulta de trabajo: 19 observaciones, adjuntos, respuestas y borradores |
| Plan Regional de Ordenamiento Territorial — Imagen Objetivo (PROT) | Publicada, inicia el 9-nov | Muestra «Participación no habilitada aún» |
| Modificación al PRI — Valle del Aconcagua (IPT) | Cerrada | Expediente terminado: 6 observaciones, todas respondidas |

## Dónde está cada cosa de la guía

**Portal ciudadano.**
- Portada y luego **Consultas**: aparecen los tres estados.
- Ficha del Borde Costero: antecedentes. El *Resumen ejecutivo* está en versión 2.
- Abrir también la ficha del PROT (todavía no se puede participar) y la del Aconcagua (cerrada, con sus respuestas publicadas).

**Crear la consulta B en vivo.** Datos para copiar:
- Título: `Modificación al Plan Regulador Intercomunal — Sector Borde Costero Norte`
- Resumen: `Actualización de las zonas de riesgo y de las áreas verdes del borde costero norte. Revisa los antecedentes y envía tus observaciones.`
- Descripción: `La modificación incorpora las zonas de riesgo por tsunami, amplía las áreas verdes junto a los esteros y asegura accesos públicos a las playas del sector norte.`
- Tipo: IPT.
- Fechas: inicio **hoy** (si se pone una fecha futura, la consulta aparece como «próxima» y no recibe observaciones) y término el 20-11-2026.
- Métodos: ClaveÚnica y Sin registro.
- Estado: Borrador. Después de subir el antecedente se cambia a Activa.
- Antecedente: `archivos-en-vivo/memoria-explicativa-borde-costero-norte.pdf`. Para mostrar **Reemplazar**, subir `memoria-explicativa-borde-costero-norte-v2.pdf`.

**Participación en vivo.**
- Ver la IP del notebook con `ipconfig` (Adaptador de LAN inalámbrica → Dirección IPv4).
- Abrir `http://<IP>:8000/consultas` en el navegador 2 y generar el QR desde el navegador. En Edge: clic derecho en la página → «Crear código QR para esta página».
- Para adjuntar, hay una foto en `archivos-en-vivo/foto-para-adjuntar.jpg`.
- Si la red no deja que los celulares lleguen al notebook (pasa en redes de invitados), participar desde el navegador 2.
- ClaveÚnica, en local, abre un **simulador** («Mock ClaveUnica — Solo para desarrollo»). Explicarlo como tal o mostrar ClaveÚnica real en QA.

**Revisar observaciones.**
- Filtrar por la consulta del Borde Costero.
- Buscar `acceso a la playa`: aparecen 4 observaciones casi iguales, que se usan en el bloque de respuestas.
- Hay un envío de **«test test / prueba prueba 123»** para archivar en vivo (super-admin). Luego mostrar el botón **Archivadas**.

**Responder.**
- Hay dos borradores ya escritos: Agrupación Amigos del Humedal e Inmobiliaria Altos del Pacífico. Revisar uno y **Publicar**, y mostrarlo en la ficha pública.
- Responder en lote las 4 de «acceso a la playa».
- El correo no sale en local; en producción tampoco sale todavía (falta activar SES).
- Ojo: «Pendientes de respuesta» del Dashboard (13) no cuenta las que tienen borrador.

**Exportar.**
- Filtrar por la consulta del Borde Costero.
- **Adjuntos en ZIP** trae 5 archivos, nombrados por código de expediente. Después mostrar el XLSX.

**Super-admin.**
- Usuarios: crear un funcionario y desactivarlo.
- Bitácora: muestra el historial desde junio, y todo lo hecho en la sesión aparece arriba.

## Ensayar y volver a dejar todo como estaba

Los datos quedaron respaldados en `storage/app/demo-capacitacion.sql`. Después de ensayar, este comando deja la base exactamente como antes. Puede pedir que vuelvas a ingresar al backoffice.

```bash
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root gore_dev < storage\app\demo-capacitacion.sql"
```

Para regenerar todo desde cero se usa `scripts/demo-capacitacion.php` (ver su cabecera).

## Si algo falla

| Síntoma | Causa | Solución |
|---|---|---|
| Error de conexión a la base | MariaDB no está corriendo | Paso 1 de «Antes de empezar» |
| Página sin estilos | Falta compilar el front | `npm run build` |
| Los celulares no cargan la página | Firewall o red de invitados | Participar desde el navegador 2 |
| Sin proyector o sin equipo | — | Capturas de todas las pantallas en `docs/etapa-2/img/` |
