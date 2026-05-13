<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un RUT chileno. Acepta los formatos comunes y verifica el
 * digito verificador con el algoritmo modulo 11.
 *
 * Formatos aceptados (todos equivalentes):
 *   "12.345.678-9"
 *   "12345678-9"
 *   "12345678-K"
 *   "123456789"
 *
 * El valor validado se normaliza a "12345678-9" en `getNormalized()`.
 */
class Rut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('El :attribute es obligatorio.');
            return;
        }

        // Quitamos puntos, espacios y guiones; pasamos a mayusculas (K).
        $clean = strtoupper(preg_replace('/[^0-9kK]/', '', $value));

        if (strlen($clean) < 2) {
            $fail('El :attribute no tiene un formato valido.');
            return;
        }

        $body = substr($clean, 0, -1);
        $given = substr($clean, -1);

        if (! ctype_digit($body)) {
            $fail('El :attribute debe contener solo numeros y un digito verificador.');
            return;
        }

        // Rango razonable de RUTs chilenos vigentes (excluye RUTs de prueba como 1-9).
        $numeric = (int) $body;
        if ($numeric < 1_000_000 || $numeric > 99_999_999) {
            $fail('El :attribute esta fuera del rango valido.');
            return;
        }

        if (self::computeDv($numeric) !== $given) {
            $fail('El digito verificador del :attribute no es valido.');
        }
    }

    /**
     * Normaliza un RUT al formato canonico "12345678-9".
     * Util en prepareForValidation de FormRequests antes de persistir.
     */
    public static function normalize(string $value): string
    {
        $clean = strtoupper(preg_replace('/[^0-9kK]/', '', $value));
        if (strlen($clean) < 2) {
            return $value;
        }
        $body = substr($clean, 0, -1);
        $dv = substr($clean, -1);
        return "{$body}-{$dv}";
    }

    /**
     * Calcula el digito verificador modulo 11 para un numero base.
     * Retorna '0'-'9' o 'K'.
     */
    public static function computeDv(int $number): string
    {
        $digits = (string) $number;
        $sum = 0;
        $factor = 2;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += (int) $digits[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $remainder = $sum % 11;
        $dv = 11 - $remainder;

        return match (true) {
            $dv === 11 => '0',
            $dv === 10 => 'K',
            default => (string) $dv,
        };
    }
}
