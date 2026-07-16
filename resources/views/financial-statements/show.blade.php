@extends('layouts.app')

@section('title', 'Laporan Posisi Keuangan')
@section('page-title', 'Laporan Posisi Keuangan')
@section('page-description', 'Rekonsiliasi data aplikasi dengan laporan client')

@section('content')

    <a
        href="{{ route('financial-statements.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke laporan keuangan
    </a>

    <section class="mt-6 rounded-3xl bg-gradient-to-br from-blue-700 to-slate-950 p-7 text-white shadow-lg">

        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">
            {{ $period->code }}
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Laporan Posisi Keuangan
        </h1>

        <p class="mt-2 text-sm text-blue-100">
            Per {{ $report_date->translatedFormat('d F Y') }}
        </p>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5">

            <p class="text-sm text-blue-700">
                Total Aset Aplikasi
            </p>

            <p class="mt-2 text-xl font-bold text-blue-700">
                Rp{{ number_format(
                    $summary['total_assets'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-violet-200 bg-violet-50 p-5">

            <p class="text-sm text-violet-700">
                Liabilitas dan Ekuitas
            </p>

            <p class="mt-2 text-xl font-bold text-violet-700">
                Rp{{ number_format(
                    $summary['total_liabilities_equity'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border {{ abs($summary['balance_difference']) < 0.01 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-5">

            <p class="text-sm {{ abs($summary['balance_difference']) < 0.01 ? 'text-emerald-700' : 'text-red-700' }}">
                Selisih Neraca
            </p>

            <p class="mt-2 text-xl font-bold {{ abs($summary['balance_difference']) < 0.01 ? 'text-emerald-700' : 'text-red-700' }}">
                Rp{{ number_format(
                    $summary['balance_difference'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5">

            <p class="text-sm text-amber-700">
                Akun Berbeda
            </p>

            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ $summary['difference_count'] }}
            </p>

        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

        <div class="grid gap-4 sm:grid-cols-3">

            <div>

                <p class="text-xs text-slate-500">
                    Saldo Awal Kas
                </p>

                <p class="mt-1 font-bold text-slate-900">
                    Rp{{ number_format(
                        $summary['opening_cash'],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>

            </div>

            <div>

                <p class="text-xs text-slate-500">
                    Pergerakan Kas
                </p>

                <p class="mt-1 font-bold {{ $summary['cash_movement'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                    Rp{{ number_format(
                        $summary['cash_movement'],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>

            </div>

            <div>

                <p class="text-xs text-slate-500">
                    Saldo Kas Aplikasi
                </p>

                <p class="mt-1 font-bold text-blue-700">
                    Rp{{ number_format(
                        $application['cash'],
                        0,
                        ',',
                        '.'
                    ) }}
                </p>

            </div>

        </div>

    </section>

    @foreach ([
        'assets' => 'ASET',
        'liabilities' => 'LIABILITAS',
        'equity' => 'EKUITAS',
        'summary' => 'TOTAL LIABILITAS DAN EKUITAS',
    ] as $sectionKey => $sectionLabel)

        @php
            $sectionAccounts = collect($accounts)
                ->where('section', $sectionKey);
        @endphp

        @if ($sectionAccounts->isNotEmpty())

            <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 p-5">

                    <h3 class="font-bold text-slate-900">
                        {{ $sectionLabel }}
                    </h3>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead>

                            <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                                <th class="px-6 py-4">Akun</th>
                                <th class="px-6 py-4">Sumber</th>
                                <th class="px-6 py-4 text-right">Aplikasi</th>
                                <th class="px-6 py-4 text-right">Laporan Client</th>
                                <th class="px-6 py-4 text-right">Selisih</th>
                                <th class="px-6 py-4 text-center">Status</th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach ($sectionAccounts as $account)

                                <tr class="{{ !$account['matched'] ? 'bg-amber-50/50' : '' }}">

                                    <td class="px-6 py-4">

                                        <p class="text-sm {{ $account['is_total'] ? 'font-bold' : 'font-medium' }} text-slate-800">
                                            {{ $account['label'] }}
                                        </p>

                                    </td>

                                    <td class="px-6 py-4">

                                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            {{ $account['source'] }}
                                        </span>

                                    </td>

                                    <td class="px-6 py-4 text-right text-sm {{ $account['is_total'] ? 'font-bold' : '' }}">
                                        Rp{{ number_format(
                                            $account['application'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm {{ $account['is_total'] ? 'font-bold' : '' }}">
                                        Rp{{ number_format(
                                            $account['reference'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-6 py-4 text-right text-sm font-semibold {{ $account['matched'] ? 'text-slate-400' : 'text-amber-700' }}">
                                        Rp{{ number_format(
                                            $account['difference'],
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-6 py-4 text-center">

                                        <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $account['matched'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $account['matched'] ? 'Sesuai' : 'Berbeda' }}
                                        </span>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </section>

        @endif

    @endforeach

@endsection
