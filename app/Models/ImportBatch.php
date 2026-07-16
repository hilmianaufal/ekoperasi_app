<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'original_name',
        'stored_path',
        'file_hash',
        'cutoff_date',
        'status',
        'sheet_count',
        'row_count',
        'member_count',
        'imported_member_count',
        'imported_saving_count',
        'imported_loan_count',
        'warnings',
        'error_message',
        'user_id',
        'members_savings_imported_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_date' => 'date',
            'warnings' => 'array',
            'completed_at' => 'datetime',
            'members_savings_imported_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ImportBatch $batch): void {
            if ($batch->code) {
                return;
            }

            $batch->updateQuietly([
                'code' => sprintf(
                    'IMP-%s-%06d',
                    now()->format('Ymd'),
                    $batch->id
                ),
            ]);
        });
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ImportMemberMapping::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        if (
            $this->members_savings_imported_at
            && $this->status !== 'completed'
        ) {
            return 'Anggota & Simpanan Selesai';
        }

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
