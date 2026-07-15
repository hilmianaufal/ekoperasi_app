<?php

namespace App\Models;

use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_number',
        'name',
        'gender',
        'place_of_birth',
        'date_of_birth',
        'address',
        'phone',
        'email',
        'join_date',
        'status',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'join_date' => 'date',
        ];
    }

    public function getGenderLabelAttribute(): string
    {
        return $this->gender === 'male'
            ? 'Laki-laki'
            : 'Perempuan';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active'
            ? 'Aktif'
            : 'Tidak Aktif';
    }

    public function savingTransactions(): HasMany
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
