<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Consultation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'slug', 'status', 'instrument_type',
                'starts_at', 'ends_at', 'auth_methods',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('consultation');
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_IPT = 'IPT';
    public const TYPE_PROT = 'PROT';
    public const TYPE_ZUBC = 'ZUBC';
    public const TYPE_OTHER = 'OTRO';

    /**
     * Nombre completo de cada tipo de instrumento, para el front ciudadano
     * (la sigla sola no le dice nada a quien no es del rubro — pedido GORE
     * 02-jul). Las tarjetas/badges compactos pueden seguir usando la sigla.
     */
    public const TYPE_LABELS = [
        self::TYPE_IPT => 'Instrumento de Planificación Territorial',
        self::TYPE_PROT => 'Plan Regional de Ordenamiento Territorial',
        self::TYPE_ZUBC => 'Zonificación de Uso del Borde Costero',
        self::TYPE_OTHER => 'Otro instrumento',
    ];

    public const AUTH_CLAVEUNICA = 'claveunica';
    public const AUTH_GUEST = 'guest';

    public function allowsGuest(): bool
    {
        return in_array(self::AUTH_GUEST, (array) ($this->auth_methods ?? []), true);
    }

    public function getInstrumentTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->instrument_type] ?? $this->instrument_type;
    }

    protected $fillable = [
        'public_id',
        'slug',
        'title',
        'summary',
        'description',
        'instrument_type',
        'status',
        'starts_at',
        'ends_at',
        'auth_methods',
        'map_image_url',
        'map_geojson',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'auth_methods' => 'array',
            'map_geojson' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $consultation) {
            if (empty($consultation->public_id)) {
                $consultation->public_id = (string) Str::uuid();
            }
        });
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConsultationDocument::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpenForObservations(): bool
    {
        // Abierta = estado activo y "ahora" dentro de su ventana. El guard de
        // fecha cierra la brecha entre el status almacenado y la fecha real:
        // aunque un cierre manual quede pendiente, un proceso fuera de su
        // ventana [starts_at, ends_at] no acepta observaciones.
        return $this->status === self::STATUS_ACTIVE && $this->isWithinWindow();
    }

    /**
     * True si "ahora" cae dentro de la ventana del proceso. Un extremo nulo
     * significa "sin limite" por ese lado.
     */
    public function isWithinWindow(): bool
    {
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * Estado "efectivo" para la vista publica: respeta la fecha aunque el
     * status almacenado siga 'active' por un cierre manual pendiente. Asi la
     * ficha nunca muestra "activa" sobre un proceso cuya ventana ya expiro.
     */
    public function effectiveStatus(): string
    {
        if ($this->status === self::STATUS_ACTIVE) {
            if ($this->ends_at && $this->ends_at->isPast()) {
                return self::STATUS_CLOSED;
            }
            if ($this->starts_at && $this->starts_at->isFuture()) {
                return self::STATUS_PUBLISHED;
            }
        }
        return $this->status;
    }
}
