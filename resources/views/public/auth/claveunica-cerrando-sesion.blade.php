{{-- Pantalla de transito del cierre de sesion federado con ClaveUnica.

     La sesion local ya se destruyo en ClaveUnicaController::logout(). Lo unico
     pendiente es cerrar la del IdP, y eso no puede hacerse con un redirect del
     servidor: el endpoint de ClaveUnica responde 204 No Content y el navegador
     se queda quieto. Lo resuelve public/js/claveunica-logout.js.

     Normalmente el ciudadano ve esta pantalla menos de dos segundos. Existe
     igual, con texto y salida manual, para que los 1500 ms no sean una pagina
     en blanco y para que quien navegue sin JavaScript no quede varado. --}}
<x-public-layout>
    @section('title', 'Cerrando tu sesion')

    <div id="claveunica-logout"
         data-endpoint="{{ $logoutUrl }}"
         data-return="{{ $returnUrl }}"></div>

    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center py-5">
                <div class="spinner-border mb-4" role="status"
                     style="color: var(--gore-primary);">
                    <span class="visually-hidden">Cerrando sesi&oacute;n&hellip;</span>
                </div>

                <h1 class="h3 fw-bold mb-2">Cerrando tu sesi&oacute;n</h1>

                <p class="text-muted mb-4">
                    Estamos cerrando tambi&eacute;n tu sesi&oacute;n en ClaveÚnica
                    para que nadie pueda seguir usando este equipo con tu identidad.
                </p>

                {{-- Salida manual: cubre el caso sin JavaScript y el de un
                     temporizador que, por lo que sea, no llegue a correr. --}}
                <noscript>
                    <p class="mb-4">
                        Tu navegador tiene JavaScript desactivado. Cierra tu sesi&oacute;n
                        de ClaveÚnica manualmente con el primer enlace y vuelve con el segundo.
                    </p>
                    <p class="mb-4">
                        <a href="{{ $logoutUrl }}" rel="noopener">Cerrar sesi&oacute;n en ClaveÚnica</a>
                    </p>
                </noscript>

                <a href="{{ $returnUrl }}" class="btn btn-outline-secondary btn-sm">
                    Volver al inicio
                </a>
            </div>
        </div>
    </section>

    <script src="{{ asset('js/claveunica-logout.js') }}"></script>
</x-public-layout>
