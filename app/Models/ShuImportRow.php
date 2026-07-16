<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShuImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'shu_import_batch_id',
        'sheet_name',
        'row_number',
        'source_number',
        'source_name',
        'normalized_name',
        'member_id',
        'receivable_balance',
        'profit_share_base',
        'principal_saving',
        'mandatory_saving',
        'saving_balance',
        'source_business_service',
        'source_saving_service',
        'source_total_shu',
        'calculated_business_service',
        'calculated_saving_service',
        'calculated_total_shu',
        'difference',
        'status',
        'raw_data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receivable_balance' => 'decimal:2',
            'profit_share_base' => 'decimal:2',
            'principal_saving' => 'decimal:2',
            'mandatory_saving' => 'decimal:2',
            'saving_balance' => 'decimal:2',
            'source_business_service' => 'decimal:2',
            'source_saving_service' => 'decimal:2',
            'source_total_shu' => 'decimal:2',
            'calculated_business_service' => 'decimal:2',
            'calculated_saving_service' => 'decimal:2',
            'calculated_total_shu' => 'decimal:2',
            'difference' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            ShuImportBatch::class,
            'shu_import_batch_id'
        );
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
