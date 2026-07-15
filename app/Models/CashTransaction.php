<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'transaction_date',
        'direction',
        'category',
        'amount',
        'payment_method',
        'description',
        'source_type',
        'source_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (CashTransaction $transaction): void {
            if ($transaction->transaction_code) {
                return;
            }

            $transaction->updateQuietly([
                'transaction_code' => sprintf(
                    'KAS-%s-%06d',
                    $transaction->transaction_date->format('Ymd'),
                    $transaction->id
                ),
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('direction', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('direction', 'expense');
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'income'
            ? 'Kas Masuk'
            : 'Kas Keluar';
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

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'saving_transaction' => 'Transaksi Simpanan',
            'loan_disbursement' => 'Pencairan Pinjaman',
            'installment_payment' => 'Pembayaran Angsuran',
            default => 'Transaksi Manual',
        };
    }
}
