@extends('layouts.app')

@section('title', 'Simpanan Anggota')
@section('page-title', 'Simpanan Anggota')
@section('page-description', 'Kelola setoran dan penarikan simpanan')

@section('content')

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Saldo
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['balance'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="wallet" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Setoran
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['deposits'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="arrow-down-to-line" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Total Penarikan
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['withdrawals'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                    <i data-lucide="arrow-up-from-line" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Jumlah Transaksi
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['transactions'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                    <i data-lucide="receipt-text" class="h-6 w-6"></i>
                </div>

            </div>
        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-5 md:p-6">

            <div class="flex flex-col justify-between gap-4 xl:flex-row xl:items-center">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Riwayat Transaksi
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Seluruh transaksi simpanan anggota koperasi.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">

                    <a
                        href="{{ route('saving-types.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                        <i data-lucide="settings-2" class="h-5 w-5"></i>
                        Jenis Simpanan
                    </a>

                    <a
                        href="{{ route('savings.create') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                        <i data-lucide="plus" class="h-5 w-5"></i>
                        Transaksi Baru
                    </a>

                </div>

            </div>

            <form
                action="{{ route('savings.index') }}"
                method="GET"
                class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-6">

                <div class="relative md:col-span-2">

                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i data-lucide="search" class="h-5 w-5"></i>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari kode transaksi atau anggota..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>

                <select
                    name="saving_type_id"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua simpanan</option>

                    @foreach ($savingTypes as $savingType)
                        <option
                            value="{{ $savingType->id }}"
                            @selected((string) $savingTypeId === (string) $savingType->id)>

                            {{ $savingType->name }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="transaction_type"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua transaksi</option>
                    <option value="deposit" @selected($transactionType === 'deposit')>
                        Setoran
                    </option>
                    <option value="withdrawal" @selected($transactionType === 'withdrawal')>
                        Penarikan
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

                <div class="flex gap-2 md:col-span-2 xl:col-span-6 xl:justify-end">

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                        <i data-lucide="list-filter" class="h-5 w-5"></i>
                        Terapkan Filter
                    </button>

                    @if ($search || $savingTypeId || $transactionType || $dateFrom || $dateTo)
                        <a
                            href="{{ route('savings.index') }}"
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
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Jenis Simpanan</th>
                        <th class="px-6 py-4">Tipe</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                        <th class="px-6 py-4 text-right">Saldo Akhir</th>
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
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $transaction->member->name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $transaction->member->member_number }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $transaction->savingType->name }}
                                </p>

                                <p class="mt-1 text-xs font-medium text-emerald-600">
                                    {{ $transaction->savingType->code }}
                                </p>
                            </td>

                            <td class="px-6 py-4">

                                @if ($transaction->transaction_type === 'deposit')
                                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                        <i data-lucide="arrow-down-to-line" class="h-3.5 w-3.5"></i>
                                        Setoran
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700">
                                        <i data-lucide="arrow-up-from-line" class="h-3.5 w-3.5"></i>
                                        Penarikan
                                    </span>
                                @endif

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <p class="text-sm font-bold {{ $transaction->transaction_type === 'deposit' ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}
                                    Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <p class="text-sm font-semibold text-slate-800">
                                    Rp{{ number_format($transaction->balance_after, 0, ',', '.') }}
                                </p>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="receipt-text" class="h-9 w-9"></i>
                                </div>

                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Belum ada transaksi simpanan
                                </h4>

                                <a
                                    href="{{ route('savings.create') }}"
                                    class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">

                                    <i data-lucide="plus" class="h-5 w-5"></i>
                                    Buat Transaksi
                                </a>

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
