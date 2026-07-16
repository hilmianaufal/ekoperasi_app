<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_id',
        'is_header',
        'is_active',
        'allow_manual_entries',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_header' => 'boolean',
            'is_active' => 'boolean',
            'allow_manual_entries' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            AccountingAccount::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            AccountingAccount::class,
            'parent_id'
        )->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(
            JournalEntryLine::class
        );
    }

    public function mapping(): HasOne
    {
        return $this->hasOne(
            AccountingAccountMapping::class
        );
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'asset' => 'Aset',
            'liability' => 'Liabilitas',
            'equity' => 'Ekuitas',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban',
            default => ucfirst($this->type),
        };
    }

    public function getNormalBalanceLabelAttribute(): string
    {
        return $this->normal_balance === 'debit'
            ? 'Debit'
            : 'Kredit';
    }
}
