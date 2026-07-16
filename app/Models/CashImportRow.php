<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_import_batch_id',
        'sheet_name',
        'row_number',
        'period_date',
        'description',
        'income_code',
        'financing_expense',
        'principal_refund',
        'mandatory_refund',
        'voluntary_withdrawal',
        'transport_expense',
        'other_expense',
        'installment_income',
        'profit_share_income',
        'administration_income',
        'principal_deposit',
        'mandatory_deposit',
        'voluntary_deposit',
        'raw_data',
        'status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'financing_expense' => 'decimal:2',
            'principal_refund' => 'decimal:2',
            'mandatory_refund' => 'decimal:2',
            'voluntary_withdrawal' => 'decimal:2',
            'transport_expense' => 'decimal:2',
            'other_expense' => 'decimal:2',
            'installment_income' => 'decimal:2',
            'profit_share_income' => 'decimal:2',
            'administration_income' => 'decimal:2',
            'principal_deposit' => 'decimal:2',
            'mandatory_deposit' => 'decimal:2',
            'voluntary_deposit' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            CashImportBatch::class,
            'cash_import_batch_id'
        );
    }
}
