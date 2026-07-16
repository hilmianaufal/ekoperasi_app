<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialStatementPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'report_date',
        'opening_cash_balance',
        'manual_balances',
        'reference_balances',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',

            'opening_cash_balance' =>
                'decimal:2',

            'manual_balances' => 'array',
            'reference_balances' => 'array',

            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (
            FinancialStatementPeriod $period
        ): void {
            if ($period->code) {
                return;
            }

            $period->updateQuietly([
                'code' => sprintf(
                    'LK-%s-%04d',
                    $period->report_date
                        ->format('Ymd'),
                    $period->id
                ),
            ]);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
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
            default => ucfirst($this->status),
        };
    }
}
