<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShuPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'year',
        'calculation_date',
        'business_service_rate',
        'saving_service_rate',
        'declared_total_shu',
        'declared_member_shu',
        'declared_business_service',
        'declared_saving_service',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'calculation_date' => 'date',
            'business_service_rate' => 'decimal:4',
            'saving_service_rate' => 'decimal:4',
            'declared_total_shu' => 'decimal:2',
            'declared_member_shu' => 'decimal:2',
            'declared_business_service' => 'decimal:2',
            'declared_saving_service' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ShuPeriod $period): void {
            if ($period->code) {
                return;
            }

            $period->updateQuietly([
                'code' => sprintf(
                    'SHU-%d-%04d',
                    $period->year,
                    $period->id
                ),
            ]);
        });
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ShuImportBatch::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            ShuMemberAllocation::class
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'review' => 'Perlu Rekonsiliasi',
            'approved' => 'Disetujui',
            'distributed' => 'Sudah Dibagikan',
            default => ucfirst($this->status),
        };
    }
}
