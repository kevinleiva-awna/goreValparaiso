<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ConsultationDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'consultation_id', 'stage_id', 'title',
                'original_filename', 'version', 'file_group_id', 'sha256',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('consultation_document');
    }

    protected $fillable = [
        'consultation_id',
        'stage_id',
        'title',
        'description',
        'original_filename',
        'mime_type',
        'size_bytes',
        'storage_path',
        'file_group_id',
        'version',
        'sha256',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $doc) {
            if (empty($doc->file_group_id)) {
                $doc->file_group_id = (string) Str::uuid();
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloadUrl(): string
    {
        return Storage::url($this->storage_path);
    }
}
