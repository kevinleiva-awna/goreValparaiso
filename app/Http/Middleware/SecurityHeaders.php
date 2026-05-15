<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica cabeceras de seguridad recomendadas por OWASP y D.S. 7/2023.
 *
 * Headers inyectados a todas las respuestas web:
 *  - X-Frame-Options: DENY                   (clickjacking)
 *  - X-Content-Type-Options: nosniff         (MIME sniffing)
 *  - Referrer-Policy: strict-origin-when-cross-origin
 *  - Permissions-Policy: bloquea APIs sensibles del navegador
 *  - Strict-Transport-Security: solo en HTTPS productivo
 *
 * NO inyecta CSP — eso se delega al paquete spatie/laravel-csp para mantener
 * la politica versionable en config/csp.php.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), camera=(), microphone=(), payment=(), interest-cohort=()'
        );

        // HSTS solo en HTTPS productivo. En dev local (http://localhost) no aplica.
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
