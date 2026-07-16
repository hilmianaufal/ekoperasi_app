<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingAccountMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'mapping_key',
        'accounting_account_id',
        'description',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            AccountingAccount::class,
            'accounting_account_id'
        );
    }
}
