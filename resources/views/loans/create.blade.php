@extends('layouts.app')

@section('title', 'Pengajuan Pinjaman')
@section('page-title', 'Pengajuan Pinjaman')
@section('page-description', 'Buat pengajuan pinjaman anggota koperasi')

@section('content')

    @php
        $minimumLoan = (float) ($setting->minimum_loan_amount ?? 0);

        $maximumLoan = $setting->maximum_loan_amount !== null
            ? (float) $setting->maximum_loan_amount
            : null;

        $defaultTenor = (int) old(
            'tenor_months',
            $setting->default_tenor_months ?? 10
        );

        $defaultTenor = min(
            max($defaultTenor, 1),
            10
        );
    @endphp

    <div class="mx-auto max-w-6xl">

        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <a
                href="{{ route('loans.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>

                Kembali ke daftar pinjaman
            </a>

            <div class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700">

                <i data-lucide="info" class="h-4 w-4"></i>

                Bagi hasil 1,5% dari seluruh pokok

            </div>

        </div>

        @if ($errors->any())

            <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5">

                <div class="flex gap-4">

                    <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>

                        <p class="font-bold text-red-800">
                            Pengajuan belum dapat disimpan
                        </p>

                        <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-red-600">

                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach

                        </ul>

                    </div>

                </div>

            </div>

        @endif

        <form
            action="{{ route('loans.store') }}"
            method="POST"
            x-data="{
                principal: Number(
                    @js((float) old(
                        'principal_amount',
                        $minimumLoan
                    ))
                ),

                rate: Number(
                    @js((float) old(
                        'interest_rate',
                        1.5
                    ))
                ),

                tenor: Number(
                    @js($defaultTenor)
                ),

                administration: Number(
                    @js((float) old(
                        'administration_fee',
                        0
                    ))
                ),

                collectionMethod: @js(
                    old(
                        'administration_collection_method',
                        'separate'
                    )
                ),

                administrationPaymentMethod: @js(
                    old(
                        'administration_payment_method',
                        'cash'
                    )
                ),

                minimumLoan: Number(
                    @js($minimumLoan)
                ),

                maximumLoan: @js($maximumLoan),

                formatRupiah(value) {
                    return new Intl.NumberFormat(
                        'id-ID',
                        {
                            maximumFractionDigits: 0,
                        }
                    ).format(
                        Math.round(
                            Number(value || 0)
                        )
                    );
                },

                get totalInterest() {
                    return Math.max(
                        Number(this.principal || 0),
                        0
                    ) * (
                        Math.max(
                            Number(this.rate || 0),
                            0
                        ) / 100
                    );
                },

                get totalPayment() {
                    return (
                        Math.max(
                            Number(this.principal || 0),
                            0
                        )
                        + this.totalInterest
                    );
                },

                get principalPerMonth() {
                    if (
                        !this.tenor
                        || Number(this.tenor) < 1
                    ) {
                        return 0;
                    }

                    return (
                        Number(this.principal || 0)
                        / Number(this.tenor)
                    );
                },

                get interestPerMonth() {
                    if (
                        !this.tenor
                        || Number(this.tenor) < 1
                    ) {
                        return 0;
                    }

                    return (
                        this.totalInterest
                        / Number(this.tenor)
                    );
                },

                get monthlyPayment() {
                    if (
                        !this.tenor
                        || Number(this.tenor) < 1
                    ) {
                        return 0;
                    }

                    return (
                        this.totalPayment
                        / Number(this.tenor)
                    );
                },

                get netDisbursement() {
                    if (
                        this.collectionMethod
                        === 'deducted'
                    ) {
                        return Math.max(
                            Number(this.principal || 0)
                            - Number(
                                this.administration || 0
                            ),
                            0
                        );
                    }

                    return Math.max(
                        Number(this.principal || 0),
                        0
                    );
                },

                get administrationPaidSeparately() {
                    if (
                        this.collectionMethod
                        !== 'separate'
                    ) {
                        return 0;
                    }

                    return Math.max(
                        Number(
                            this.administration || 0
                        ),
                        0
                    );
                },

                get validPrincipal() {
                    const principal = Number(
                        this.principal || 0
                    );

                    if (
                        principal
                        < this.minimumLoan
                    ) {
                        return false;
                    }

                    if (
                        this.maximumLoan !== null
                        && principal
                            > Number(this.maximumLoan)
                    ) {
                        return false;
                    }

                    return true;
                },

                get validTenor() {
                    const tenor = Number(
                        this.tenor || 0
                    );

                    return (
                        Number.isInteger(tenor)
                        && tenor >= 1
                        && tenor <= 10
                    );
                },

                get validAdministration() {
                    const administration = Number(
                        this.administration || 0
                    );

                    const principal = Number(
                        this.principal || 0
                    );

                    return (
                        administration >= 0
                        && administration < principal
                    );
                },

                get canSubmit() {
                    return (
                        this.validPrincipal
                        && this.validTenor
                        && this.validAdministration
                    );
                }
            }">

            @csrf

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">

                <section class="space-y-6">

                    {{-- Informasi pengajuan --}}
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="mb-7 flex items-center gap-4">

                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="hand-coins" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Informasi Pengajuan
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Pilih anggota dan tentukan tanggal pengajuan pinjaman.
                                </p>

                            </div>

                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div class="md:col-span-2">

                                <label
                                    for="member_id"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Anggota

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="member_id"
                                    id="member_id"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <option value="">
                                        Pilih anggota
                                    </option>

                                    @foreach ($members as $member)

                                        <option
                                            value="{{ $member->id }}"
                                            @selected(
                                                (string) old(
                                                    'member_id',
                                                    $selectedMemberId
                                                )
                                                ===
                                                (string) $member->id
                                            )>

                                            {{ $member->member_number }}
                                            —
                                            {{ $member->name }}

                                        </option>

                                    @endforeach

                                </select>

                                @error('member_id')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="application_date"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tanggal pengajuan

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    name="application_date"
                                    id="application_date"
                                    value="{{ old(
                                        'application_date',
                                        now()->format('Y-m-d')
                                    ) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                @error('application_date')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="principal_amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Pokok pinjaman

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <div class="relative">

                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">
                                        Rp
                                    </span>

                                    <input
                                        type="number"
                                        name="principal_amount"
                                        id="principal_amount"
                                        x-model.number="principal"
                                        min="{{ $minimumLoan }}"
                                        @if ($maximumLoan !== null)
                                            max="{{ $maximumLoan }}"
                                        @endif
                                        step="1"
                                        required
                                        placeholder="Contoh: 1000000"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                </div>

                                <p class="mt-2 text-xs leading-5 text-slate-400">

                                    Minimal
                                    Rp{{ number_format(
                                        $minimumLoan,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                    @if ($maximumLoan !== null)

                                        dan maksimal
                                        Rp{{ number_format(
                                            $maximumLoan,
                                            0,
                                            ',',
                                            '.'
                                        ) }}.

                                    @endif

                                </p>

                                <p
                                    x-show="!validPrincipal"
                                    x-cloak
                                    class="mt-2 text-xs font-medium text-red-600">

                                    Nominal pinjaman berada di luar batas yang diperbolehkan.

                                </p>

                                @error('principal_amount')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="interest_rate"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Bagi hasil keseluruhan

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <div class="relative">

                                    <input
                                        type="number"
                                        name="interest_rate"
                                        id="interest_rate"
                                        x-model.number="rate"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        readonly
                                        required
                                        class="w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3.5 pr-12 text-sm font-semibold text-slate-700 outline-none">

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-400">
                                        %
                                    </span>

                                </div>

                                <p class="mt-2 text-xs leading-5 text-slate-400">
                                    Dihitung satu kali dari seluruh pokok, bukan 1,5% setiap bulan.
                                </p>

                                @error('interest_rate')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="tenor_months"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tenor

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <div class="relative">

                                    <input
                                        type="number"
                                        name="tenor_months"
                                        id="tenor_months"
                                        x-model.number="tenor"
                                        min="1"
                                        max="10"
                                        step="1"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 pr-20 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-400">
                                        bulan
                                    </span>

                                </div>

                                <p class="mt-2 text-xs text-slate-400">
                                    Tenor maksimal 10 bulan.
                                </p>

                                <p
                                    x-show="!validTenor"
                                    x-cloak
                                    class="mt-2 text-xs font-medium text-red-600">

                                    Tenor harus antara 1 sampai 10 bulan.

                                </p>

                                @error('tenor_months')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>

                    </article>

                    {{-- Administrasi --}}
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="mb-7 flex items-center gap-4">

                            <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                                <i data-lucide="receipt-text" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Biaya Administrasi
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Administrasi dikenakan pada pinjaman dan tidak dimasukkan ke angsuran bulanan.
                                </p>

                            </div>

                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div>

                                <label
                                    for="administration_fee"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Nominal administrasi

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <div class="relative">

                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">
                                        Rp
                                    </span>

                                    <input
                                        type="number"
                                        name="administration_fee"
                                        id="administration_fee"
                                        x-model.number="administration"
                                        min="0"
                                        step="1"
                                        required
                                        placeholder="Contoh: 20000"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                </div>

                                <p
                                    x-show="!validAdministration"
                                    x-cloak
                                    class="mt-2 text-xs font-medium text-red-600">

                                    Administrasi harus lebih kecil dari pokok pinjaman.

                                </p>

                                @error('administration_fee')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="administration_collection_method"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Cara pembayaran administrasi

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="administration_collection_method"
                                    id="administration_collection_method"
                                    x-model="collectionMethod"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <option value="separate">
                                        Dibayar terpisah saat pencairan
                                    </option>

                                    <option value="deducted">
                                        Dipotong dari uang pencairan
                                    </option>

                                </select>

                                @error('administration_collection_method')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div class="md:col-span-2">

                                <label
                                    for="administration_payment_method"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Metode pembayaran administrasi

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="administration_payment_method"
                                    id="administration_payment_method"
                                    x-model="administrationPaymentMethod"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <option value="cash">
                                        Tunai
                                    </option>

                                    <option value="transfer">
                                        Transfer
                                    </option>

                                    <option value="other">
                                        Lainnya
                                    </option>

                                </select>

                                <p class="mt-2 text-xs leading-5 text-slate-400">

                                    <template x-if="collectionMethod === 'separate'">
                                        <span>
                                            Metode ini digunakan saat anggota membayar administrasi secara terpisah.
                                        </span>
                                    </template>

                                    <template x-if="collectionMethod === 'deducted'">
                                        <span>
                                            Administrasi akan mengurangi uang yang diterima anggota.
                                        </span>
                                    </template>

                                </p>

                                @error('administration_payment_method')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>

                    </article>

                    {{-- Tujuan dan catatan --}}
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="mb-7 flex items-center gap-4">

                            <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                                <i data-lucide="file-text" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Keterangan Pinjaman
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Tuliskan tujuan pengajuan dan catatan tambahan.
                                </p>

                            </div>

                        </div>

                        <div class="space-y-5">

                            <div>

                                <label
                                    for="purpose"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tujuan pinjaman

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <textarea
                                    name="purpose"
                                    id="purpose"
                                    rows="5"
                                    maxlength="2000"
                                    required
                                    placeholder="Contoh: Modal usaha, biaya pendidikan, atau kebutuhan lainnya"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('purpose') }}</textarea>

                                @error('purpose')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="notes"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Catatan tambahan
                                </label>

                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="Catatan tambahan apabila diperlukan"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes') }}</textarea>

                                @error('notes')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>

                    </article>

                </section>

                {{-- Ringkasan --}}
                <aside class="h-fit lg:sticky lg:top-6">

                    <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-800 to-slate-900 text-white shadow-xl">

                        <div class="border-b border-white/10 p-6">

                            <div class="flex items-center gap-3">

                                <div class="rounded-2xl bg-white/10 p-3">
                                    <i data-lucide="calculator" class="h-6 w-6"></i>
                                </div>

                                <div>

                                    <h2 class="font-bold">
                                        Ringkasan Pinjaman
                                    </h2>

                                    <p class="mt-1 text-xs text-emerald-100">
                                        Perhitungan otomatis
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="space-y-4 p-6">

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                <span class="text-sm text-emerald-100">
                                    Pokok pinjaman
                                </span>

                                <strong class="text-right">
                                    Rp<span x-text="formatRupiah(principal)"></span>
                                </strong>

                            </div>

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                <span class="text-sm text-emerald-100">
                                    Bagi hasil
                                    <span x-text="rate"></span>%
                                </span>

                                <strong class="text-right text-amber-300">
                                    Rp<span x-text="formatRupiah(totalInterest)"></span>
                                </strong>

                            </div>

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                <span class="text-sm text-emerald-100">
                                    Total tagihan
                                </span>

                                <strong class="text-right">
                                    Rp<span x-text="formatRupiah(totalPayment)"></span>
                                </strong>

                            </div>

                            <div class="grid grid-cols-2 gap-3">

                                <div class="rounded-2xl bg-white/10 p-4">

                                    <p class="text-xs text-emerald-100">
                                        Pokok per bulan
                                    </p>

                                    <p class="mt-2 text-sm font-bold">
                                        Rp<span x-text="formatRupiah(principalPerMonth)"></span>
                                    </p>

                                </div>

                                <div class="rounded-2xl bg-white/10 p-4">

                                    <p class="text-xs text-emerald-100">
                                        Bagi hasil per bulan
                                    </p>

                                    <p class="mt-2 text-sm font-bold">
                                        Rp<span x-text="formatRupiah(interestPerMonth)"></span>
                                    </p>

                                </div>

                            </div>

                            <div class="rounded-2xl bg-white p-5 text-emerald-800">

                                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
                                    Angsuran bulanan
                                </p>

                                <p class="mt-2 text-3xl font-bold">
                                    Rp<span x-text="formatRupiah(monthlyPayment)"></span>
                                </p>

                                <p class="mt-2 text-xs text-slate-500">
                                    Selama
                                    <span
                                        class="font-bold text-slate-700"
                                        x-text="tenor">
                                    </span>
                                    bulan
                                </p>

                            </div>

                            <div class="border-t border-white/10 pt-4">

                                <div class="flex items-center justify-between gap-4">

                                    <span class="text-sm text-emerald-100">
                                        Administrasi
                                    </span>

                                    <strong class="text-right">
                                        Rp<span x-text="formatRupiah(administration)"></span>
                                    </strong>

                                </div>

                                <div class="mt-4 flex items-center justify-between gap-4">

                                    <span class="text-sm text-emerald-100">
                                        Dana diterima
                                    </span>

                                    <strong class="text-right text-blue-200">
                                        Rp<span x-text="formatRupiah(netDisbursement)"></span>
                                    </strong>

                                </div>

                                <div
                                    x-show="collectionMethod === 'separate'"
                                    x-cloak
                                    class="mt-4 rounded-2xl bg-amber-400/15 p-4">

                                    <p class="text-xs leading-5 text-amber-100">
                                        Administrasi sebesar
                                        <strong>
                                            Rp<span x-text="formatRupiah(administrationPaidSeparately)"></span>
                                        </strong>
                                        dibayar terpisah saat pencairan.
                                    </p>

                                </div>

                                <div
                                    x-show="collectionMethod === 'deducted'"
                                    x-cloak
                                    class="mt-4 rounded-2xl bg-blue-400/15 p-4">

                                    <p class="text-xs leading-5 text-blue-100">
                                        Administrasi dipotong dari pokok sehingga dana bersih yang diterima adalah
                                        <strong>
                                            Rp<span x-text="formatRupiah(netDisbursement)"></span>.
                                        </strong>
                                    </p>

                                </div>

                            </div>

                            <div class="pt-3">

                                <button
                                    type="submit"
                                    x-bind:disabled="!canSubmit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">

                                    <i data-lucide="send" class="h-5 w-5"></i>

                                    Simpan Pengajuan
                                </button>

                                <a
                                    href="{{ route('loans.index') }}"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/20 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">

                                    <i data-lucide="x" class="h-5 w-5"></i>

                                    Batal
                                </a>

                            </div>

                        </div>

                    </article>

                    <div class="mt-5 rounded-3xl border border-blue-200 bg-blue-50 p-5">

                        <div class="flex gap-3">

                            <i data-lucide="shield-check" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"></i>

                            <p class="text-xs leading-6 text-blue-700">
                                Administrasi tidak dimasukkan ke total angsuran. Angsuran bulanan hanya terdiri dari pokok dan bagi hasil.
                            </p>

                        </div>

                    </div>

                </aside>

            </div>

        </form>

    </div>

@endsection
