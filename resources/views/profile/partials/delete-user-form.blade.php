<section>
    <header class="mb-3">
        <h2 class="h5 mb-1 text-danger">Eliminar cuenta</h2>
        <p class="text-muted small mb-0">
            Al eliminar tu cuenta, todos los recursos asociados se borraran permanentemente.
            Descarga cualquier informacion que desees conservar antes de proceder.
        </p>
    </header>

    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmUserDeletion">
        Eliminar cuenta
    </button>

    <div class="modal fade {{ $errors->userDeletion->isNotEmpty() ? 'show' : '' }}"
         id="confirmUserDeletion"
         tabindex="-1"
         aria-hidden="{{ $errors->userDeletion->isNotEmpty() ? 'false' : 'true' }}"
         @if ($errors->userDeletion->isNotEmpty()) style="display: block;" @endif
         data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar eliminacion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            Ingresa tu contrasena para confirmar la eliminacion permanente de tu cuenta.
                        </p>

                        <div class="mb-2">
                            <x-text-input id="password" name="password" type="password" placeholder="Contrasena" />
                            <x-input-error :messages="$errors->userDeletion->get('password')" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar cuenta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
