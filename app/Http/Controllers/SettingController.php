<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $setting = AppSetting::current();

        return view('settings.edit', compact('setting'));
    }

    public function update(
        Request $request
    ): RedirectResponse {
        $setting = AppSetting::current();

        $data = $request->validate([
            'cooperative_name' => [
                'required',
                'string',
                'max:150',
            ],
            'short_name' => [
                'required',
                'string',
                'max:50',
            ],
            'tagline' => [
                'nullable',
                'string',
                'max:200',
            ],
            'registration_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'chairman_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'treasurer_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'default_interest_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'default_tenor_months' => [
                'required',
                'integer',
                'min:1',
                'max:120',
            ],
            'minimum_loan_amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'maximum_loan_amount' => [
                'nullable',
                'numeric',
                'gte:minimum_loan_amount',
            ],
            'receipt_footer' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'timezone' => [
                'required',
                'in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
            ],
        ], [
            'cooperative_name.required' => 'Nama koperasi wajib diisi.',
            'short_name.required' => 'Nama singkat koperasi wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, PNG, atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'default_interest_rate.required' => 'Bunga pinjaman default wajib diisi.',
            'default_tenor_months.required' => 'Tenor default wajib diisi.',
            'maximum_loan_amount.gte' => 'Batas maksimal pinjaman tidak boleh lebih kecil dari batas minimal.',
        ]);

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')
                    ->delete($setting->logo);
            }

            $data['logo'] = $request
                ->file('logo')
                ->store('settings', 'public');
        }

        $setting->update($data);

        AppSetting::clearCache();

        return redirect()
            ->route('settings.edit')
            ->with(
                'success',
                'Pengaturan aplikasi berhasil diperbarui.'
            );
    }
}
