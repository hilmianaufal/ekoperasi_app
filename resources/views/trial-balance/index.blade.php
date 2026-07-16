@extends('layouts.app')

@section('title', 'Neraca Saldo')
@section('page-title', 'Neraca Saldo')
@section('page-description', 'Ringkasan saldo debit dan kredit seluruh akun')

@push('styles')

    <style>
        @media print {
            body {
                background: white !important;
            }

            aside,
            header,
            nav,
            .no-print {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-report {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
            }

            table {
                font-size: 10px !important;
            }

            @page {
                size: landscape;
                margin: 10mm;
            }
        }
    </style>

@endpush

@section('content')

    <div class="no-print flex flex-col justify-between gap-4 lg:flex-row lg:items-center">

        <div>

            <h2 class="text-xl font-bold text-slate-900">
                Neraca Saldo
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Berdasarkan seluruh jurnal yang sudah diposting.
            </p>

        </div>

        <div class="flex flex-wrap gap-3">

            <a
                href="{{ route(
                    'trial-balance.export',
                    request()->query()
                ) }}"
                class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">

                <i data-lucide="file-spreadsheet" class="h-5 w-5"></i>
                Export CSV
            </a>

            <a
                href="{{ route(
                    'trial-balance.print',
                    request()->query()
                ) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                <i data-lucide="printer" class="h-5 w-5"></i>
                Cetak
            </a>

        </div>

    </div>

    {{-- FILTER --}}
    <section class="no-print mt-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            action="{{ route(
                'trial-balance.index'
            ) }}"
            method="GET"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Tanggal Awal
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom->toDateString() }}"
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Tanggal Akhir
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo->toDateString() }}"
                    required
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Kelompok Akun
                </label>

                <select
                    name="account_type"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    <option value="">
                        Semua kelompok
                    </option>

                    <option
                        value="asset"
                        @selected(
                            $accountType === 'asset'
                        )>

                        Aset
                    </option>

                    <option
                        value="liability"
                        @selected(
                            $accountType === 'liability'
                        )>

                        Liabilitas
                    </option>

                    <option
                        value="equity"
                        @selected(
                            $accountType === 'equity'
                        )>

                        Ekuitas
                    </option>

                    <option
                        value="revenue"
                        @selected(
                            $accountType === 'revenue'
                        )>

                        Pendapatan
                    </option>

                    <option
                        value="expense"
                        @selected(
                            $accountType === 'expense'
                        )>

                        Beban
                    </option>

                </select>

            </div>

            <div class="flex items-end">

                <label class="flex w-full items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">

                    <input
                        type="hidden"
                        name="show_zero"
                        value="0">

                    <input
                        type="checkbox"
                        name="show_zero"
                        value="1"
                        @checked($showZero)
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600">

                    <span class="text-sm font-medium text-slate-700">
                        Tampilkan akun nol
                    </span>

                </label>

            </div>

            <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-4">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                    <i data-lucide="search" class="h-4 w-4"></i>
                    Tampilkan
                </button>

                <a
                    href="{{ route(
                        'trial-balance.index'
                    ) }}"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    Reset
                </a>

            </div>

        </form>

    </section>

    {{-- HEADER CETAK --}}
    <section class="print-report mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-7 text-center">

            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">
                {{ config('app.name') }}
            </p>

            <h1 class="mt-2 text-2xl font-bold uppercase text-slate-900">
                Neraca Saldo
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Periode
                {{ $dateFrom->translatedFormat('d F Y') }}
                sampai
                {{ $dateTo->translatedFormat('d F Y') }}
            </p>

        </div>

        {{-- RINGKASAN --}}
        <div class="p-6">

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Akun Ditampilkan
                    </p>

                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ number_format(
                            $summary['account_count'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4">

                    <p class="text-xs text-blue-700">
                        Mutasi Debit
                    </p>

                    <p class="mt-2 text-xl font-bold text-blue-700">
                        Rp{{ number_format(
                            $summary['period_debit'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl border border-violet-200 bg-violet-50 p-4">

                    <p class="text-xs text-violet-700">
                        Mutasi Kredit
                    </p>

                    <p class="mt-2 text-xl font-bold text-violet-700">
                        Rp{{ number_format(
                            $summary['period_credit'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article
                    class="rounded-2xl border p-4
                        {{ $summary['is_balanced']
                            ? 'border-emerald-200 bg-emerald-50'
                            : 'border-red-200 bg-red-50' }}">

                    <p
                        class="text-xs
                            {{ $summary['is_balanced']
                                ? 'text-emerald-700'
                                : 'text-red-700' }}">

                        Status Neraca
                    </p>

                    <p
                        class="mt-2 text-xl font-bold
                            {{ $summary['is_balanced']
                                ? 'text-emerald-700'
                                : 'text-red-700' }}">

                        {{ $summary['is_balanced']
                            ? 'Seimbang'
                            : 'Tidak Seimbang' }}
                    </p>

                </article>

            </section>

            @if (!$summary['is_balanced'])

                <section class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-5">

                    <div class="flex items-start gap-3">

                        <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 text-red-600"></i>

                        <div>

                            <h3 class="font-bold text-red-800">
                                Neraca Saldo Tidak Seimbang
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-red-700">
                                Periksa jurnal yang belum seimbang, jurnal draft, atau jurnal yang belum diposting.
                            </p>

                        </div>

                    </div>

                </section>

            @endif

            {{-- TABEL --}}
            <div class="mt-7 overflow-x-auto">

                <table class="min-w-full border-collapse">

                    <thead>

                        <tr class="bg-slate-100 text-left text-xs font-bold uppercase text-slate-600">

                            <th
                                rowspan="2"
                                class="border border-slate-300 px-3 py-3">

                                Kode
                            </th>

                            <th
                                rowspan="2"
                                class="border border-slate-300 px-3 py-3">

                                Nama Akun
                            </th>

                            <th
                                rowspan="2"
                                class="border border-slate-300 px-3 py-3">

                                Kelompok
                            </th>

                            <th
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center">

                                Saldo Awal
                            </th>

                            <th
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center">

                                Mutasi Periode
                            </th>

                            <th
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center">

                                Saldo Akhir
                            </th>

                        </tr>

                        <tr class="bg-slate-50 text-xs font-bold uppercase text-slate-500">

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Debit
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Kredit
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Debit
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Kredit
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Debit
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Kredit
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($rows as $row)

                            @php
                                $typeClass = match (
                                    $row['type']
                                ) {
                                    'asset' =>
                                        'bg-blue-100 text-blue-700',

                                    'liability' =>
                                        'bg-amber-100 text-amber-700',

                                    'equity' =>
                                        'bg-violet-100 text-violet-700',

                                    'revenue' =>
                                        'bg-emerald-100 text-emerald-700',

                                    'expense' =>
                                        'bg-red-100 text-red-700',

                                    default =>
                                        'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <tr class="hover:bg-slate-50/70">

                                <td class="border border-slate-300 px-3 py-3 font-mono text-sm font-bold text-slate-700">
                                    {{ $row['code'] }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $row['name'] }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Saldo normal:
                                        {{ $row['normal_balance_label'] }}
                                    </p>

                                </td>

                                <td class="border border-slate-300 px-3 py-3">

                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $typeClass }}">
                                        {{ $row['type_label'] }}
                                    </span>

                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm">
                                    {{ $row['opening_debit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['opening_debit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm">
                                    {{ $row['opening_credit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['opening_credit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm text-blue-700">
                                    {{ $row['period_debit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['period_debit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm text-violet-700">
                                    {{ $row['period_credit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['period_credit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm font-bold text-blue-700">
                                    {{ $row['ending_debit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['ending_debit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm font-bold text-violet-700">
                                    {{ $row['ending_credit'] > 0
                                        ? 'Rp' . number_format(
                                            $row['ending_credit'],
                                            0,
                                            ',',
                                            '.'
                                        )
                                        : '-' }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="border border-slate-300 px-6 py-16 text-center text-sm text-slate-500">

                                    Belum ada jurnal yang diposting pada periode ini.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="bg-emerald-50 font-bold text-slate-900">

                            <td
                                colspan="3"
                                class="border border-slate-300 px-3 py-4 text-right">

                                TOTAL
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right">
                                Rp{{ number_format(
                                    $summary['opening_debit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right">
                                Rp{{ number_format(
                                    $summary['opening_credit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right text-blue-700">
                                Rp{{ number_format(
                                    $summary['period_debit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right text-violet-700">
                                Rp{{ number_format(
                                    $summary['period_credit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right text-blue-700">
                                Rp{{ number_format(
                                    $summary['ending_debit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-4 text-right text-violet-700">
                                Rp{{ number_format(
                                    $summary['ending_credit'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                        </tr>

                        <tr class="bg-slate-50 font-semibold">

                            <td
                                colspan="3"
                                class="border border-slate-300 px-3 py-3 text-right text-slate-600">

                                SELISIH
                            </td>

                            <td
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center
                                    {{ abs($summary['opening_difference']) < 0.01
                                        ? 'text-emerald-700'
                                        : 'text-red-600' }}">

                                Rp{{ number_format(
                                    $summary['opening_difference'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center
                                    {{ abs($summary['period_difference']) < 0.01
                                        ? 'text-emerald-700'
                                        : 'text-red-600' }}">

                                Rp{{ number_format(
                                    $summary['period_difference'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-center
                                    {{ abs($summary['ending_difference']) < 0.01
                                        ? 'text-emerald-700'
                                        : 'text-red-600' }}">

                                Rp{{ number_format(
                                    $summary['ending_difference'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

            <section class="mt-10 grid gap-10 sm:grid-cols-2">

                <div>

                    <p class="text-sm text-slate-500">
                        Mengetahui,
                    </p>

                    <div class="h-20"></div>

                    <p class="border-t border-slate-400 pt-2 text-sm font-semibold">
                        Ketua Koperasi
                    </p>

                </div>

                <div class="sm:text-right">

                    <p class="text-sm text-slate-500">
                        Dibuat oleh,
                    </p>

                    <div class="h-20"></div>

                    <p class="border-t border-slate-400 pt-2 text-sm font-semibold">
                        {{ auth()->user()?->name ?? 'Administrator' }}
                    </p>

                </div>

            </section>

        </div>

    </section>

@endsection

@if ($printMode)

    @push('scripts')

        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>

    @endpush

@endif
