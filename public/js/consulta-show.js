/**
 * Comportamiento del formulario de observacion en la ficha publica de
 * consulta. Vive en un archivo EXTERNO (no inline) a proposito: la CSP
 * estricta del portal usa `script-src 'self'`, que permite scripts del
 * propio dominio sin nonce pero BLOQUEA cualquier <script> inline.
 * Ver app/Support/Csp/GoreCspPolicy.php (nonce_enabled=false).
 *
 * Dos responsabilidades:
 *  1. Contador de caracteres del cuerpo de la observacion.
 *  2. Selector de tipo de participante (Persona Natural / Juridica / Org):
 *     muestra solo el bloque de campos del actor elegido y deshabilita los
 *     ocultos para que no entren al submit. El estado inicial ya viene
 *     resuelto server-side (display:none + disabled), asi que el formulario
 *     es correcto aun sin JS; este script solo refina al cambiar la seleccion.
 */
(function () {
    'use strict';

    function initCharCounter() {
        const txt = document.getElementById('obs_body');
        const counter = document.getElementById('obs_charcount');
        if (!txt || !counter) return;
        const update = () => counter.textContent = txt.value.length.toLocaleString('es-CL');
        txt.addEventListener('input', update);
        update();
    }

    function initActorSelector() {
        const radios = document.querySelectorAll('.actor-radio');
        if (!radios.length) return;
        const blocks = document.querySelectorAll('.actor-fields');

        // data-show-for puede tener varios valores separados por espacio
        // (ej. "pj org"): un mismo bloque sirve a Persona Juridica y a
        // Organizacion sin PJ.
        const apply = (value) => {
            blocks.forEach(block => {
                const matches = block.dataset.showFor.split(/\s+/).includes(value);
                block.style.display = matches ? '' : 'none';
                // Deshabilitar inputs ocultos para que no entren al submit
                // ni bloqueen el envio por algun required.
                block.querySelectorAll('input, select').forEach(el => {
                    el.disabled = !matches;
                });
            });
            document.querySelectorAll('.actor-card').forEach(card => {
                const inp = card.querySelector('input');
                card.classList.toggle('actor-card-selected', inp && inp.value === value);
            });
        };

        radios.forEach(r => r.addEventListener('change', () => apply(r.value)));
        const checked = document.querySelector('.actor-radio:checked');
        apply(checked ? checked.value : 'natural');
    }

    function init() {
        initCharCounter();
        initActorSelector();
    }

    // Robusto ante el orden de carga: si el DOM aun se parsea, esperar;
    // si ya esta listo, ejecutar de inmediato.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
