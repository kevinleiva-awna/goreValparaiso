@php
    $authMethods = old('auth_methods', $consultation->auth_methods ?? []);
    $startsAt = old('starts_at',
        $consultation->starts_at?->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at',
        $consultation->ends_at?->format('Y-m-d\TH:i'));
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <x-input-label for="title" value="Titulo de la consulta *" />
        <x-text-input id="title" name="title" type="text"
                      :value="old('title', $consultation->title)" required maxlength="255" />
        <x-input-error :messages="$errors->get('title')" />
    </div>

    <div class="col-md-4">
        <x-input-label for="slug" value="Slug (URL)" />
        <x-text-input id="slug" name="slug" type="text"
                      :value="old('slug', $consultation->slug)"
                      placeholder="auto-generado desde el titulo" />
        <x-input-error :messages="$errors->get('slug')" />
    </div>

    <div class="col-md-4">
        <x-input-label for="instrument_type" value="Tipo de instrumento *" />
        <select id="instrument_type" name="instrument_type" class="form-select" required>
            @foreach (['IPT' => 'IPT - Instrumento Planificacion Territorial', 'PROT' => 'PROT - Plan Regional', 'ZUBC' => 'ZUBC - Zonificacion Borde Costero', 'OTRO' => 'OTRO'] as $value => $label)
                <option value="{{ $value }}"
                        @selected(old('instrument_type', $consultation->instrument_type) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('instrument_type')" />
    </div>

    <div class="col-md-4">
        <x-input-label for="status" value="Estado *" />
        <select id="status" name="status" class="form-select" required>
            @foreach (['draft' => 'Borrador', 'published' => 'Publicada', 'active' => 'Activa', 'closed' => 'Cerrada', 'archived' => 'Archivada'] as $value => $label)
                <option value="{{ $value }}"
                        @selected(old('status', $consultation->status) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" />
    </div>

    <div class="col-md-4">
        <x-input-label value="Metodos de autenticacion *" />
        <div class="border rounded p-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="auth_methods[]" value="claveunica"
                       id="auth_claveunica"
                       @checked(in_array('claveunica', (array) $authMethods))>
                <label class="form-check-label" for="auth_claveunica">
                    ClaveUnica
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       name="auth_methods[]" value="guest"
                       id="auth_guest"
                       @checked(in_array('guest', (array) $authMethods))>
                <label class="form-check-label" for="auth_guest">
                    Sin registro <span class="text-muted small">(nombre/raz&oacute;n social + email)</span>
                </label>
            </div>
        </div>
        <x-input-error :messages="$errors->get('auth_methods')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="starts_at" value="Inicio del proceso" />
        <input id="starts_at" name="starts_at" type="datetime-local"
               class="form-control" value="{{ $startsAt }}">
        <x-input-error :messages="$errors->get('starts_at')" />
    </div>

    <div class="col-md-6">
        <x-input-label for="ends_at" value="Termino del proceso" />
        <input id="ends_at" name="ends_at" type="datetime-local"
               class="form-control" value="{{ $endsAt }}">
        <x-input-error :messages="$errors->get('ends_at')" />
    </div>

    <div class="col-12">
        <x-input-label for="summary" value="Resumen (max 1000 caracteres)" />
        <textarea id="summary" name="summary" rows="2" maxlength="1000"
                  class="form-control">{{ old('summary', $consultation->summary) }}</textarea>
        <x-input-error :messages="$errors->get('summary')" />
    </div>

    <div class="col-12">
        <x-input-label for="description" value="Descripcion completa" />
        <textarea id="description" name="description" rows="8"
                  class="form-control">{{ old('description', $consultation->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" />
    </div>
</div>
