@extends('layouts.app')

@section('title', 'Kuitansi Pembayaran SHU')
@section('page-title', 'Kuitansi Pembayaran SHU')
@section('page-description', 'Detail pembayaran pembagian SHU anggota')

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

            .print-container {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>

@endpush

@section('content')

    @php
        $allocation = $shuPayment->allocation;
        $period = $allocation->period;
        $member = $allocation->member;

        $methodLabel = match ($shuPayment->payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'other' => 'Lainnya',
            default => ucfirst($shuPayment->payment_method),
        };
    @endphp

    <div class="no-print mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">

        <a
            href="{{ route(
                'shu-periods.show',
                $period
            ) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke periode SHU
        </a>

        <div class="flex gap-3">

            <a
                href="{{ route(
                    'shu-payments.receipt',
                    $shuPayment
                ) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                <i data-lucide="external-link" class="h-5 w-5"></i>
                Buka Kuitansi
            </a>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                <i data-lucide="printer" class="h-5 w-5"></i>
                Cetak
            </button>

        </div>

    </div>

    <section class="print-container mx-auto max-w-4xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-7">

            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">

                <div>

                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-emerald-600">
                        {{ config('app.name') }}
                    </p>

                    <h1 class="mt-2 text-3xl font-bold text-slate-900">
                        Kuitansi Pembayaran SHU
                    </h1>

                    <p class="mt-2 text-sm text-slate-500">
                        Sisa Hasil Usaha Tahun
                        {{ $period->year }}
                    </p>

                </div>

                <div class="rounded-2xl bg-emerald-50 px-5 py-4 text-right">

                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                        Nomor Kuitansi
                    </p>

                    <p class="mt-1 font-bold text-emerald-800">
                        {{ $shuPayment->payment_code }}
                    </p>

                </div>

            </div>

        </div>

        <div class="p-7">

            <div class="grid gap-5 sm:grid-cols-2">

                <div class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Telah Diterima Oleh
                    </p>

                    <p class="mt-3 text-lg font-bold text-slate-900">
                        {{ $member?->name ?? '-' }}
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $member?->member_number ?? '-' }}
                    </p>

                    @if ($member?->address)

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $member->address }}
                        </p>

                    @endif

                </div>

                <div class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Informasi Pembayaran
                    </p>

                    <dl class="mt-3 space-y-3 text-sm">

                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Tanggal</dt>
                            <dd class="font-semibold text-slate-800">
                                {{ $shuPayment->payment_date->translatedFormat('d F Y') }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Metode</dt>
                            <dd class="font-semibold text-slate-800">
                                {{ $methodLabel }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Referensi</dt>
                            <dd class="font-semibold text-slate-800">
                                {{ $shuPayment->reference_number ?: '-' }}
                            </dd>
                        </div>

                    </dl>

                </div>

            </div>

            <div class="mt-6 rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-800 p-6 text-white">

                <p class="text-sm text-emerald-100">
                    Nominal Pembayaran
                </p>

                <p class="mt-2 text-4xl font-bold">
                    Rp{{ number_format(
                        (float) $shuPayment->amount,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>

                <p class="mt-3 text-sm text-emerald-100">
                    Pembayaran pembagian SHU tahun
                    {{ $period->year }}
                    kepada anggota
                    {{ $member?->name }}.
                </p>

            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Total Hak SHU
                    </p>

                    <p class="mt-2 font-bold text-slate-900">
                        Rp{{ number_format(
                            (float) $allocation->total_shu,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Total Sudah Dibayar
                    </p>

                    <p class="mt-2 font-bold text-emerald-700">
                        Rp{{ number_format(
                            (float) $allocation->paid_amount,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

                <article class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-500">
                        Sisa Pembayaran
                    </p>

                    <p class="mt-2 font-bold text-blue-700">
                        Rp{{ number_format(
                            max(
                                (float) $allocation->total_shu
                                - (float) $allocation->paid_amount,
                                0
                            ),
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </article>

            </div>

            @if ($shuPayment->notes)

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Catatan
                    </p>

                    <p class="mt-2 text-sm leading-7 text-slate-700">
                        {{ $shuPayment->notes }}
                    </p>

                </div>

            @endif

            <div class="mt-10 grid gap-10 sm:grid-cols-2">

                <div>

                    <p class="text-sm text-slate-500">
                        Penerima,
                    </p>

                    <div class="h-20"></div>

                    <p class="border-t border-slate-400 pt-2 text-sm font-semibold text-slate-800">
                        {{ $member?->name ?? '____________________' }}
                    </p>

                </div>

                <div class="sm:text-right">

                    <p class="text-sm text-slate-500">
                        Petugas,
                    </p>

                    <div class="h-20"></div>

                    <p class="border-t border-slate-400 pt-2 text-sm font-semibold text-slate-800">
                        {{ $shuPayment->user?->name ?? 'Administrator' }}
                    </p>

                </div>

            </div>

        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-7 py-4">

            <div class="flex flex-col justify-between gap-2 text-xs text-slate-400 sm:flex-row">

                <p>
                    Dicatat pada
                    {{ $shuPayment->created_at->translatedFormat('d F Y H:i') }}
                </p>

                @if ($cashTransaction)

                    <p>
                        Transaksi kas:
                        {{ $cashTransaction->transaction_code }}
                    </p>

                @endif

            </div>

        </div>

    </section>

@endsection

@if (!empty($printMode))

    @push('scripts')

        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>

    @endpush

@endif
