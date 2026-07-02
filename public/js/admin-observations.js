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
(function () {
    const filterForm = document.getElementById('observations-filter-form');
    if (! filterForm) return;
    document.querySelectorAll('[data-export-format]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const base = link.getAttribute('data-export-base');
            const formData = new FormData(filterForm);
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                if (value !== '' && value !== null) params.append(key, value);
            }
            const qs = params.toString();
            window.location.href = qs ? `${base}?${qs}` : base;
        });
    });
})();
