// Cierre de sesion federado con ClaveUnica ("Metodo 2" de la guia tecnica).
//
// El endpoint de logout del IdP responde 204 No Content, sin cabecera
// Location: si el servidor redirige ahi, el navegador hace la peticion y se
// queda donde estaba, y al ciudadano no le pasa nada visible. Entonces vuelve
// a apretar "Cerrar sesion", el POST llega con el token CSRF de la sesion ya
// destruida, y aparece una pagina 419.
//
// La secuencia correcta es: navegar al endpoint —la peticion viaja con las
// cookies del IdP y cierra su sesion— y, como el 204 deja la pagina en pie,
// usar un temporizador para volver al home por cuenta propia.
//
// La guia prohibe expresamente llamar al endpoint desde un popup o un iframe:
// eso provoca un error de CORS y la sesion de ClaveUnica queda abierta.
//
// Va en public/js/ y no inline porque la CSP del portal es script-src 'self'.
(function () {
    var config = document.getElementById('claveunica-logout');

    if (! config) {
        return;
    }

    var endpoint = config.getAttribute('data-endpoint');
    var destino = config.getAttribute('data-return') || '/';

    // Sin endpoint no hay nada que cerrar: al menos no dejamos al ciudadano
    // varado en una pantalla de transito.
    if (! endpoint) {
        window.location.replace(destino);
        return;
    }

    // Dispara el cierre en accounts.claveunica.gob.cl.
    window.location.href = endpoint;

    // Rescate. Si ClaveUnica devolvio 204 (lo habitual), seguimos en esta
    // pagina y este temporizador nos lleva al home. Si en cambio redirigio de
    // vuelta por su cuenta, ya navegamos y esto nunca llega a correr.
    // replace() y no href para que el boton "atras" no reviva esta pantalla.
    window.setTimeout(function () {
        window.location.replace(destino);
    }, 1500);
})();
