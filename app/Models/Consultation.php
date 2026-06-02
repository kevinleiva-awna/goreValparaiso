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

    public const AUTH_CLAVEUNICA = 'claveunica';
    public const AUTH_GUEST = 'guest';

    public function allowsGuest(): bool
    {
        return in_array(self::AUTH_GUEST, (array) ($this->auth_methods ?? []), true);
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

    public function stages(): HasMany
    {
        return $this->hasMany(ConsultationStage::class)->orderBy('position');
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
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        return $this->stages()->where('accepts_observations', true)
            ->where('status', ConsultationStage::STATUS_ACTIVE)
            ->exists();
    }
}
