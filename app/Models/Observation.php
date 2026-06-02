<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Observation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Las observaciones son inalterables: solo se audita su CREACION,
     * nunca updates ni deletes (que ademas no deberian ocurrir).
     */
    protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'public_id', 'consultation_id', 'stage_id',
                'subject', 'category', 'auth_method_used',
            ])
            ->dontSubmitEmptyLogs()
            ->useLogName('observation');
    }

    public const AUTH_CLAVEUNICA = 'claveunica';
    public const AUTH_GUEST = 'guest';

    public const ACTOR_NATURAL = 'natural';
    public const ACTOR_PJ = 'pj';
    public const ACTOR_ORG = 'org';

    public const ID_TYPE_RUT = 'rut';
    public const ID_TYPE_PASSPORT = 'pasaporte';

    protected $fillable = [
        'public_id',
        'consultation_id',
        'stage_id',
        'user_id',
        'subject',
        'body',
        'category',
        'attachment_path',
        'attachment_disk',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size_bytes',
        'auth_method_used',
        // Snapshot extendido (acta junio 2026, punto 3).
        'snapshot_actor_type',
        'snapshot_id_type',
        'snapshot_national_id',
        'snapshot_full_name',
        'snapshot_legal_name',
        'snapshot_trade_name',
        'snapshot_business_id',
        'snapshot_email',
        'snapshot_phone',
        'snapshot_address',
        'snapshot_comuna',
        'snapshot_age',
        'submitted_at',
        'ip_address',
        'user_agent',
    ];

    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_path);
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'snapshot_age' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $obs) {
            if (empty($obs->public_id)) {
                $obs->public_id = (string) Str::uuid();
            }
            if (empty($obs->submitted_at)) {
                $obs->submitted_at = now();
            }
            // Invariante: PJ y Organizacion sin PJ NUNCA entran por ClaveUnica
            // (el servicio del Estado solo identifica personas naturales).
            // Fail-fast en BD-write si el controller manda combinaciones invalidas.
            if (in_array($obs->snapshot_actor_type, [self::ACTOR_PJ, self::ACTOR_ORG], true)
                && $obs->user_id !== null) {
                throw new \LogicException(
                    "PJ/Org no pueden tener user_id (solo entran via guest). " .
                    "Recibido: actor_type={$obs->snapshot_actor_type}, user_id={$obs->user_id}"
                );
            }
        });
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ConsultationStage::class, 'stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(InstitutionalResponse::class);
    }
}
