<?php

namespace App\Support;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Traduce una entrada de la bitacora a algo legible para el funcionario:
 * el tipo de recurso en espanol y lo que lo identifica en el resto del
 * backoffice (codigo de la observacion, titulo de la consulta, nombre del
 * usuario), en vez del log_name tecnico y el id interno de la tabla.
 *
 * Los modelos archivados (soft delete) no vuelven como subject
 * (subject_returns_soft_deleted_models = false), asi que la descripcion cae a
 * los atributos guardados en la propia entrada y queda sin enlace.
 */
class ActivityLogPresenter
{
    public const LABELS = [
        'consultation' => 'Consulta',
        'consultation_document' => 'Antecedente',
        'observation' => 'Observacion',
        'institutional_response' => 'Respuesta institucional',
        'user' => 'Usuario',
    ];

    public static function label(?string $logName): string
    {
        return self::LABELS[$logName] ?? ($logName ?: '-');
    }

    /**
     * @return array{prefix: ?string, text: string, code: bool, url: ?string}
     */
    public static function describe(Activity $log): array
    {
        $subject = $log->subject;
        $saved = $log->properties->get('attributes', []) + $log->properties->get('old', []);
        $fallback = ['prefix' => null, 'text' => "Registro #{$log->subject_id}", 'code' => false, 'url' => null];

        $result = match ($log->log_name) {
            'consultation' => [
                'text' => $subject?->title ?? $saved['title'] ?? null,
                'url' => $subject ? route('admin.consultations.show', $subject) : null,
            ],
            'consultation_document' => [
                'text' => self::documentText($subject?->title ?? $saved['title'] ?? null, $subject?->version ?? $saved['version'] ?? null),
            ],
            'observation' => [
                'text' => $subject?->public_id ?? $saved['public_id'] ?? null,
                'code' => true,
                'url' => $subject ? route('admin.observations.show', $subject) : null,
            ],
            'institutional_response' => [
                'prefix' => 'a la observacion',
                'text' => $subject?->observation?->public_id,
                'code' => true,
                'url' => $subject?->observation ? route('admin.observations.show', $subject->observation) : null,
            ],
            // Los ciudadanos de ClaveUnica tambien son users, pero no se
            // administran desde el backoffice: sin enlace a la edicion.
            'user' => [
                'text' => self::userText(
                    trim(($subject?->name ?? $saved['name'] ?? '').' '.($subject?->last_name ?? $saved['last_name'] ?? '')),
                    ($subject?->role ?? $saved['role'] ?? null) === User::ROLE_CITIZEN,
                ),
                'url' => $subject?->isStaff() ? route('admin.users.edit', $subject) : null,
            ],
            default => [],
        };

        return empty($result['text']) ? $fallback : $result + $fallback;
    }

    private static function userText(string $name, bool $citizen): ?string
    {
        if ($name === '') {
            return null;
        }

        return $citizen ? "{$name} · ciudadano (ClaveUnica)" : $name;
    }

    private static function documentText(?string $title, mixed $version): ?string
    {
        if (! $title) {
            return null;
        }

        return $version ? "{$title} · v{$version}" : $title;
    }
}
