<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanImportEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'import_row_id',
        'loan_id',
        'member_id',
        'period_date',
        'opening_principal',
        'new_financing',
        'principal_payment',
        'profit_share',
        'administration_fee',
        'reported_remaining',
        'calculated_remaining',
        'balance_adjustment',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'opening_principal' => 'decimal:2',
            'new_financing' => 'decimal:2',
            'principal_payment' => 'decimal:2',
            'profit_share' => 'decimal:2',
            'administration_fee' => 'decimal:2',
            'reported_remaining' => 'decimal:2',
            'calculated_remaining' => 'decimal:2',
            'balance_adjustment' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ImportBatch::class,
            'import_batch_id'
        );
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(
            ImportRow::class
        );
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
