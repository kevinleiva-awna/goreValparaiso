{{-- Bloque de una observacion dentro del repetidor. Reutilizado por el loop
     server-side (con valores de old()) y por el <template> que clona el JS
     (con el placeholder __INDEX__ / __NUM__ en su lugar).

     Variables: $index (numerico o '__INDEX__'), $values (array old o []),
     $display (numero visible o '__NUM__'). --}}
@php
    $obs = $values ?? [];
@endphp
<fieldset class="gore-obs-block border rounded p-3 p-md-4 mb-3" data-obs-block data-obs-index="{{ $index }}">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <legend class="h6 mb-0 fw-semibold w-auto float-none">
            Observacion <span data-obs-num>{{ $display }}</span>
        </legend>
        <button type="button" class="btn btn-sm btn-outline-danger obs-remove" data-obs-remove
                aria-label="Quitar esta observacion" @if($display == 1) hidden @endif>
            <i class="bi bi-x-lg me-1"></i> Quitar
        </button>
    </div>

    <div class="mb-3">
        <x-input-label :for="'obs_category_'.$index" value="Tema (opcional)" />
        <select id="obs_category_{{ $index }}" name="observations[{{ $index }}][category]" class="form-select">
            <option value="">Sin tema especifico</option>
            @foreach (\App\Models\Observation::CATEGORIES as $cat)
                <option value="{{ $cat }}" @selected(($obs['category'] ?? '') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('observations.'.$index.'.category')" />
    </div>

    <div class="mb-3">
        <x-input-label :for="'obs_subject_'.$index" value="Asunto (opcional)" />
        <input id="obs_subject_{{ $index }}" name="observations[{{ $index }}][subject]" type="text"
               class="form-control" maxlength="255" value="{{ $obs['subject'] ?? '' }}"
               placeholder="Ej: Observacion sobre el uso de suelo en Concon">
        <x-input-error :messages="$errors->get('observations.'.$index.'.subject')" />
    </div>

    <div class="mb-3">
        <x-input-label :for="'obs_body_'.$index" value="Tu observacion *" />
        <textarea id="obs_body_{{ $index }}" name="observations[{{ $index }}][body]"
                  class="form-control obs-body" rows="6" minlength="10" maxlength="10000" required
                  placeholder="Describe tu observacion con el mayor detalle posible. Minimo 10 caracteres, maximo 10.000.">{{ $obs['body'] ?? '' }}</textarea>
        <div class="form-text"><span class="obs-charcount">0</span> / 10.000 caracteres</div>
        <x-input-error :messages="$errors->get('observations.'.$index.'.body')" />
    </div>

    <div class="mb-0">
        <x-input-label :for="'obs_attachment_'.$index" value="Archivo adjunto (opcional)" />
        <input id="obs_attachment_{{ $index }}" name="observations[{{ $index }}][attachment]" type="file"
               class="form-control"
               accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt">
        <div class="form-text">PDF, imagen, Word, Excel o texto plano. Maximo 10 MB.</div>
        <x-input-error :messages="$errors->get('observations.'.$index.'.attachment')" />
    </div>
</fieldset>
