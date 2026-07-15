@extends('layouts.app')

@section('title', 'Kuitansi Angsuran')
@section('page-title', 'Kuitansi Pembayaran')
@section('page-description', 'Bukti pembayaran angsuran anggota')

@push('styles')
    <style>
        @media print {
            aside,
            header,
            footer,
            .no-print {
                display: none !important;
            }

            body,
            main {
                background: white !important;
                padding: 0 !important;
            }

            .lg\:pl-72 {
                padding-left: 0 !important;
            }

            #receipt {
                border: none !important;
                box-shadow: none !important;
                max-width: 100% !important;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $installment = $installmentPayment->installment;
        $loan = $installment->loan;
        $member = $loan->member;
    @endphp

    <div class="no-print mx-auto mb-6 flex max-w-3xl flex-col justify-between gap-3 sm:flex-row">

        <a
            href="{{ route('installments.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke angsuran

        </a>

        <button
            type="button"
            onclick="window.print()"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

            <i data-lucide="printer" class="h-5 w-5"></i>
            Cetak Kuitansi

        </button>

    </div>

    <section
        id="receipt"
        class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">

        <div class="bg-gradient-to-br from-emerald-600 to-teal-800 p-7 text-white">

            <div class="flex items-start justify-between gap-5">

                <div class="flex items-center gap-4">

                    @if ($appSetting?->logo_url)

                        <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white">

                            <img
                                src="{{ $appSetting->logo_url }}"
                                alt="{{ $appSetting->cooperative_name }}"
                                class="h-full w-full object-contain p-2">

                        </div>

                    @else

                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15">

                            <i data-lucide="landmark" class="h-9 w-9"></i>

                        </div>

                    @endif

                    <div>

                        <h1 class="text-xl font-bold">
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

                <div class="text-right">

                    <p class="text-xs text-emerald-100">
                        Kuitansi Pembayaran
                    </p>

                    <p class="mt-1 font-bold">
                        {{ $installmentPayment->payment_code }}
                    </p>

                    <p class="mt-1 text-[10px] text-emerald-100">
                        {{ $installmentPayment->payment_date->translatedFormat('d F Y') }}
                    </p>

                </div>

            </div>

            @if ($appSetting?->address || $appSetting?->phone || $appSetting?->email)

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

                <p class="mt-3 text-3xl font-bold text-emerald-800">

                    Rp{{ number_format($installmentPayment->amount, 0, ',', '.') }}

                </p>

                <p class="mt-3 text-sm text-emerald-700">
                    Pembayaran berhasil dicatat
                </p>

            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">

                <article class="rounded-3xl border border-slate-200 p-6">

                    <h3 class="font-bold text-slate-900">
                        Informasi Anggota
                    </h3>

                    <dl class="mt-5 space-y-4">

                        <div>

                            <dt class="text-xs text-slate-500">
                                Nama anggota
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $member->name }}
                            </dd>

                        </div>

                        <div>

                            <dt class="text-xs text-slate-500">
                                Nomor anggota
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $member->member_number }}
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

                    <h3 class="font-bold text-slate-900">
                        Informasi Pembayaran
                    </h3>

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

                    </dl>

                </article>

            </div>

            <div class="mt-6 rounded-3xl bg-slate-50 p-6">

                <div class="flex justify-between gap-5 border-b border-slate-200 pb-4">

                    <span class="text-sm text-slate-500">
                        Total tagihan angsuran
                    </span>

                    <span class="text-sm font-semibold text-slate-800">

                        Rp{{ number_format($installment->total_amount, 0, ',', '.') }}

                    </span>

                </div>

                <div class="flex justify-between gap-5 border-b border-slate-200 py-4">

                    <span class="text-sm text-slate-500">
                        Pembayaran saat ini
                    </span>

                    <span class="text-sm font-semibold text-emerald-600">

                        Rp{{ number_format($installmentPayment->amount, 0, ',', '.') }}

                    </span>

                </div>

                <div class="flex justify-between gap-5 pt-4">

                    <span class="text-sm font-semibold text-slate-700">
                        Sisa setelah pembayaran
                    </span>

                    <span class="text-sm font-bold text-amber-600">

                        Rp{{ number_format($installmentPayment->remaining_after, 0, ',', '.') }}

                    </span>

                </div>

            </div>

            @if ($installmentPayment->reference_number)

                <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-4">

                    <p class="text-xs font-semibold text-blue-600">
                        Nomor Referensi
                    </p>

                    <p class="mt-1 text-sm font-bold text-blue-800">
                        {{ $installmentPayment->reference_number }}
                    </p>

                </div>

            @endif

            @if ($installmentPayment->notes)

                <div class="mt-6">

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
                            {{ $member->name }}
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
                                ?: ($installmentPayment->user?->name ?? 'Petugas Koperasi') }}

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
