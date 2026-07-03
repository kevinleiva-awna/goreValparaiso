<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Observation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * El CONTENIDO de la observacion es inalterable: solo se audita su
     * creacion, nunca updates. El soft-delete ("archivar") es la unica
     * excepcion — reversible y restringido a super-admin — para sacar del
     * listado spam/duplicados/pruebas sin destruir el expediente.
     */
    protected static $recordEvents = ['created'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'public_id', 'consultation_id', 'submission_group_id',
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

    /**
     * Temas disponibles para clasificar una observacion. Fuente unica
     * compartida por el formulario publico (select) y la validacion del
     * StoreObservationRequest (Rule::in).
     */
    public const CATEGORIES = [
        'Uso de suelo',
        'Vialidad',
        'Areas verdes',
        'Patrimonio',
        'Equipamiento',
        'Riesgo natural',
        'Otro',
    ];

    protected $fillable = [
        'public_id',
        'consultation_id',
        'submission_group_id',
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

    /**
     * Nombre "a quien responder": el de la persona natural o, para PJ y
     * Organizacion sin PJ, la razon social. Los snapshots de PJ/Org dejan
     * snapshot_full_name vacio, asi que TODO lo que muestre identidad debe
     * pasar por aqui y no leer snapshot_full_name directo (bug GORE 02-jul).
     */
    public function getDisplayNameAttribute(): ?string
    {
        return $this->snapshot_full_name ?: $this->snapshot_legal_name;
    }

    /** RUT de la persona natural o de la entidad segun el tipo de actor. */
    public function getDisplayRutAttribute(): ?string
    {
        return $this->snapshot_national_id ?: $this->snapshot_business_id;
    }

    /** Etiqueta humana del tipo de participante (snapshot_actor_type). */
    public function getActorTypeLabelAttribute(): string
    {
        return match ($this->snapshot_actor_type) {
            self::ACTOR_PJ => 'Persona Juridica',
            self::ACTOR_ORG => 'Organizacion sin PJ',
            default => 'Persona Natural',
        };
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
        // withTrashed: el expediente de una observacion debe seguir mostrando
        // su consulta aunque esta se archive (soft delete). Sin esto, la
        // relacion devuelve null para consultas archivadas y el backoffice
        // revienta al armar links/titulos (500 reportado por GORE 03-jul).
        return $this->belongsTo(Consultation::class)->withTrashed();
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
