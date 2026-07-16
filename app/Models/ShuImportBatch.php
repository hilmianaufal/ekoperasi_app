<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShuImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'shu_period_id',
        'code',
        'original_name',
        'stored_path',
        'file_hash',
        'status',
        'row_count',
        'matched_count',
        'review_count',
        'imported_count',
        'warnings',
        'error_message',
        'user_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'warnings' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ShuImportBatch $batch): void {
            if ($batch->code) {
                return;
            }

            $batch->updateQuietly([
                'code' => sprintf(
                    'SHU-IMP-%s-%06d',
                    now()->format('Ymd'),
                    $batch->id
                ),
            ]);
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            ShuPeriod::class,
            'shu_period_id'
        );
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ShuImportRow::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploaded' => 'File Diunggah',
            'previewed' => 'Siap Diperiksa',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}
