<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'sheet_name',
        'row_number',
        'period_date',
        'source_number',
        'source_name',
        'normalized_name',
        'canonical_name',
        'principal_saving',
        'mandatory_saving',
        'mandatory_balance',
        'principal_installment',
        'profit_share',
        'accumulated_profit_share',
        'remaining_financing',
        'voluntary_saving',
        'voluntary_balance',
        'voluntary_withdrawal',
        'administration_fee',
        'new_financing',
        'raw_data',
        'status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'principal_saving' => 'decimal:2',
            'mandatory_saving' => 'decimal:2',
            'mandatory_balance' => 'decimal:2',
            'principal_installment' => 'decimal:2',
            'profit_share' => 'decimal:2',
            'accumulated_profit_share' => 'decimal:2',
            'remaining_financing' => 'decimal:2',
            'voluntary_saving' => 'decimal:2',
            'voluntary_balance' => 'decimal:2',
            'voluntary_withdrawal' => 'decimal:2',
            'administration_fee' => 'decimal:2',
            'new_financing' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ImportBatch::class,
            'import_batch_id'
        );
    }
}
