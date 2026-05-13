<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modo de integracion con ClaveUnica
    |--------------------------------------------------------------------------
    |
    | 'mock' : usa un simulador local para desarrollo y QA. No requiere
    |          credenciales reales ni conexion a accounts.claveunica.gob.cl.
    | 'live' : usa el OIDC real del Estado de Chile. Requiere client_id y
    |          client_secret entregados por la Unidad de Gobierno Digital.
    |
    | El default es 'mock' por seguridad — produccion debe setear explicitamente
    | 'live' en su .env una vez que las credenciales del GORE esten registradas.
    */
    'mode' => env('CLAVEUNICA_MODE', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Credenciales OIDC (solo se usan en mode=live)
    |--------------------------------------------------------------------------
    */
    'client_id' => env('CLAVEUNICA_CLIENT_ID'),
    'client_secret' => env('CLAVEUNICA_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Endpoints oficiales del OIDC de ClaveUnica
    |--------------------------------------------------------------------------
    | Fuente: https://digital.gob.cl/biblioteca/guias/claveunica
    */
    'authorize_url' => env('CLAVEUNICA_AUTHORIZE_URL', 'https://accounts.claveunica.gob.cl/openid/authorize/'),
    'token_url' => env('CLAVEUNICA_TOKEN_URL', 'https://accounts.claveunica.gob.cl/openid/token/'),
    'userinfo_url' => env('CLAVEUNICA_USERINFO_URL', 'https://accounts.claveunica.gob.cl/openid/userinfo/'),

    /*
    |--------------------------------------------------------------------------
    | Scopes solicitados a ClaveUnica
    |--------------------------------------------------------------------------
    | El brief especifica scopes minimos: openid, run, nombre.
    | El correo no siempre viene en el token — se solicita al usuario cuando
    | no esta presente.
    */
    'scopes' => ['openid', 'run', 'name'],
];
