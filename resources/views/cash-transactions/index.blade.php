@extends('layouts.app')

@section('title', 'Kas Koperasi')
@section('page-title', 'Kas Koperasi')
@section('page-description', 'Kelola pemasukan, pengeluaran, dan saldo kas')

@section('content')

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Saldo Kas
                    </p>

                    <h3 class="mt-2 text-xl font-bold {{ $statistics['balance'] >= 0 ? 'text-slate-900' : 'text-red-600' }}">
                        Rp{{ number_format($statistics['balance'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                    <i data-lucide="wallet" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Kas Masuk
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-emerald-600">
                        Rp{{ number_format($statistics['income'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="arrow-down-to-line" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Kas Keluar
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-red-600">
                        Rp{{ number_format($statistics['expense'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-red-100 p-3 text-red-600">
                    <i data-lucide="arrow-up-from-line" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Perubahan Hari Ini
                    </p>

                    <h3 class="mt-2 text-xl font-bold {{ $statistics['today'] >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        {{ $statistics['today'] >= 0 ? '+' : '-' }}
                        Rp{{ number_format(abs($statistics['today']), 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="calendar-days" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-5 md:p-6">

            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Buku Kas Koperasi
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Seluruh pemasukan dan pengeluaran koperasi.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">

                    <a href="{{ route('expenses.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-red-200 transition hover:bg-red-700">

                        <i data-lucide="circle-minus" class="h-5 w-5">
                        </i>

                        Tambah Pengeluaran
                    </a>

                    <a href="{{ route('cash-transactions.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">

                        <i data-lucide="circle-plus" class="h-5 w-5">
                        </i>

                        Transaksi Manual
                    </a>

                </div>

            </div>

            <form action="{{ route('cash-transactions.index') }}" method="GET"
                class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-6">

                <div class="relative xl:col-span-2">

                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i data-lucide="search" class="h-5 w-5"></i>
                    </div>

                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Cari kode, kategori, atau keterangan..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white">

                </div>

                <select name="direction"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua jenis</option>

                    <option value="income" @selected($direction === 'income')>
                        Kas Masuk
                    </option>

                    <option value="expense" @selected($direction === 'expense')>
                        Kas Keluar
                    </option>

                </select>

                <select name="category"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua kategori</option>

                    @foreach ($categories as $categoryOption)
                        <option value="{{ $categoryOption }}" @selected($category === $categoryOption)>

                            {{ $categoryOption }}
                        </option>
                    @endforeach

                </select>

                <input type="date" name="date_from" value="{{ $dateFrom }}" title="Tanggal mulai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <input type="date" name="date_to" value="{{ $dateTo }}" title="Tanggal selesai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <div class="flex gap-2 md:col-span-2 xl:col-span-6 xl:justify-end">

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                        <i data-lucide="list-filter" class="h-5 w-5"></i>
                        Filter
                    </button>

                    @if ($search || $direction || $category || $dateFrom || $dateTo)
                        <a href="{{ route('cash-transactions.index') }}"
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
                        <th class="px-6 py-4">Transaksi</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4">Sumber</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $transaction->transaction_code }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                                </p>

                                <p class="mt-1 text-[10px] text-slate-400">
                                    Oleh {{ $transaction->user?->name ?? 'Sistem' }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-700">
                                    {{ $transaction->category }}
                                </p>

                                @if ($transaction->direction === 'income')
                                    <span
                                        class="mt-2 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[10px] font-semibold text-emerald-700">
                                        Kas Masuk
                                    </span>
                                @else
                                    <span
                                        class="mt-2 inline-flex rounded-full bg-red-100 px-3 py-1 text-[10px] font-semibold text-red-700">
                                        Kas Keluar
                                    </span>
                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <p class="max-w-md text-sm leading-6 text-slate-600">
                                    {{ $transaction->description ?: '-' }}
                                </p>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4">

                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                    {{ $transaction->source_label }}
                                </span>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ $transaction->payment_method_label }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <p
                                    class="text-sm font-bold {{ $transaction->direction === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $transaction->direction === 'income' ? '+' : '-' }}
                                    Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                </p>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="px-6 py-16 text-center">

                                <div
                                    class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="wallet" class="h-9 w-9"></i>
                                </div>

                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Belum ada transaksi kas
                                </h4>

                                <p class="mt-2 text-sm text-slate-500">
                                    Tambahkan transaksi manual atau lakukan transaksi koperasi.
                                </p>

                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($transactions->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $transactions->links() }}
            </div>
        @endif

    </section>

@endsection
