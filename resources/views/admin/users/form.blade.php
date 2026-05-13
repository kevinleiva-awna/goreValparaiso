<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    {{ $mode === 'create' ? 'Nuevo funcionario' : 'Editar usuario' }}
                </h1>
                <a href="{{ route('admin.users.index') }}" class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Volver al listado
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        <form method="POST"
              action="{{ $mode === 'create' ? route('admin.users.store') : route('admin.users.update', $user) }}">
            @csrf
            @if ($mode === 'edit') @method('PATCH') @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-input-label for="national_id" value="RUT *" />
                            <x-text-input id="national_id" name="national_id" type="text"
                                          :value="old('national_id', $user->national_id)"
                                          placeholder="12.345.678-9"
                                          required maxlength="12" />
                            <div class="form-text">Se acepta con o sin puntos. El digito verificador se valida.</div>
                            <x-input-error :messages="$errors->get('national_id')" />
                        </div>

                        <div class="col-md-6">
                            <x-input-label for="role" value="Rol *" />
                            <select id="role" name="role" class="form-select" required>
                                <option value="funcionario" @selected(old('role', $user->role) === 'funcionario')>Funcionario</option>
                                <option value="super-admin" @selected(old('role', $user->role) === 'super-admin')>Super-administrador</option>
                            </select>
                            <div class="form-text">
                                Funcionarios pueden gestionar consultas. Super-admin adicionalmente gestiona usuarios.
                            </div>
                            <x-input-error :messages="$errors->get('role')" />
                        </div>

                        <div class="col-md-6">
                            <x-input-label for="name" value="Nombres *" />
                            <x-text-input id="name" name="name" type="text"
                                          :value="old('name', $user->name)" required maxlength="100" />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="col-md-6">
                            <x-input-label for="last_name" value="Apellidos *" />
                            <x-text-input id="last_name" name="last_name" type="text"
                                          :value="old('last_name', $user->last_name)" required maxlength="100" />
                            <x-input-error :messages="$errors->get('last_name')" />
                        </div>

                        <div class="col-md-7">
                            <x-input-label for="email" value="Correo institucional *" />
                            <x-text-input id="email" name="email" type="email"
                                          :value="old('email', $user->email)" required maxlength="255" />
                            <x-input-error :messages="$errors->get('email')" />
                        </div>

                        <div class="col-md-5">
                            <x-input-label for="phone" value="Telefono (opcional)" />
                            <x-text-input id="phone" name="phone" type="text"
                                          :value="old('phone', $user->phone)" maxlength="20"
                                          placeholder="+569 1234 5678" />
                            <x-input-error :messages="$errors->get('phone')" />
                        </div>

                        @if ($mode === 'create')
                            <div class="col-md-6">
                                <x-input-label for="password" value="Contrasena *" />
                                <x-text-input id="password" name="password" type="password" required />
                                <div class="form-text">Minimo 8 caracteres.</div>
                                <x-input-error :messages="$errors->get('password')" />
                            </div>
                            <div class="col-md-6">
                                <x-input-label for="password_confirmation" value="Confirmar contrasena *" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required />
                            </div>
                        @else
                            <div class="col-12">
                                <hr>
                                <div class="d-flex align-items-center mb-2">
                                    <h6 class="mb-0">Cambiar contrasena</h6>
                                    <span class="small text-muted ms-2">(opcional)</span>
                                </div>
                                <p class="small text-muted">
                                    Dejar en blanco para conservar la contrasena actual.
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <x-input-label for="password" value="Nueva contrasena" />
                                        <x-text-input id="password" name="password" type="password" />
                                        <x-input-error :messages="$errors->get('password')" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input-label for="password_confirmation" value="Confirmar nueva contrasena" />
                                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" />
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($mode === 'edit' && $user->id !== auth()->id())
                            <div class="col-12">
                                <hr>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="is_active" name="is_active" value="1"
                                           @checked(old('is_active', $user->is_active))>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Cuenta activa</strong>
                                    </label>
                                    <div class="form-text">
                                        Desactivar impide login. Las acciones historicas del usuario se conservan en auditoria.
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $mode === 'create' ? 'Crear usuario' : 'Guardar cambios' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
