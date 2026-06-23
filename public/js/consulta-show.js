/**
 * Comportamiento del formulario de observacion en la ficha publica de
 * consulta. Vive en un archivo EXTERNO (no inline) a proposito: la CSP
 * estricta del portal usa `script-src 'self'`, que permite scripts del
 * propio dominio sin nonce pero BLOQUEA cualquier <script> inline.
 * Ver app/Support/Csp/GoreCspPolicy.php (nonce_enabled=false).
 *
 * Tres responsabilidades:
 *  1. Selector de tipo de participante (Persona Natural / Juridica / Org):
 *     muestra solo el bloque del actor elegido y deshabilita los ocultos.
 *  2. Repetidor de observaciones: agregar/quitar bloques, cada uno con su
 *     tema/asunto/cuerpo/adjunto. El primer bloque viene server-rendered, asi
 *     que el formulario es correcto aun sin JS (envia una observacion).
 *  3. Contador de caracteres por bloque de observacion (event delegation, para
 *     que tambien cubra los bloques agregados dinamicamente).
 */
(function () {
    'use strict';

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

    function updateCounter(textarea) {
        const block = textarea.closest('[data-obs-block]');
        const counter = block && block.querySelector('.obs-charcount');
        if (counter) counter.textContent = textarea.value.length.toLocaleString('es-CL');
    }

    function initObservationRepeater() {
        const repeater = document.getElementById('observations-repeater');
        const addBtn = document.getElementById('obs-add');
        const template = document.getElementById('obs-block-template');
        if (!repeater || !addBtn || !template) return;

        const MAX = parseInt(addBtn.dataset.max || '20', 10);
        const blocks = () => Array.from(repeater.querySelectorAll('[data-obs-block]'));

        // Indice monotonico para los name="observations[i][...]": arranca en
        // max(indices server-rendered) + 1 para no chocar con bloques que el
        // servidor repoblo tras un error de validacion (claves posiblemente no
        // contiguas).
        const seenIndices = blocks()
            .map(b => parseInt(b.dataset.obsIndex, 10))
            .filter(n => !Number.isNaN(n));
        let nextIndex = (seenIndices.length ? Math.max.apply(null, seenIndices) : -1) + 1;

        const refresh = () => {
            const list = blocks();
            list.forEach((block, i) => {
                const num = block.querySelector('[data-obs-num]');
                if (num) num.textContent = String(i + 1);
                // "Quitar" solo tiene sentido si hay mas de un bloque.
                const remove = block.querySelector('[data-obs-remove]');
                if (remove) remove.hidden = list.length <= 1;
            });
            addBtn.disabled = list.length >= MAX;
        };

        addBtn.addEventListener('click', () => {
            if (blocks().length >= MAX) return;
            const html = template.innerHTML
                .split('__INDEX__').join(String(nextIndex))
                .split('__NUM__').join(String(blocks().length + 1));
            nextIndex += 1;
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const block = holder.firstElementChild;
            repeater.appendChild(block);
            refresh();
            const field = block.querySelector('select, textarea, input');
            if (field) field.focus();
        });

        repeater.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-obs-remove]');
            if (!btn) return;
            const list = blocks();
            if (list.length <= 1) return;           // nunca quitar el ultimo
            const block = btn.closest('[data-obs-block]');
            const pos = list.indexOf(block);
            block.remove();
            refresh();
            // Foco al bloque anterior (o al primero) tras quitar.
            const remaining = blocks();
            const focusBlock = remaining[pos - 1] || remaining[0];
            const field = focusBlock && focusBlock.querySelector('select, textarea, input');
            (field || addBtn).focus();
        });

        repeater.addEventListener('input', (e) => {
            if (e.target.classList && e.target.classList.contains('obs-body')) {
                updateCounter(e.target);
            }
        });

        // Estado inicial: contadores de los bloques server-rendered + visibilidad.
        repeater.querySelectorAll('.obs-body').forEach(updateCounter);
        refresh();
    }

    function init() {
        initActorSelector();
        initObservationRepeater();
    }

    // Robusto ante el orden de carga: si el DOM aun se parsea, esperar;
    // si ya esta listo, ejecutar de inmediato.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
