<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Policy;

/**
 * Politica CSP estricta del portal GORE Valparaiso (D21).
 *
 * - default/script/connect/form-action: solo el propio dominio.
 * - style: self + 'unsafe-inline' (necesario para los estilos inline de los
 *   componentes Blade existentes; eliminar gradualmente en futura iteracion).
 * - font/style: tambien permite fonts.bunny.net (CSS y woff2 de Inter).
 * - img: self + data: (favicons y background SVG inline).
 * - object: none.
 * - upgrade-insecure-requests en produccion.
 */
class GoreCspPolicy extends Policy
{
    public function configure(): void
    {
        $this
            ->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::CONNECT, Keyword::SELF)
            ->addDirective(Directive::FORM_ACTION, Keyword::SELF)
            ->addDirective(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->addDirective(Directive::OBJECT, Keyword::NONE)

            // Scripts: solo del propio dominio. El nonce automatico de spatie
            // se inyecta cuando @cspNonce se usa en una vista.
            ->addDirective(Directive::SCRIPT, Keyword::SELF)

            // Estilos: self + 'unsafe-inline' (style="..." inline en Blade)
            // + Bunny Fonts (CSS). NO permitimos otros CDN.
            ->addDirective(Directive::STYLE, [
                Keyword::SELF,
                Keyword::UNSAFE_INLINE,
                'https://fonts.bunny.net',
            ])

            // Fuentes: self + Bunny Fonts (woff2).
            ->addDirective(Directive::FONT, [
                Keyword::SELF,
                'https://fonts.bunny.net',
            ])

            // Imagenes: self + data: para favicons/SVG inline.
            ->addDirective(Directive::IMG, [
                Keyword::SELF,
                'data:',
            ])

            // Media (audio/video): self.
            ->addDirective(Directive::MEDIA, Keyword::SELF);

        // En produccion forzar HTTPS sobre cualquier recurso sub-loaded.
        if (app()->environment('production')) {
            $this->addDirective(Directive::UPGRADE_INSECURE_REQUESTS, []);
        }
    }
}
