// JS de /admin/observations (listado). Vive como archivo externo porque la
// CSP de la app (script-src 'self', sin nonce) bloquea los <script> inline.
// Un bloque inline aqui "funciona en local" y muere silenciosamente en
// prod/staging — ver docs internos del proyecto.

// Seleccion masiva: checkboxes por fila + barra flotante "Responder en lote".
(function () {
    const selectAll = document.getElementById('select-all');
    const rowChecks = Array.from(document.querySelectorAll('.row-check'));
    const bar = document.getElementById('bulk-bar');
    const countEl = document.getElementById('bulk-count');
    const clearBtn = document.getElementById('bulk-clear');

    function refresh() {
        const checked = rowChecks.filter(c => c.checked && !c.disabled);
        if (countEl) countEl.textContent = checked.length.toString();
        if (bar) bar.classList.toggle('d-none', checked.length === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            rowChecks.forEach(c => {
                if (! c.disabled) c.checked = selectAll.checked;
            });
            refresh();
        });
    }

    rowChecks.forEach(c => c.addEventListener('change', refresh));

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            rowChecks.forEach(c => c.checked = false);
            if (selectAll) selectAll.checked = false;
            refresh();
        });
    }

    refresh();
})();

// Export: construye la URL leyendo el estado ACTUAL del form de filtros (no
// los $filters precalculados del server). Asi, si el funcionario cambia un
// filtro y clickea "Exportar" sin haber hecho submit, el export respeta su
// seleccion. El href del enlace queda como fallback server-side por si este
// script no corre.
//
// Ademas muestra un spinner mientras el archivo se arma. Un export es una
// navegacion normal, no un fetch: cuando la respuesta es un archivo el
// navegador no dispara ningun evento y la pagina se queda igual. Con el ZIP de
// adjuntos eso son cerca de 60 segundos de boton aparentemente muerto -GORE
// reporto exactamente eso el 09-sep-2026-.
//
// La unica senal que el navegador deja cuando una descarga empieza es una
// cookie: se manda un token en la URL, el controlador lo devuelve en
// `gore_export_ready` junto con el archivo y aca se espera a verlo aparecer.
// Viaja en las cabeceras, o sea llega apenas el ZIP esta armado y antes de
// transferir los 330 MB, que es justo el momento en que el navegador toma el
// control y muestra su propia barra de progreso.
(function () {
    const filterForm = document.getElementById('observations-filter-form');
    if (! filterForm) return;

    const links = Array.from(document.querySelectorAll('[data-export-format]'));
    if (links.length === 0) return;

    const COOKIE = 'gore_export_ready';
    const POLL_MS = 500;
    // Un poco mas que el fastcgi_read_timeout de nginx (180s): si a los 200
    // segundos no llego nada, ya no va a llegar.
    const TIMEOUT_MS = 200000;

    const DETALLE = {
        zip: 'Se esta bajando cada adjunto y armando el ZIP. Con el volumen actual toma cerca de un minuto. No cierres ni recargues esta pestana.',
        xlsx: 'Armando el compendio en Excel.',
        csv: 'Armando el compendio en CSV.',
    };

    const button = document.getElementById('export-button');
    const spinner = document.getElementById('export-spinner');
    const icon = document.getElementById('export-icon');
    const label = document.getElementById('export-label');
    const progress = document.getElementById('export-progress');
    const detail = document.getElementById('export-progress-detail');
    const failure = document.getElementById('export-failure');

    let timer = null;

    function readCookie() {
        const hit = document.cookie.split('; ').find(c => c.indexOf(COOKIE + '=') === 0);
        return hit ? decodeURIComponent(hit.slice(COOKIE.length + 1)) : null;
    }

    // Se borra antes de cada intento y al confirmar: si quedara viva, el
    // proximo clic veria la confirmacion del anterior y apagaria el spinner
    // de inmediato.
    function forgetCookie() {
        document.cookie = COOKIE + '=; Max-Age=0; path=/';
    }

    function busy(on, format) {
        if (spinner) spinner.classList.toggle('d-none', ! on);
        if (icon) icon.classList.toggle('d-none', on);
        if (label) label.textContent = on ? 'Preparando...' : 'Exportar';
        if (button) button.disabled = on;
        if (progress) progress.classList.toggle('d-none', ! on);
        if (on && detail) detail.textContent = DETALLE[format] || '';
        links.forEach(l => l.classList.toggle('disabled', on));
    }

    function stopWaiting() {
        clearInterval(timer);
        timer = null;
        busy(false);
    }

    // No es un token de seguridad -solo empareja la respuesta con el clic que
    // la pidio-, pero tiene que salir siempre con largo fijo: un
    // `Math.random().toString(36)` corto (pasa con valores como 0.5) dejaria
    // el token vacio, el servidor no mandaria la cookie y el spinner se
    // quedaria girando hasta el timeout.
    function makeToken() {
        let token = '';
        while (token.length < 12) {
            token += Math.random().toString(36).replace(/[^a-z0-9]/g, '');
        }

        return token.slice(0, 12);
    }

    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();

            // Ya hay una descarga armandose: un segundo clic no la acelera y
            // si dispara otro request duplica el trabajo en el servidor.
            if (timer) return;

            const format = link.getAttribute('data-export-format');
            const base = link.getAttribute('data-export-base');

            const params = new URLSearchParams();
            for (const [key, value] of new FormData(filterForm).entries()) {
                if (value !== '' && value !== null) params.append(key, value);
            }

            const token = makeToken();
            params.append('dl_token', token);

            forgetCookie();
            if (failure) failure.classList.add('d-none');
            busy(true, format);

            const deadline = Date.now() + TIMEOUT_MS;
            timer = setInterval(() => {
                if (readCookie() === token) {
                    forgetCookie();
                    stopWaiting();
                } else if (Date.now() > deadline) {
                    stopWaiting();
                    if (failure) failure.classList.remove('d-none');
                }
            }, POLL_MS);

            window.location.href = `${base}?${params.toString()}`;
        });
    });

    // Si la respuesta fue un aviso en vez de un archivo (filtro sin adjuntos,
    // tope excedido) el navegador navega y la pagina se recarga: el spinner se
    // va con ella. Pero volver con el boton "atras" puede restaurar la pagina
    // desde el bfcache con el spinner encendido y el intervalo muerto.
    window.addEventListener('pageshow', event => {
        if (event.persisted) stopWaiting();
    });
})();
