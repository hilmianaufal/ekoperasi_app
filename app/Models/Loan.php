<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_number',
        'member_id',
        'created_by',
        'approved_by',
        'application_date',
        'principal_amount',
        'interest_rate',
        'tenor_months',
        'interest_amount',
        'total_amount',
        'monthly_installment',
        'start_date',
        'maturity_date',
        'purpose',
        'notes',
        'status',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'application_date' => 'date',
            'start_date' => 'date',
            'maturity_date' => 'date',
            'approved_at' => 'datetime',
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'monthly_installment' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)
            ->orderBy('installment_number');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'active' => 'Aktif',
            'rejected' => 'Ditolak',
            'paid' => 'Lunas',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}
