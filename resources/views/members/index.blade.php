@extends('layouts.app')

@section('title', 'Data Anggota')
@section('page-title', 'Data Anggota')
@section('page-description', 'Kelola seluruh anggota koperasi')

@section('content')

    <section class="grid gap-5 sm:grid-cols-3">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Anggota
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['total'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Anggota Aktif
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['active'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="user-check" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Tidak Aktif
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['inactive'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-slate-100 p-3 text-slate-500">
                    <i data-lucide="user-x" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-5 md:p-6">

            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Daftar Anggota
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Cari dan kelola data anggota koperasi.
                    </p>
                </div>

                <a
                    href="{{ route('members.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="user-plus" class="h-5 w-5"></i>
                    Tambah Anggota
                </a>

            </div>

            <form
                action="{{ route('members.index') }}"
                method="GET"
                class="mt-6 grid gap-3 lg:grid-cols-[1fr_220px_auto]">

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i data-lucide="search" class="h-5 w-5"></i>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari nomor, nama, telepon, atau email..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>

                <select
                    name="status"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                    <option value="">Semua status</option>

                    <option value="active" @selected($status === 'active')>
                        Aktif
                    </option>

                    <option value="inactive" @selected($status === 'inactive')>
                        Tidak Aktif
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                        <i data-lucide="list-filter" class="h-5 w-5"></i>
                        Filter
                    </button>

                    @if ($search || $status)
                        <a
                            href="{{ route('members.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 text-slate-500 hover:bg-slate-50"
                            title="Reset filter">

                            <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                        </a>
                    @endif
                </div>

            </form>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Kontak</th>
                        <th class="px-6 py-4">Bergabung</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($members as $member)

                        <tr class="hover:bg-slate-50/80">

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">

                                    @if ($member->photo)
                                        <img
                                            src="{{ asset('storage/' . $member->photo) }}"
                                            alt="{{ $member->name }}"
                                            class="h-12 w-12 shrink-0 rounded-2xl object-cover">
                                    @else
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 font-bold text-emerald-700">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                    @endif

                                    <div>
                                        <a
                                            href="{{ route('members.show', $member) }}"
                                            class="text-sm font-semibold text-slate-800 hover:text-emerald-600">

                                            {{ $member->name }}
                                        </a>

                                        <p class="mt-1 text-xs font-medium text-slate-400">
                                            {{ $member->member_number }}
                                        </p>
                                    </div>

                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $member->phone ?: '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $member->email ?: 'Email belum tersedia' }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $member->join_date->translatedFormat('d M Y') }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $member->gender_label }}
                                </p>
                            </td>

                            <td class="px-6 py-4">

                                @if ($member->status === 'active')
                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                        Tidak Aktif
                                    </span>
                                @endif

                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route('members.show', $member) }}"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                        title="Detail">

                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>

                                    <a
                                        href="{{ route('members.edit', $member) }}"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-600"
                                        title="Edit">

                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>

                                    <form
                                        action="{{ route('members.destroy', $member) }}"
                                        method="POST"
                                        id="delete-member-{{ $member->id }}">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="button"
                                            onclick="confirmDeleteMember(
                                                {{ $member->id }},
                                                @js($member->name)
                                            )"
                                            class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                            title="Hapus">

                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-16">

                                <div class="flex flex-col items-center text-center">

                                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i data-lucide="users-round" class="h-9 w-9"></i>
                                    </div>

                                    <h4 class="mt-5 font-semibold text-slate-700">
                                        Data anggota belum tersedia
                                    </h4>

                                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                        Tambahkan anggota baru untuk mulai mengelola data koperasi.
                                    </p>

                                    <a
                                        href="{{ route('members.create') }}"
                                        class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                                        <i data-lucide="user-plus" class="h-5 w-5"></i>
                                        Tambah Anggota
                                    </a>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($members->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $members->links() }}
            </div>
        @endif

    </section>

@endsection

@push('scripts')
    <script>
        function confirmDeleteMember(id, name) {
            Swal.fire({
                icon: 'warning',
                title: 'Hapus anggota?',
                html: `Data anggota <strong>${name}</strong> akan dihapus permanen.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document
                        .getElementById(`delete-member-${id}`)
                        .submit();
                }
            });
        }
    </script>
@endpush
