<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMemberMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'source_number',
        'detected_names',
        'canonical_name',
        'normalized_name',
        'member_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'detected_names' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ImportBatch::class,
            'import_batch_id'
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'Anggota Baru',
            'matched' => 'Sudah Cocok',
            'review' => 'Perlu Diperiksa',
            'ignored' => 'Diabaikan',
            'imported' => 'Sudah Diimpor',
            default => ucfirst($this->status),
        };
    }
}
