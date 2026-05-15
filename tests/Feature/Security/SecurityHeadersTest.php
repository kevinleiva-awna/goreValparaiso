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

it('aplica rate limit al endpoint de registro ciudadano', function () {
    // 5 envios permitidos por minuto. Al 6to debe responder 429.
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post(route('citizen.register.store'), [
            'name' => 'Test',
            'last_name' => 'Smith',
            'national_id' => '11111111-1',
            'email' => "spam{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        // No nos importa el status del POST en si — 422 o 302 son OK.
        // Solo que no bloquee por rate limit aun.
        expect($response->status())->not->toBe(429);
    }

    $blocked = $this->post(route('citizen.register.store'), [
        'name' => 'Spammer',
        'email' => 'overflow@example.com',
    ]);
    $blocked->assertStatus(429);
});
