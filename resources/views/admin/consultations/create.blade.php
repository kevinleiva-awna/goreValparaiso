<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Nueva consulta</h1>
                <a href="{{ route('admin.consultations.index') }}" class="small text-muted text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Volver al listado
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        <form method="POST" action="{{ route('admin.consultations.store') }}">
            @csrf

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @include('admin.consultations._form')
                </div>
                <div class="card-footer bg-light d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Crear consulta
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
