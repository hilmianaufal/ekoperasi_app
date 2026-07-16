@extends('layouts.app')

@section('title', 'Laporan SHU')
@section('page-title', 'Laporan Sisa Hasil Usaha')
@section('page-description', 'Rekap alokasi dan pembayaran SHU anggota')

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
            }

            .print-break-avoid {
                break-inside: avoid;
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

    <div class="no-print flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a
            href="{{ route(
                'shu-periods.show',
                $shuPeriod
            ) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke periode SHU
        </a>

        <div class="flex flex-wrap gap-3">

            <a
                href="{{ route(
                    'shu-reports.export',
                    $shuPeriod
                ) }}"
                class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">

                <i data-lucide="file-spreadsheet" class="h-5 w-5"></i>
                Export Excel
            </a>

            <a
                href="{{ route(
                    'shu-reports.print',
                    $shuPeriod
                ) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                <i data-lucide="printer" class="h-5 w-5"></i>
                Cetak Laporan
            </a>

        </div>

    </div>

    <section class="print-report mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-7 text-center">

            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">
                {{ config('app.name') }}
            </p>

            <h1 class="mt-2 text-2xl font-bold uppercase text-slate-900">
                Laporan Pembagian Sisa Hasil Usaha
            </h1>

            <p class="mt-2 text-sm font-semibold text-slate-500">
                Periode Tahun {{ $shuPeriod->year }}
            </p>

        </div>

        <div class="p-7">

            <section class="print-break-avoid grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <article class="rounded-2xl border border-slate-200 p-4">

                    <p class="text-xs text-slate-500">
                        Ketetapan SHU Anggota
                    </p>

                    <p class="mt-2 text-lg font-bold text-slate-900">
                        Rp{{ number_format(
                            $summary['declared_member_shu'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4">

                    <p class="text-xs text-blue-700">
                        Hasil Alokasi
                    </p>

                    <p class="mt-2 text-lg font-bold text-blue-700">
                        Rp{{ number_format(
                            $summary['allocated_total'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

                    <p class="text-xs text-emerald-700">
                        Sudah Dibayar
                    </p>

                    <p class="mt-2 text-lg font-bold text-emerald-700">
                        Rp{{ number_format(
                            $summary['paid_total'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                    <p class="text-xs text-amber-700">
                        Selisih Alokasi
                    </p>

                    <p class="mt-2 text-lg font-bold text-amber-700">
                        Rp{{ number_format(
                            $summary['difference'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

            </section>

            <section class="print-break-avoid mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Jumlah Anggota
                    </p>

                    <p class="mt-2 text-2xl font-bold text-slate-900">
                        {{ $summary['member_count'] }}
                    </p>

                </article>

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Anggota Lunas
                    </p>

                    <p class="mt-2 text-2xl font-bold text-emerald-700">
                        {{ $summary['paid_count'] }}
                    </p>

                </article>

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Belum Lunas
                    </p>

                    <p class="mt-2 text-2xl font-bold text-amber-700">
                        {{ $summary['unpaid_count'] }}
                    </p>

                </article>

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Progres Pembayaran
                    </p>

                    <p class="mt-2 text-2xl font-bold text-blue-700">
                        {{ number_format(
                            $summary['payment_percentage'],
                            2,
                            ',',
                            '.'
                        ) }}%
                    </p>

                </article>

            </section>

            <section class="print-break-avoid mt-5 rounded-2xl border border-slate-200 p-5">

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div>

                        <p class="text-xs text-slate-500">
                            Total JASUS
                        </p>

                        <p class="mt-1 font-bold">
                            Rp{{ number_format(
                                $summary['business_service'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-slate-500">
                            Total JASIM
                        </p>

                        <p class="mt-1 font-bold">
                            Rp{{ number_format(
                                $summary['saving_service'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-slate-500">
                            Kas Keluar Pembayaran
                        </p>

                        <p class="mt-1 font-bold text-emerald-700">
                            Rp{{ number_format(
                                $summary['cash_expense_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>

                    <div>

                        <p class="text-xs text-slate-500">
                            Selisih Kas dan Pembayaran
                        </p>

                        <p class="mt-1 font-bold {{ abs($summary['cash_difference']) >= 0.01 ? 'text-red-600' : 'text-emerald-700' }}">
                            Rp{{ number_format(
                                $summary['cash_difference'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>

                </div>

            </section>

            <div class="mt-7 overflow-x-auto">

                <table class="min-w-full border-collapse">

                    <thead>

                        <tr class="bg-slate-100 text-left text-xs font-bold uppercase text-slate-600">

                            <th class="border border-slate-300 px-3 py-3">
                                No.
                            </th>

                            <th class="border border-slate-300 px-3 py-3">
                                Anggota
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Simpanan
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                JASUS
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                JASIM
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Total SHU
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Dibayar
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-right">
                                Sisa
                            </th>

                            <th class="border border-slate-300 px-3 py-3 text-center">
                                Status
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse ($allocations as $allocation)

                            @php
                                $remaining = max(
                                    (float) $allocation->total_shu
                                    - (float) $allocation->paid_amount,
                                    0
                                );

                                $statusLabel = match (
                                    $allocation->payment_status
                                ) {
                                    'paid' => 'Lunas',
                                    'partial' => 'Sebagian',
                                    default => 'Belum Dibayar',
                                };
                            @endphp

                            <tr>

                                <td class="border border-slate-300 px-3 py-3 text-sm">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $allocation->member?->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $allocation->member?->member_number }}
                                    </p>

                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm">
                                    Rp{{ number_format(
                                        $allocation->saving_balance,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm">
                                    Rp{{ number_format(
                                        $allocation->business_service_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm">
                                    Rp{{ number_format(
                                        $allocation->saving_service_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm font-bold">
                                    Rp{{ number_format(
                                        $allocation->total_shu,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm text-emerald-700">
                                    Rp{{ number_format(
                                        $allocation->paid_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-right text-sm text-blue-700">
                                    Rp{{ number_format(
                                        $remaining,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="border border-slate-300 px-3 py-3 text-center text-xs font-semibold">
                                    {{ $statusLabel }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="border border-slate-300 px-4 py-12 text-center text-sm text-slate-500">

                                    Belum ada alokasi SHU anggota.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="bg-emerald-50 font-bold">

                            <td
                                colspan="2"
                                class="border border-slate-300 px-3 py-3 text-right">

                                TOTAL
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['saving_balance'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['business_service'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['saving_service'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['allocated_total'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['paid_total'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3 text-right">
                                Rp{{ number_format(
                                    $summary['remaining_total'],
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="border border-slate-300 px-3 py-3"></td>

                        </tr>

                    </tfoot>

                </table>

            </div>

            <section class="print-break-avoid mt-10 grid gap-10 sm:grid-cols-2">

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
