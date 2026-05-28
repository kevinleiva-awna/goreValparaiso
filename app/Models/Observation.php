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
    public const AUTH_MANUAL = 'manual';
    public const AUTH_GUEST = 'guest';

    protected $fillable = [
        'public_id',
        'consultation_id',
        'stage_id',
        'user_id',
        'subject',
        'body',
        'category',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size_bytes',
        'auth_method_used',
        'snapshot_national_id',
        'snapshot_full_name',
        'snapshot_email',
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
