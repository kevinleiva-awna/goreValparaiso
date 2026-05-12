<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    {{ $mode === 'create' ? 'Agregar etapa' : 'Editar etapa' }}
                </h1>
                <a href="{{ route('admin.consultations.show', $consultation) }}"
                   class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver a {{ Str::limit($consultation->title, 50) }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        <form method="POST"
              action="{{ $mode === 'create'
                  ? route('admin.consultations.stages.store', $consultation)
                  : route('admin.consultations.stages.update', [$consultation, $stage]) }}">
            @csrf
            @if ($mode === 'edit') @method('PATCH') @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @php
                        $startsAt = old('starts_at', $stage->starts_at?->format('Y-m-d\TH:i'));
                        $endsAt = old('ends_at', $stage->ends_at?->format('Y-m-d\TH:i'));
                        $acceptsObs = old('accepts_observations', $stage->accepts_observations ?? true);
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-8">
                            <x-input-label for="name" value="Nombre de la etapa *" />
                            <x-text-input id="name" name="name" type="text"
                                          :value="old('name', $stage->name)"
                                          placeholder="Ej: Difusion, Recepcion de observaciones, Analisis"
                                          required />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="col-md-4">
                            <x-input-label for="status" value="Estado *" />
                            <select id="status" name="status" class="form-select" required>
                                @foreach (['pending' => 'Pendiente', 'active' => 'Activa', 'closed' => 'Cerrada'] as $value => $label)
                                    <option value="{{ $value }}"
                                            @selected(old('status', $stage->status) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" />
                        </div>

                        <div class="col-md-6">
                            <x-input-label for="starts_at" value="Inicio de la etapa" />
                            <input id="starts_at" name="starts_at" type="datetime-local"
                                   class="form-control" value="{{ $startsAt }}">
                            <x-input-error :messages="$errors->get('starts_at')" />
                        </div>

                        <div class="col-md-6">
                            <x-input-label for="ends_at" value="Termino de la etapa" />
                            <input id="ends_at" name="ends_at" type="datetime-local"
                                   class="form-control" value="{{ $endsAt }}">
                            <x-input-error :messages="$errors->get('ends_at')" />
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="accepts_observations" name="accepts_observations" value="1"
                                       @checked($acceptsObs)>
                                <label class="form-check-label" for="accepts_observations">
                                    <strong>Acepta observaciones</strong> en esta etapa
                                </label>
                                <div class="form-text">
                                    Desactivar para etapas puramente informativas (difusion, analisis).
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <x-input-label for="description" value="Descripcion (opcional)" />
                            <textarea id="description" name="description" rows="4"
                                      maxlength="2000"
                                      class="form-control">{{ old('description', $stage->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" />
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.consultations.show', $consultation) }}"
                       class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $mode === 'create' ? 'Crear etapa' : 'Guardar cambios' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
