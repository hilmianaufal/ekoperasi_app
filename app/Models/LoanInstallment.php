<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'paid_amount',
        'paid_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            InstallmentPayment::class,
            'loan_installment_id'
        )
            ->latest('payment_date')
            ->latest('id');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(
            (float) $this->total_amount
                - (float) $this->paid_amount,
            0
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'unpaid' => 'Belum Dibayar',
            'partial' => 'Dibayar Sebagian',
            'paid' => 'Lunas',
            'overdue' => 'Terlambat',
            default => ucfirst($this->status),
        };
    }
}
