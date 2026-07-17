@extends('layouts.app')

@section('title', 'Kuitansi Angsuran')
@section('page-title', 'Kuitansi Pembayaran')
@section('page-description', 'Bukti pembayaran angsuran anggota')

@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }

        @media print {
            aside,
            header,
            footer,
            .no-print {
                display: none !important;
            }

            body,
            main {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .lg\:pl-72 {
                padding-left: 0 !important;
            }

            #receipt {
                max-width: 100% !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }

            #receipt > div:first-child {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $installment = $installmentPayment->installment;
        $loan = $installment->loan;
        $member = $loan->member;

        $canEditPayment = (bool) ($canEdit ?? false)
            && \Illuminate\Support\Facades\Route::has(
                'installment-payments.edit'
            );

        $isImportedPayment = $installmentPayment->import_batch_id !== null;

        $principalAmount = (float) (
            $installmentPayment->principal_amount ?? 0
        );

        $profitShareAmount = (float) (
            $installmentPayment->profit_share_amount ?? 0
        );

        /*
         * Data pembayaran lama mungkin belum mempunyai
         * pembagian pokok dan bagi hasil.
         */
        if ($principalAmount <= 0 && $profitShareAmount <= 0) {
            $principalAmount = min(
                (float) $installmentPayment->amount,
                (float) $installment->principal_amount
            );

            $profitShareAmount = max(
                (float) $installmentPayment->amount
                    - $principalAmount,
                0
            );
        }

        $administrationAmount = (float) (
            $installmentPayment->administration_fee ?? 0
        );
    @endphp

    <div class="no-print mx-auto mb-6 flex max-w-3xl flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a
            href="{{ route('installments.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>

            Kembali ke angsuran

        </a>

        <div class="flex flex-col gap-3 sm:flex-row">

            @if ($canEditPayment)

                <a
                    href="{{ route(
                        'installment-payments.edit',
                        $installmentPayment
                    ) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">

                    <i data-lucide="pencil-line" class="h-5 w-5"></i>

                    Edit Pembayaran

                </a>

            @elseif ($isImportedPayment)

                <div class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-4 py-3 text-xs font-medium text-slate-500">

                    <i data-lucide="lock-keyhole" class="h-4 w-4"></i>

                    Pembayaran import tidak dapat diedit

                </div>

            @endif

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">

                <i data-lucide="printer" class="h-5 w-5"></i>

                Cetak Kuitansi

            </button>

        </div>

    </div>

    <section
        id="receipt"
        class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">

        <div class="bg-gradient-to-br from-emerald-600 to-teal-800 p-7 text-white">

            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">

                <div class="flex items-center gap-4">

                    @if ($appSetting?->logo_url)

                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white">

                            <img
                                src="{{ $appSetting->logo_url }}"
                                alt="{{ $appSetting->cooperative_name }}"
                                class="h-full w-full object-contain p-2">

                        </div>

                    @else

                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15">

                            <i data-lucide="landmark" class="h-9 w-9"></i>

                        </div>

                    @endif

                    <div class="min-w-0">

                        <h1 class="truncate text-xl font-bold">
                            {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}
                        </h1>

                        <p class="mt-1 text-xs text-emerald-100">
                            {{ $appSetting?->tagline ?? 'Sistem Manajemen Koperasi' }}
                        </p>

                        @if ($appSetting?->registration_number)

                            <p class="mt-1 text-[10px] text-emerald-100">
                                Badan Hukum: {{ $appSetting->registration_number }}
                            </p>

                        @endif

                    </div>

                </div>

                <div class="sm:text-right">

                    <p class="text-xs text-emerald-100">
                        Kuitansi Pembayaran
                    </p>

                    <p class="mt-1 break-all font-bold">
                        {{ $installmentPayment->payment_code }}
                    </p>

                    <p class="mt-1 text-[10px] text-emerald-100">
                        {{ $installmentPayment->payment_date->translatedFormat('d F Y') }}
                    </p>

                </div>

            </div>

            @if (
                $appSetting?->address
                || $appSetting?->phone
                || $appSetting?->email
            )

                <div class="mt-6 border-t border-white/15 pt-4 text-xs leading-6 text-emerald-100">

                    @if ($appSetting?->address)
                        <p>{{ $appSetting->address }}</p>
                    @endif

                    @if ($appSetting?->phone || $appSetting?->email)

                        <p>

                            {{ $appSetting?->phone }}

                            @if ($appSetting?->phone && $appSetting?->email)
                                ·
                            @endif

                            {{ $appSetting?->email }}

                        </p>

                    @endif

                </div>

            @endif

        </div>

        <div class="p-7 md:p-9">

            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 text-center">

                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">
                    Jumlah Pembayaran
                </p>

                <p class="mt-3 break-all text-3xl font-bold text-emerald-800">
                    Rp{{ number_format(
                        (float) $installmentPayment->amount,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>

                <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700">

                    <i data-lucide="circle-check-big" class="h-4 w-4"></i>

                    Pembayaran berhasil dicatat

                </div>

            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">

                <article class="rounded-3xl border border-slate-200 p-6">

                    <div class="flex items-center gap-3">

                        <div class="rounded-xl bg-emerald-100 p-2 text-emerald-600">
                            <i data-lucide="user-round" class="h-5 w-5"></i>
                        </div>

                        <h3 class="font-bold text-slate-900">
                            Informasi Anggota
                        </h3>

                    </div>

                    <dl class="mt-5 space-y-4">

                        <div>

                            <dt class="text-xs text-slate-500">
                                Nama anggota
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $member?->name ?? '-' }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Nomor anggota
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $member?->member_number ?? '-' }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Nomor pinjaman
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $loan->loan_number }}
                            </dd>

                        </div>

                    </dl>

                </article>

                <article class="rounded-3xl border border-slate-200 p-6">

                    <div class="flex items-center gap-3">

                        <div class="rounded-xl bg-blue-100 p-2 text-blue-600">
                            <i data-lucide="receipt-text" class="h-5 w-5"></i>
                        </div>

                        <h3 class="font-bold text-slate-900">
                            Informasi Pembayaran
                        </h3>

                    </div>

                    <dl class="mt-5 space-y-4">

                        <div>

                            <dt class="text-xs text-slate-500">
                                Angsuran
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                Angsuran ke-{{ $installment->installment_number }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Tanggal pembayaran
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $installmentPayment->payment_date->translatedFormat('d F Y') }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Metode
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $installmentPayment->payment_method_label }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Petugas
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $installmentPayment->user?->name ?? '-' }}
                            </dd>

                        </div>

                    </dl>

                </article>

            </div>

            <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">

                <div class="border-b border-slate-200 bg-white px-6 py-4">

                    <h3 class="font-bold text-slate-900">
                        Rincian Pembayaran
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Angsuran terdiri dari pokok dan bagi hasil.
                    </p>

                </div>

                <div class="p-6">

                    <div class="flex justify-between gap-5 border-b border-slate-200 pb-4">

                        <span class="text-sm text-slate-500">
                            Angsuran pokok
                        </span>

                        <span class="text-sm font-semibold text-slate-800">
                            Rp{{ number_format(
                                $principalAmount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>

                    </div>

                    <div class="flex justify-between gap-5 border-b border-slate-200 py-4">

                        <span class="text-sm text-slate-500">
                            Bagi hasil
                        </span>

                        <span class="text-sm font-semibold text-amber-600">
                            Rp{{ number_format(
                                $profitShareAmount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>

                    </div>

                    @if ($administrationAmount > 0)

                        <div class="flex justify-between gap-5 border-b border-slate-200 py-4">

                            <span class="text-sm text-slate-500">
                                Administrasi data lama
                            </span>

                            <span class="text-sm font-semibold text-slate-600">
                                Rp{{ number_format(
                                    $administrationAmount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </span>

                        </div>

                    @endif

                    <div class="flex justify-between gap-5 border-b border-slate-200 py-4">

                        <span class="text-sm font-semibold text-slate-700">
                            Total pembayaran
                        </span>

                        <span class="text-sm font-bold text-emerald-600">
                            Rp{{ number_format(
                                (float) $installmentPayment->amount,
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>

                    </div>

                    <div class="flex justify-between gap-5 pt-4">

                        <span class="text-sm font-semibold text-slate-700">
                            {{ $loan->is_legacy
                                ? 'Sisa pokok pembiayaan'
                                : 'Sisa tagihan angsuran' }}
                        </span>

                        <span class="text-sm font-bold text-amber-600">
                            Rp{{ number_format(
                                (float) $installmentPayment->remaining_after,
                                0,
                                ',',
                                '.'
                            ) }}
                        </span>

                    </div>

                </div>

            </div>

            @if ($installmentPayment->reference_number)

                <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-4">

                    <div class="flex items-start gap-3">

                        <i data-lucide="landmark" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"></i>

                        <div>

                            <p class="text-xs font-semibold text-blue-600">
                                Nomor Referensi
                            </p>

                            <p class="mt-1 break-all text-sm font-bold text-blue-800">
                                {{ $installmentPayment->reference_number }}
                            </p>

                        </div>

                    </div>

                </div>

            @endif

            @if ($installmentPayment->notes)

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">

                    <p class="text-xs font-semibold text-slate-500">
                        Catatan Pembayaran
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">
                        {{ $installmentPayment->notes }}
                    </p>

                </div>

            @endif

            @if ($appSetting?->receipt_footer)

                <div class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">

                    <p class="text-xs leading-6 text-slate-500">
                        {{ $appSetting->receipt_footer }}
                    </p>

                </div>

            @endif

            <div class="mt-12 grid grid-cols-2 gap-12 text-center">

                <div>

                    <p class="text-xs text-slate-500">
                        Pembayar
                    </p>

                    <div class="mt-16 border-t border-slate-300 pt-2">

                        <p class="text-sm font-semibold text-slate-800">
                            {{ $member?->name ?? '-' }}
                        </p>

                    </div>

                </div>

                <div>

                    <p class="text-xs text-slate-500">
                        Bendahara / Petugas
                    </p>

                    <div class="mt-16 border-t border-slate-300 pt-2">

                        <p class="text-sm font-semibold text-slate-800">
                            {{ $appSetting?->treasurer_name
                                ?: ($installmentPayment->user?->name
                                    ?? 'Petugas Koperasi') }}
                        </p>

                    </div>

                </div>

            </div>

            <p class="mt-10 text-center text-[10px] leading-5 text-slate-400">

                Kuitansi ini dibuat secara otomatis oleh aplikasi
                {{ $appSetting?->short_name ?? 'e-Koperasi' }}
                dan merupakan bukti pembayaran yang sah.

            </p>

        </div>

    </section>

@endsection
