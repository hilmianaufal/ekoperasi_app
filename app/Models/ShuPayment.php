<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shu_member_allocation_id',
        'payment_code',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ShuPayment $payment): void {
            if ($payment->payment_code) {
                return;
            }

            $payment->updateQuietly([
                'payment_code' => sprintf(
                    'BYR-SHU-%s-%06d',
                    $payment->payment_date->format('Ymd'),
                    $payment->id
                ),
            ]);
        });
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(
            ShuMemberAllocation::class,
            'shu_member_allocation_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
