<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $members = Member::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('member_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn($query) => $query->where('status', $status)
            )
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $statistics = [
            'total' => Member::count(),
            'active' => Member::where('status', 'active')->count(),
            'inactive' => Member::where('status', 'inactive')->count(),
        ];

        return view('members.index', compact(
            'members',
            'statistics',
            'search',
            'status'
        ));
    }

    public function create(): View
    {
        return view('members.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMember($request);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request
                ->file('photo')
                ->store('members', 'public');
        }

        $member = Member::create($data);

        $member->update([
            'member_number' => sprintf(
                'AGT-%s-%05d',
                now()->format('Y'),
                $member->id
            ),
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', 'Data anggota berhasil ditambahkan.');
    }

    public function show(Member $member): View
    {
        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $data = $this->validateMember($request, $member);

        if ($request->hasFile('photo')) {
            if ($member->photo) {
                Storage::disk('public')->delete($member->photo);
            }

            $data['photo'] = $request
                ->file('photo')
                ->store('members', 'public');
        }

        $member->update($data);

        return redirect()
            ->route('members.index')
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        if ($member->savingTransactions()->exists()) {
            return back()->with(
                'error',
                'Anggota tidak dapat dihapus karena sudah memiliki transaksi simpanan.'
            );
        }

        if ($member->loans()->exists()) {
            return back()->with(
                'error',
                'Anggota tidak dapat dihapus karena sudah memiliki data pinjaman.'
            );
        }

        if ($member->photo) {
            Storage::disk('public')->delete($member->photo);
        }

        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Data anggota berhasil dihapus.');
    }

    private function validateMember(
        Request $request,
        ?Member $member = null
    ): array {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:25'],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('members', 'email')->ignore($member?->id),
            ],
            'join_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'name.required' => 'Nama anggota wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'join_date.required' => 'Tanggal bergabung wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan anggota lain.',
            'date_of_birth.before_or_equal' => 'Tanggal lahir tidak valid.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);
    }
}
