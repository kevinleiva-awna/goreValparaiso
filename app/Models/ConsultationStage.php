<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConsultationStage extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consultation_id', 'name', 'position',
                'starts_at', 'ends_at', 'accepts_observations', 'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('consultation_stage');
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'consultation_id',
        'name',
        'description',
        'position',
        'starts_at',
        'ends_at',
        'accepts_observations',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'accepts_observations' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ConsultationDocument::class, 'stage_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'stage_id');
    }

    public function isOpenForObservations(): bool
    {
        return $this->accepts_observations
            && $this->status === self::STATUS_ACTIVE
            && now()->between($this->starts_at, $this->ends_at);
    }
}
