<?php

namespace App\Http\Controllers;

use App\Models\SavingType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SavingTypeController extends Controller
{
    public function index(): View
    {
        $savingTypes = SavingType::query()
            ->withCount('transactions')
            ->latest()
            ->paginate(10);

        return view('saving-types.index', compact('savingTypes'));
    }

    public function create(): View
    {
        return view('saving-types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $this->validateSavingType($request);

        $data['is_withdrawable'] = $request->boolean('is_withdrawable');
        $data['is_active'] = $request->boolean('is_active');

        SavingType::create($data);

        return redirect()
            ->route('saving-types.index')
            ->with('success', 'Jenis simpanan berhasil ditambahkan.');
    }

    public function edit(SavingType $savingType): View
    {
        return view('saving-types.edit', compact('savingType'));
    }

    public function update(
        Request $request,
        SavingType $savingType
    ): RedirectResponse {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $data = $this->validateSavingType($request, $savingType);

        $data['is_withdrawable'] = $request->boolean('is_withdrawable');
        $data['is_active'] = $request->boolean('is_active');

        $savingType->update($data);

        return redirect()
            ->route('saving-types.index')
            ->with('success', 'Jenis simpanan berhasil diperbarui.');
    }

    public function destroy(SavingType $savingType): RedirectResponse
    {
        if ($savingType->transactions()->exists()) {
            return back()->with(
                'error',
                'Jenis simpanan tidak dapat dihapus karena sudah memiliki transaksi.'
            );
        }

        $savingType->delete();

        return redirect()
            ->route('saving-types.index')
            ->with('success', 'Jenis simpanan berhasil dihapus.');
    }

    private function validateSavingType(
        Request $request,
        ?SavingType $savingType = null
    ): array {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('saving_types', 'code')
                    ->ignore($savingType?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_amount' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Nama jenis simpanan wajib diisi.',
            'code.required' => 'Kode jenis simpanan wajib diisi.',
            'code.unique' => 'Kode jenis simpanan sudah digunakan.',
            'code.alpha_dash' => 'Kode hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.',
            'default_amount.required' => 'Nominal default wajib diisi.',
            'default_amount.numeric' => 'Nominal default harus berupa angka.',
        ]);
    }
}
