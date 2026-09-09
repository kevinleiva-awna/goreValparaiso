<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Descarga masiva de adjuntos (ZIP)
    |--------------------------------------------------------------------------
    |
    | El ZIP se arma sincronico dentro del request: se bajan los adjuntos desde
    | el disk (S3 en staging y produccion) a temporales y se empaquetan. El
    | techo real no lo pone PHP sino nginx, con `fastcgi_read_timeout 180s`
    | (ver docs/staging/gore-prod-nginx.conf). Estos topes existen para avisar
    | con un mensaje claro en pantalla en vez de terminar en un 504 opaco.
    |
    | Se leen del entorno a proposito: si el volumen del proceso crece y hay
    | que moverlos, basta editar el .env del servidor y correr
    | `php artisan config:cache`, sin desplegar codigo.
    |
    | Referencia de calibracion: en septiembre de 2026 el proceso
    | `participagobiernovalparaiso` tenia 307 adjuntos y 327 MB.
    |
    */

    'zip_max_files' => (int) env('GORE_ZIP_MAX_FILES', 500),

    'zip_max_mb' => (int) env('GORE_ZIP_MAX_MB', 500),

    /*
     * Extensiones que ya vienen comprimidas: entran al ZIP sin deflate
     * (CM_STORE). Comprimir un PDF o un JPG quema CPU y no baja el peso; con
     * cientos de MB esa diferencia es justamente la que decide si el request
     * alcanza a terminar antes del timeout.
     */
    'zip_store_extensions' => [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx', 'xlsx', 'ods', 'odt', 'zip',
    ],

];
