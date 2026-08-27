{{-- Boton oficial de ClaveUnica.

     Marcado tomado literalmente del "Manual de uso boton ClaveUnica"
     (wikiguias.digital.gob.cl/Manuales/BotonCU). Su uso correcto es requisito
     de la certificacion que habilita las credenciales de produccion, asi que
     este componente NO debe adaptarse a la paleta ni a los botones del GORE:
     colores, tipografia, espaciados e isotipo son de marca registrada. Los
     estilos viven en la seccion 5 de resources/scss/app.scss.

     Reglas de marca que el manual exige y que este componente resuelve:
       - La marca se escribe "ClaveUnica" junta, con C y U mayusculas y tilde
         en la U. El resto del codigo usa ASCII sin tildes por convencion, pero
         el texto visible del boton es marca registrada y va acentuado.
       - No usar textos del tipo "ingresa con tu ClaveUnica" junto a un boton
         que ya dice "ClaveUnica": ahi corresponde "Iniciar sesion". Por eso
         existe el prop `texto`.
       - El boton no puede enlazar a otro metodo de autenticacion que no sea
         ClaveUnica; el href por defecto es la ruta del flujo OIDC.

     Props:
       - texto:     'marca' (default, muestra "ClaveUnica") | 'sesion' (muestra
                    "Iniciar sesion"). Usar 'sesion' cuando el texto que rodea
                    al boton ya nombra a ClaveUnica, para no ser redundante.
       - fullWidth: true para que ocupe el ancho disponible (max 550px).
       - href:      destino. Por defecto, el inicio del flujo OIDC.
--}}
@props([
    'texto' => 'marca',
    'fullWidth' => false,
    'href' => null,
])

@php
    $esSesion = $texto === 'sesion';

    // Textos y aria-label exactos de los dos ejemplos oficiales del manual.
    $etiqueta = $esSesion ? 'Iniciar sesión' : 'ClaveÚnica';
    $ariaLabel = $esSesion ? 'Iniciar sesión con ClaveÚnica' : 'Continuar con ClaveÚnica';
@endphp

<a {{ $attributes->class([
        'btn-cu',
        'btn-m',
        'btn-color-estandar',
        'rounded-middle',
        'btn-fw' => $fullWidth,
    ]) }}
   href="{{ $href ?? route('citizen.claveunica.redirect') }}"
   aria-label="{{ $ariaLabel }}">
    <span class="cl-claveunica" aria-hidden="true"></span>
    <span class="texto" aria-hidden="true">{{ $etiqueta }}</span>
</a>
