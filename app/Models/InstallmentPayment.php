<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_code',
        'loan_installment_id',
        'user_id',
        'payment_date',
        'amount',
        'remaining_after',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'remaining_after' => 'decimal:2',
        ];
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(
            LoanInstallment::class,
            'loan_installment_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'other' => 'Lainnya',
            default => ucfirst($this->payment_method),
        };
    }
}
