<?php

/**
 * D21 — Hardening: validacion de cabeceras de seguridad y rate limits.
 *
 * El middleware SecurityHeaders se aplica globalmente a rutas web. Aqui
 * verificamos que las cabeceras criticas viajen en cada respuesta.
 */

use App\Models\Consultation;

it('inyecta cabeceras de seguridad en la home', function () {
    Consultation::factory()->count(2)->create([
        'status' => Consultation::STATUS_ACTIVE,
    ]);

    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeaderMissing('Strict-Transport-Security'); // local != production
});

it('inyecta Permissions-Policy bloqueando APIs sensibles', function () {
    $response = $this->get('/');

    $policy = $response->headers->get('Permissions-Policy');
    expect($policy)->toContain('geolocation=()');
    expect($policy)->toContain('camera=()');
    expect($policy)->toContain('microphone=()');
    expect($policy)->toContain('payment=()');
});

it('inyecta Content-Security-Policy estricta con default-src self', function () {
    $response = $this->get('/');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->not->toBeEmpty();
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("script-src 'self'");
    expect($csp)->toContain("frame-ancestors 'none'");
    expect($csp)->toContain("object-src 'none'");
    expect($csp)->toContain('https://fonts.bunny.net');
});

it('aplica rate limit al endpoint de ClaveUnica redirect', function () {
    // 10 GET permitidos por minuto al endpoint que inicia el flujo OIDC.
    // El registro manual fue eliminado en junio 2026, asi que el rate-limit
    // mas relevante para anti-flood es este (anti spam de redirect).
    for ($i = 0; $i < 10; $i++) {
        $response = $this->get(route('citizen.claveunica.redirect'));
        expect($response->status())->not->toBe(429);
    }

    $blocked = $this->get(route('citizen.claveunica.redirect'));
    $blocked->assertStatus(429);
});
