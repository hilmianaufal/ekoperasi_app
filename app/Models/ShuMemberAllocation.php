<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShuMemberAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shu_period_id',
        'shu_import_row_id',
        'member_id',
        'source_number',
        'receivable_balance',
        'profit_share_base',
        'principal_saving',
        'mandatory_saving',
        'saving_balance',
        'business_service_amount',
        'saving_service_amount',
        'total_shu',
        'paid_amount',
        'payment_status',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receivable_balance' => 'decimal:2',
            'profit_share_base' => 'decimal:2',
            'principal_saving' => 'decimal:2',
            'mandatory_saving' => 'decimal:2',
            'saving_balance' => 'decimal:2',
            'business_service_amount' => 'decimal:2',
            'saving_service_amount' => 'decimal:2',
            'total_shu' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            ShuPeriod::class,
            'shu_period_id'
        );
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(
            ShuImportRow::class,
            'shu_import_row_id'
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ShuPayment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(
            (float) $this->total_shu
                - (float) $this->paid_amount,
            0
        );
    }
}
