@extends('layouts.app')

@section('title', 'Pengeluaran Koperasi')
@section('page-title', 'Pengeluaran Koperasi')
@section('page-description', 'Kelola biaya operasional dan pengeluaran koperasi')

@section('content')

    <div class="space-y-7">

        {{-- Statistik --}}
        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Total Pengeluaran
                        </p>

                        <h3 class="mt-2 text-xl font-bold text-slate-900">
                            Rp{{ number_format(
                                $statistics['total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </h3>

                    </div>

                    <div class="rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="wallet-cards" class="h-6 w-6"></i>
                    </div>

                </div>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Bulan Ini
                        </p>

                        <h3 class="mt-2 text-xl font-bold text-slate-900">
                            Rp{{ number_format(
                                $statistics['this_month'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </h3>

                    </div>

                    <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                        <i data-lucide="calendar-days" class="h-6 w-6"></i>
                    </div>

                </div>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Hari Ini
                        </p>

                        <h3 class="mt-2 text-xl font-bold text-slate-900">
                            Rp{{ number_format(
                                $statistics['today'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </h3>

                    </div>

                    <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                        <i data-lucide="calendar-check" class="h-6 w-6"></i>
                    </div>

                </div>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <p class="text-sm font-medium text-slate-500">
                            Jumlah Transaksi
                        </p>

                        <h3 class="mt-2 text-3xl font-bold text-slate-900">
                            {{ number_format(
                                $statistics['transaction_count'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </h3>

                    </div>

                    <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                        <i data-lucide="receipt-text" class="h-6 w-6"></i>
                    </div>

                </div>

            </article>

        </section>

        {{-- Daftar pengeluaran --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-5 md:p-6">

                <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-center">

                    <div>

                        <h2 class="font-bold text-slate-900">
                            Riwayat Pengeluaran
                        </h2>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Gaji, listrik, air, internet, iuran, ATK, transportasi, dan biaya operasional lainnya.
                        </p>

                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">

                        <a
                            href="{{ route('cash-transactions.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">

                            <i data-lucide="book-open" class="h-5 w-5"></i>

                            Buku Kas
                        </a>

                        <a
                            href="{{ route('expenses.create') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-red-200 transition hover:bg-red-700">

                            <i data-lucide="circle-plus" class="h-5 w-5"></i>

                            Tambah Pengeluaran
                        </a>

                    </div>

                </div>

                {{-- Filter --}}
                <form
                    action="{{ route('expenses.index') }}"
                    method="GET"
                    class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">

                    <div class="xl:col-span-2">

                        <label
                            for="search"
                            class="mb-2 block text-xs font-semibold text-slate-600">

                            Pencarian
                        </label>

                        <div class="relative">

                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            </i>

                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ $search }}"
                                placeholder="Kode, kategori, atau keterangan"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                        </div>

                    </div>

                    <div>

                        <label
                            for="category"
                            class="mb-2 block text-xs font-semibold text-slate-600">

                            Kategori
                        </label>

                        <select
                            name="category"
                            id="category"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                            <option value="">
                                Semua kategori
                            </option>

                            @foreach ($categories as $categoryOption)

                                <option
                                    value="{{ $categoryOption }}"
                                    @selected(
                                        $category === $categoryOption
                                    )>

                                    {{ $categoryOption }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label
                            for="date_from"
                            class="mb-2 block text-xs font-semibold text-slate-600">

                            Dari tanggal
                        </label>

                        <input
                            type="date"
                            name="date_from"
                            id="date_from"
                            value="{{ $dateFrom }}"
                            max="{{ now()->format('Y-m-d') }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                    </div>

                    <div>

                        <label
                            for="date_to"
                            class="mb-2 block text-xs font-semibold text-slate-600">

                            Sampai tanggal
                        </label>

                        <input
                            type="date"
                            name="date_to"
                            id="date_to"
                            value="{{ $dateTo }}"
                            max="{{ now()->format('Y-m-d') }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                    </div>

                    <div class="flex flex-col gap-3 md:flex-row xl:col-span-5 xl:justify-end">

                        @if (
                            $search
                            || $category
                            || $dateFrom
                            || $dateTo
                        )

                            <a
                                href="{{ route('expenses.index') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">

                                <i data-lucide="rotate-ccw" class="h-4 w-4"></i>

                                Reset
                            </a>

                        @endif

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">

                            <i data-lucide="search" class="h-4 w-4"></i>

                            Terapkan Filter
                        </button>

                    </div>

                </form>

            </div>

            {{-- Tabel --}}
            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">

                            <th class="px-6 py-4">
                                Transaksi
                            </th>

                            <th class="px-6 py-4">
                                Kategori
                            </th>

                            <th class="px-6 py-4">
                                Keterangan
                            </th>

                            <th class="px-6 py-4">
                                Metode
                            </th>

                            <th class="px-6 py-4">
                                Petugas
                            </th>

                            <th class="px-6 py-4 text-right">
                                Nominal
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($expenses as $expense)

                            <tr class="transition hover:bg-slate-50">

                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $expense->transaction_code }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $expense
                                            ->transaction_date
                                            ->translatedFormat('d M Y') }}
                                    </p>

                                </td>

                                <td class="px-6 py-4">

                                    @php
                                        $categoryClass = match (
                                            $expense->category
                                        ) {
                                            'Gaji Karyawan' =>
                                                'bg-violet-100 text-violet-700',

                                            'Listrik',
                                            'Air',
                                            'Internet' =>
                                                'bg-blue-100 text-blue-700',

                                            'Transportasi' =>
                                                'bg-amber-100 text-amber-700',

                                            'ATK',
                                            'Pemeliharaan' =>
                                                'bg-emerald-100 text-emerald-700',

                                            default =>
                                                'bg-slate-100 text-slate-700',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $categoryClass }}">

                                        {{ $expense->category }}

                                    </span>

                                </td>

                                <td class="max-w-sm px-6 py-4">

                                    <p class="line-clamp-2 text-sm leading-6 text-slate-700">
                                        {{ $expense->description }}
                                    </p>

                                </td>

                                <td class="px-6 py-4">

                                    @php
                                        $methodClass = match (
                                            $expense->payment_method
                                        ) {
                                            'cash' =>
                                                'bg-emerald-50 text-emerald-700',

                                            'transfer' =>
                                                'bg-blue-50 text-blue-700',

                                            default =>
                                                'bg-slate-100 text-slate-700',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold {{ $methodClass }}">

                                        @if ($expense->payment_method === 'cash')

                                            <i data-lucide="banknote" class="h-3.5 w-3.5"></i>

                                        @elseif ($expense->payment_method === 'transfer')

                                            <i data-lucide="landmark" class="h-3.5 w-3.5"></i>

                                        @else

                                            <i data-lucide="wallet" class="h-3.5 w-3.5"></i>

                                        @endif

                                        {{ $expense->payment_method_label }}

                                    </span>

                                </td>

                                <td class="px-6 py-4">

                                    <p class="text-sm font-medium text-slate-700">
                                        {{ $expense->user?->name ?? '-' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $expense->created_at?->format('H:i') }}
                                    </p>

                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <p class="text-sm font-bold text-red-600">
                                        - Rp{{ number_format(
                                            $expense->amount,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                                        <i data-lucide="receipt-text" class="h-7 w-7"></i>

                                    </div>

                                    <h3 class="mt-4 font-bold text-slate-800">
                                        Belum ada pengeluaran
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-500">

                                        @if (
                                            $search
                                            || $category
                                            || $dateFrom
                                            || $dateTo
                                        )

                                            Tidak ada data yang sesuai dengan filter.

                                        @else

                                            Catat biaya operasional koperasi melalui tombol tambah pengeluaran.

                                        @endif

                                    </p>

                                    @if (
                                        !$search
                                        && !$category
                                        && !$dateFrom
                                        && !$dateTo
                                    )

                                        <a
                                            href="{{ route('expenses.create') }}"
                                            class="mt-5 inline-flex items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700">

                                            <i data-lucide="circle-plus" class="h-5 w-5"></i>

                                            Tambah Pengeluaran
                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if ($expenses->hasPages())

                <div class="border-t border-slate-200 p-5 md:p-6">
                    {{ $expenses->links() }}
                </div>

            @endif

        </section>

    </div>

@endsection
