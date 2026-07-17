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
        'import_batch_id',
        'source_number',
        'is_legacy',
        'opening_principal',
        'disbursed_during_import',
        'outstanding_principal',
        'profit_share_paid',
        'administration_paid',
        'administration_fee',
        'administration_collection_method',
        'administration_payment_method',
        'administration_collected_at',
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
            'is_legacy' => 'boolean',
            'opening_principal' => 'decimal:2',
            'disbursed_during_import' => 'decimal:2',
            'outstanding_principal' => 'decimal:2',
            'profit_share_paid' => 'decimal:2',
            'administration_paid' => 'decimal:2',
            'administration_fee' => 'decimal:2',
            'administration_collected_at' => 'datetime',
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


    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function importEntries(): HasMany
    {
        return $this->hasMany(LoanImportEntry::class);
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

    public function getAdministrationCollectionMethodLabelAttribute(): string
    {
        return match ($this->administration_collection_method) {
            'deducted' => 'Dipotong dari pencairan',
            'separate' => 'Dibayar terpisah',
            default => '-',
        };
    }

    public function getAdministrationPaymentMethodLabelAttribute(): string
    {
        return match ($this->administration_payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'other' => 'Lainnya',
            default => '-',
        };
    }

    public function getNetDisbursementAmountAttribute(): float
    {
        $principal = (float) $this->principal_amount;
        $administration = (float) $this->administration_fee;

        if (
            $this->administration_collection_method
            === 'deducted'
        ) {
            return max(
                round(
                    $principal - $administration,
                    2
                ),
                0
            );
        }

        return round(
            $principal,
            2
        );
    }
}
