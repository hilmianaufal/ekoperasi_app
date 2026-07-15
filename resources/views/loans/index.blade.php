@extends('layouts.app')

@section('title', 'Pinjaman Anggota')
@section('page-title', 'Pinjaman Anggota')
@section('page-description', 'Kelola pengajuan dan pinjaman aktif anggota')

@section('content')

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Menunggu Persetujuan
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['pending'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                    <i data-lucide="clock-3" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Pinjaman Aktif
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['active'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="hand-coins" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Pinjaman Lunas
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['paid'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="badge-check" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Sisa Piutang
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['outstanding'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                    <i data-lucide="landmark" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-5 md:p-6">

            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Daftar Pinjaman
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Pengajuan dan riwayat pinjaman anggota.
                    </p>
                </div>

                <a
                    href="{{ route('loans.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="plus" class="h-5 w-5"></i>
                    Pengajuan Baru
                </a>

            </div>

            <form
                action="{{ route('loans.index') }}"
                method="GET"
                class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-5">

                <div class="relative xl:col-span-2">

                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i data-lucide="search" class="h-5 w-5"></i>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari nomor pinjaman atau anggota..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white">

                </div>

                <select
                    name="status"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua status</option>
                    <option value="pending" @selected($status === 'pending')>
                        Menunggu
                    </option>
                    <option value="active" @selected($status === 'active')>
                        Aktif
                    </option>
                    <option value="paid" @selected($status === 'paid')>
                        Lunas
                    </option>
                    <option value="rejected" @selected($status === 'rejected')>
                        Ditolak
                    </option>
                    <option value="cancelled" @selected($status === 'cancelled')>
                        Dibatalkan
                    </option>

                </select>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    title="Tanggal mulai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    title="Tanggal selesai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <div class="flex gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                        <i data-lucide="list-filter" class="h-5 w-5"></i>
                        Terapkan Filter
                    </button>

                    @if ($search || $status || $dateFrom || $dateTo)
                        <a
                            href="{{ route('loans.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                            <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                            Reset
                        </a>
                    @endif

                </div>

            </form>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Pinjaman</th>
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Nominal</th>
                        <th class="px-6 py-4">Tenor</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($loans as $loan)

                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-6 py-4">

                                <a
                                    href="{{ route('loans.show', $loan) }}"
                                    class="text-sm font-semibold text-slate-800 hover:text-emerald-600">

                                    {{ $loan->loan_number }}
                                </a>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $loan->application_date->translatedFormat('d M Y') }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $loan->member->name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $loan->member->member_number }}
                                </p>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4">

                                <p class="text-sm font-bold text-slate-800">
                                    Rp{{ number_format($loan->principal_amount, 0, ',', '.') }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Bunga {{ number_format($loan->interest_rate, 2, ',', '.') }}%
                                </p>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ $loan->tenor_months }} bulan
                            </td>

                            <td class="px-6 py-4">

                                @php
                                    $statusClass = match ($loan->status) {
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'active' => 'bg-blue-100 text-blue-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp

                                <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $loan->status_label }}
                                </span>

                            </td>

                            <td class="px-6 py-4">

                                <div class="flex justify-end">

                                    <a
                                        href="{{ route('loans.show', $loan) }}"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                        title="Detail">

                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="hand-coins" class="h-9 w-9"></i>
                                </div>

                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Belum ada pengajuan pinjaman
                                </h4>

                                <a
                                    href="{{ route('loans.create') }}"
                                    class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">

                                    <i data-lucide="plus" class="h-5 w-5"></i>
                                    Buat Pengajuan
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($loans->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $loans->links() }}
            </div>
        @endif

    </section>

@endsection
