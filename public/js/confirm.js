// Confirmaciones de formularios SIN JS inline: la CSP (script-src 'self',
// sin 'unsafe-inline') bloquea los handlers onsubmit="..." en prod/staging,
// asi que un confirm() inline nunca corre y el form se envia directo.
// Uso: <form data-confirm="Mensaje a confirmar">...</form>
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form instanceof HTMLFormElement && form.hasAttribute('data-confirm')) {
        if (! window.confirm(form.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    }
}, true);
