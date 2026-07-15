<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_amount',
        'is_withdrawable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_withdrawable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingTransaction::class);
    }
}
