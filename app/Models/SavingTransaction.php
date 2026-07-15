<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'member_id',
        'saving_type_id',
        'user_id',
        'transaction_date',
        'transaction_type',
        'amount',
        'balance_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function savingType(): BelongsTo
    {
        return $this->belongsTo(SavingType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTransactionTypeLabelAttribute(): string
    {
        return $this->transaction_type === 'deposit'
            ? 'Setoran'
            : 'Penarikan';
    }
}
