<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'data_import_batch_id',
        'original_name',
        'stored_path',
        'file_hash',
        'cutoff_date',
        'status',
        'sheet_count',
        'row_count',
        'imported_cash_count',
        'warnings',
        'error_message',
        'user_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_date' => 'date',
            'warnings' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (CashImportBatch $batch): void {
            if ($batch->code) {
                return;
            }

            $batch->updateQuietly([
                'code' => sprintf(
                    'KAS-IMP-%s-%06d',
                    now()->format('Ymd'),
                    $batch->id
                ),
            ]);
        });
    }

    public function dataImportBatch(): BelongsTo
    {
        return $this->belongsTo(
            ImportBatch::class,
            'data_import_batch_id'
        );
    }

    public function rows(): HasMany
    {
        return $this->hasMany(CashImportRow::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploaded' => 'File Diunggah',
            'previewed' => 'Siap Diproses',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}
