<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooperative_name',
        'short_name',
        'tagline',
        'registration_number',
        'address',
        'phone',
        'email',
        'chairman_name',
        'treasurer_name',
        'logo',
        'default_interest_rate',
        'default_tenor_months',
        'minimum_loan_amount',
        'maximum_loan_amount',
        'receipt_footer',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'default_interest_rate' => 'decimal:2',
            'default_tenor_months' => 'integer',
            'minimum_loan_amount' => 'decimal:2',
            'maximum_loan_amount' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return Cache::rememberForever(
            'app_settings',
            function (): self {
                return self::query()->firstOrCreate(
                    ['id' => 1],
                    [
                        'cooperative_name' => 'e-Koperasi',
                        'short_name' => 'e-Koperasi',
                        'tagline' => 'Sistem Manajemen Koperasi',
                        'default_interest_rate' => 1.5,
                        'default_tenor_months' => 10,
                        'minimum_loan_amount' => 100000,
                        'timezone' => 'Asia/Jakarta',
                    ]
                );
            }
        );
    }

    public static function clearCache(): void
    {
        Cache::forget('app_settings');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        return asset('storage/' . $this->logo);
    }
}
