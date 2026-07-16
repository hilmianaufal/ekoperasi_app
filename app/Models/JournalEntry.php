<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'reference_number',
        'description',
        'source_type',
        'source_id',
        'status',
        'reversal_of_id',
        'created_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (
            JournalEntry $entry
        ): void {
            if ($entry->entry_number) {
                return;
            }

            $entry->updateQuietly([
                'entry_number' => sprintf(
                    'JU-%s-%06d',
                    $entry->entry_date
                        ->format('Ymd'),
                    $entry->id
                ),
            ]);
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(
            JournalEntryLine::class
        )->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(
            JournalEntry::class,
            'reversal_of_id'
        );
    }

    public function getTotalDebitAttribute(): float
    {
        if ($this->relationLoaded('lines')) {
            return round(
                (float) $this->lines->sum('debit'),
                2
            );
        }

        return round(
            (float) $this->lines()->sum('debit'),
            2
        );
    }

    public function getTotalCreditAttribute(): float
    {
        if ($this->relationLoaded('lines')) {
            return round(
                (float) $this->lines->sum('credit'),
                2
            );
        }

        return round(
            (float) $this->lines()->sum('credit'),
            2
        );
    }

    public function getIsBalancedAttribute(): bool
    {
        return abs(
            $this->total_debit
                - $this->total_credit
        ) < 0.01;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'posted' => 'Sudah Diposting',
            'reversed' => 'Dibalik',
            default => ucfirst($this->status),
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'saving_transaction' =>
            'Transaksi Simpanan',

            'loan_disbursement' =>
            'Pencairan Pembiayaan',

            'installment_payment' =>
            'Pembayaran Angsuran',

            'shu_payment' =>
            'Pembayaran SHU',
            'journal_reversal' =>
            'Jurnal Pembalik',
            'cash_transaction' =>
            'Transaksi Kas',

            'shu_allocation' =>
            'Pengakuan SHU Anggota',

            'shu_payment' =>
            'Pembayaran SHU',

            'opening_balance' =>
            'Saldo Awal',

            'adjustment' =>
            'Jurnal Penyesuaian',

            null => 'Jurnal Manual',

            default =>
            ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $this->source_type
                )
            ),
        };
    }
}
