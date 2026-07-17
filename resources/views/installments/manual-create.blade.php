@extends('layouts.app')

@section('title', 'Tambah Angsuran Manual')
@section('page-title', 'Tambah Angsuran Manual')
@section('page-description', 'Catat pembayaran lanjutan pembiayaan hasil migrasi')

@section('content')

    <div class="mx-auto max-w-7xl">

        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <a
                href="{{ route('installments.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>

                Kembali ke angsuran
            </a>

            <div class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700">

                <i data-lucide="info" class="h-4 w-4"></i>

                Angsuran hanya pokok dan bagi hasil
            </div>

        </div>

        @if ($errors->any())

            <section class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5">

                <div class="flex gap-4">

                    <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>

                        <h3 class="font-bold text-red-800">
                            Pembayaran belum dapat disimpan
                        </h3>

                        <ul class="mt-3 list-inside list-disc space-y-1 text-sm text-red-600">

                            @foreach ($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                </div>

            </section>

        @endif

        @if ($loanOptions->isEmpty())

            <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">

                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <i data-lucide="badge-check" class="h-9 w-9"></i>
                </div>

                <h2 class="mt-5 text-xl font-bold text-slate-900">
                    Tidak ada pembiayaan yang perlu dibayar
                </h2>

                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                    Semua pembiayaan hasil migrasi sudah lunas atau tidak mempunyai sisa pokok.
                </p>

                <a
                    href="{{ route('installments.index') }}"
                    class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                    <i data-lucide="arrow-left" class="h-5 w-5"></i>

                    Kembali ke daftar angsuran
                </a>

            </section>

        @else

            <form
                action="{{ route('manual-installments.store') }}"
                method="POST"
                x-data="{
                    loans: @js(
                        $loanOptions
                            ->values()
                            ->all()
                    ),

                    loanId: @js(
                        (string) old(
                            'loan_id',
                            $selectedLoanId
                        )
                    ),

                    principal: @js(
                        (string) old(
                            'principal_amount',
                            ''
                        )
                    ),

                    profitShare: @js(
                        (string) old(
                            'profit_share_amount',
                            '0'
                        )
                    ),

                    paymentMethod: @js(
                        (string) old(
                            'payment_method',
                            'cash'
                        )
                    ),

                    get selectedLoan() {
                        return this.loans.find(
                            (loan) =>
                                String(loan.id)
                                === String(this.loanId)
                        ) || null;
                    },

                    get totalPayment() {
                        return (
                            Number(
                                this.principal || 0
                            )
                            + Number(
                                this.profitShare || 0
                            )
                        );
                    },

                    get remainingPrincipal() {
                        if (!this.selectedLoan) {
                            return 0;
                        }

                        return Math.max(
                            Number(
                                this.selectedLoan
                                    .outstanding_principal
                            )
                            - Number(
                                this.principal || 0
                            ),
                            0
                        );
                    },

                    get validPrincipal() {
                        if (!this.selectedLoan) {
                            return false;
                        }

                        const principal = Number(
                            this.principal || 0
                        );

                        const outstanding = Number(
                            this.selectedLoan
                                .outstanding_principal
                            || 0
                        );

                        return (
                            principal > 0
                            && principal <= outstanding
                        );
                    },

                    get validProfitShare() {
                        return Number(
                            this.profitShare || 0
                        ) >= 0;
                    },

                    get canSubmit() {
                        return (
                            this.selectedLoan
                            && this.validPrincipal
                            && this.validProfitShare
                            && this.totalPayment > 0
                        );
                    },

                    changeLoan() {
                        this.principal = '';
                        this.profitShare = '0';
                    },

                    fillAllPrincipal() {
                        if (!this.selectedLoan) {
                            return;
                        }

                        this.principal =
                            this.selectedLoan
                                .outstanding_principal;
                    },

                    fillProfitSuggestion() {
                        if (!this.selectedLoan) {
                            return;
                        }

                        this.profitShare =
                            this.selectedLoan
                                .suggested_profit_share;
                    },

                    currency(value) {
                        return new Intl.NumberFormat(
                            'id-ID',
                            {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }
                        ).format(
                            Number(value || 0)
                        );
                    },
                }">

                @csrf

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

                    <section class="space-y-6">

                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                            <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                    <i data-lucide="hand-coins" class="h-6 w-6"></i>
                                </div>

                                <div>

                                    <h2 class="font-bold text-slate-900">
                                        Data Pembiayaan
                                    </h2>

                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Pilih pembiayaan hasil migrasi yang masih memiliki sisa pokok.
                                    </p>

                                </div>

                            </div>

                            <div class="mt-6">

                                <label
                                    for="loan_id"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Pembiayaan anggota

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="loan_id"
                                    id="loan_id"
                                    x-model="loanId"
                                    x-on:change="changeLoan()"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <option value="">
                                        Pilih pembiayaan
                                    </option>

                                    @foreach ($loanOptions as $loanOption)

                                        <option value="{{ $loanOption['id'] }}">

                                            {{ $loanOption['loan_number'] }}
                                            —
                                            {{ $loanOption['member_number'] }}
                                            {{ $loanOption['member_name'] }}
                                            —
                                            Sisa Rp{{ number_format(
                                                $loanOption[
                                                    'outstanding_principal'
                                                ],
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </option>

                                    @endforeach

                                </select>

                                @error('loan_id')

                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div
                                x-show="selectedLoan"
                                x-cloak
                                class="mt-6 grid gap-4 rounded-3xl border border-blue-100 bg-blue-50 p-5 sm:grid-cols-2 lg:grid-cols-4">

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Anggota
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-blue-900"
                                        x-text="selectedLoan?.member_name">
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-blue-600"
                                        x-text="selectedLoan?.member_number">
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Angsuran berikutnya
                                    </p>

                                    <p class="mt-1 text-sm font-bold text-blue-900">

                                        Ke-<span
                                            x-text="selectedLoan?.next_installment_number">
                                        </span>

                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Sisa pokok
                                    </p>

                                    <p
                                        class="mt-1 text-sm font-bold text-blue-900"
                                        x-text="currency(
                                            selectedLoan?.outstanding_principal
                                        )">
                                    </p>

                                </div>

                                <div>

                                    <p class="text-xs text-blue-500">
                                        Tenor tercatat
                                    </p>

                                    <p class="mt-1 text-sm font-bold text-blue-900">

                                        <template
                                            x-if="
                                                Number(
                                                    selectedLoan?.tenor_months
                                                ) > 0
                                            ">

                                            <span>
                                                <span
                                                    x-text="selectedLoan?.tenor_months">
                                                </span>
                                                bulan
                                            </span>

                                        </template>

                                        <template
                                            x-if="
                                                Number(
                                                    selectedLoan?.tenor_months
                                                ) <= 0
                                            ">

                                            <span>
                                                Tidak tersedia
                                            </span>

                                        </template>

                                    </p>

                                </div>

                            </div>

                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                            <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                                <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                                    <i data-lucide="receipt-text" class="h-6 w-6"></i>
                                </div>

                                <div>

                                    <h2 class="font-bold text-slate-900">
                                        Data Pembayaran
                                    </h2>

                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Masukkan pokok dan bagi hasil yang dibayar anggota.
                                    </p>

                                </div>

                            </div>

                            <div class="mt-6 grid gap-5 md:grid-cols-2">

                                <div>

                                    <label
                                        for="payment_date"
                                        class="mb-2 block text-sm font-semibold text-slate-700">

                                        Tanggal pembayaran

                                        <span class="text-red-500">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="date"
                                        name="payment_date"
                                        id="payment_date"
                                        value="{{ old(
                                            'payment_date',
                                            now()->format('Y-m-d')
                                        ) }}"
                                        max="{{ now()->format('Y-m-d') }}"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    @error('payment_date')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                                <div>

                                    <label
                                        for="payment_method"
                                        class="mb-2 block text-sm font-semibold text-slate-700">

                                        Metode pembayaran

                                        <span class="text-red-500">
                                            *
                                        </span>

                                    </label>

                                    <select
                                        name="payment_method"
                                        id="payment_method"
                                        x-model="paymentMethod"
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

                                    @error('payment_method')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                                <div>

                                    <div class="mb-2 flex items-center justify-between gap-3">

                                        <label
                                            for="principal_amount"
                                            class="block text-sm font-semibold text-slate-700">

                                            Angsuran pokok

                                            <span class="text-red-500">
                                                *
                                            </span>

                                        </label>

                                        <button
                                            type="button"
                                            x-on:click="fillAllPrincipal()"
                                            x-bind:disabled="!selectedLoan"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 disabled:cursor-not-allowed disabled:text-slate-300">

                                            Isi seluruh sisa
                                        </button>

                                    </div>

                                    <div class="relative">

                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">
                                            Rp
                                        </span>

                                        <input
                                            type="number"
                                            name="principal_amount"
                                            id="principal_amount"
                                            x-model.number="principal"
                                            min="1"
                                            step="1"
                                            x-bind:max="
                                                selectedLoan
                                                    ? selectedLoan
                                                        .outstanding_principal
                                                    : undefined
                                            "
                                            required
                                            placeholder="Contoh: 500000"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    </div>

                                    <p
                                        x-show="
                                            selectedLoan
                                            && !validPrincipal
                                            && Number(principal || 0) > 0
                                        "
                                        x-cloak
                                        class="mt-2 text-xs font-medium text-red-600">

                                        Pokok tidak boleh melebihi sisa pembiayaan.

                                    </p>

                                    @error('principal_amount')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                                <div>

                                    <div class="mb-2 flex items-center justify-between gap-3">

                                        <label
                                            for="profit_share_amount"
                                            class="block text-sm font-semibold text-slate-700">

                                            Bagi hasil
                                        </label>

                                        <button
                                            type="button"
                                            x-on:click="fillProfitSuggestion()"
                                            x-bind:disabled="
                                                !selectedLoan
                                                || Number(
                                                    selectedLoan
                                                        .suggested_profit_share
                                                ) <= 0
                                            "
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:text-slate-300">

                                            Gunakan estimasi
                                        </button>

                                    </div>

                                    <div class="relative">

                                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">
                                            Rp
                                        </span>

                                        <input
                                            type="number"
                                            name="profit_share_amount"
                                            id="profit_share_amount"
                                            x-model.number="profitShare"
                                            min="0"
                                            step="1"
                                            placeholder="Contoh: 1500"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    </div>

                                    <div
                                        x-show="
                                            selectedLoan
                                            && Number(
                                                selectedLoan
                                                    .suggested_profit_share
                                            ) > 0
                                        "
                                        x-cloak
                                        class="mt-2 rounded-2xl bg-blue-50 p-3">

                                        <p class="text-xs leading-5 text-blue-700">

                                            Estimasi bagi hasil bulanan:

                                            <strong
                                                x-text="currency(
                                                    selectedLoan
                                                        ?.suggested_profit_share
                                                )">
                                            </strong>

                                            dari bagi hasil keseluruhan

                                            <strong>
                                                <span
                                                    x-text="
                                                        selectedLoan
                                                            ?.profit_share_rate
                                                    ">
                                                </span>%
                                            </strong>

                                            yang dibagi mengikuti tenor.

                                        </p>

                                    </div>

                                    <p
                                        x-show="
                                            selectedLoan
                                            && Number(
                                                selectedLoan
                                                    .suggested_profit_share
                                            ) <= 0
                                        "
                                        x-cloak
                                        class="mt-2 text-xs leading-5 text-amber-600">

                                        Tenor pembiayaan migrasi tidak tersedia. Isi bagi hasil sesuai catatan koperasi.

                                    </p>

                                    @error('profit_share_amount')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                                <div
                                    x-show="paymentMethod === 'transfer'"
                                    x-cloak
                                    class="md:col-span-2">

                                    <label
                                        for="reference_number"
                                        class="mb-2 block text-sm font-semibold text-slate-700">

                                        Nomor referensi transfer

                                        <span class="text-red-500">
                                            *
                                        </span>

                                    </label>

                                    <input
                                        type="text"
                                        name="reference_number"
                                        id="reference_number"
                                        value="{{ old('reference_number') }}"
                                        maxlength="150"
                                        x-bind:required="
                                            paymentMethod === 'transfer'
                                        "
                                        placeholder="Nomor transfer atau transaksi"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    @error('reference_number')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                                <div class="md:col-span-2">

                                    <label
                                        for="notes"
                                        class="mb-2 block text-sm font-semibold text-slate-700">

                                        Catatan
                                    </label>

                                    <textarea
                                        name="notes"
                                        id="notes"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Catatan tambahan pembayaran"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes') }}</textarea>

                                    @error('notes')

                                        <p class="mt-2 text-sm font-medium text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </div>

                            </div>

                        </article>

                    </section>

                    <aside class="h-fit xl:sticky xl:top-6">

                        <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-800 to-slate-900 text-white shadow-xl">

                            <div class="border-b border-white/10 p-6">

                                <div class="flex items-center gap-3">

                                    <div class="rounded-2xl bg-white/10 p-3">
                                        <i data-lucide="calculator" class="h-6 w-6"></i>
                                    </div>

                                    <div>

                                        <h2 class="font-bold">
                                            Ringkasan Pembayaran
                                        </h2>

                                        <p class="mt-1 text-xs text-emerald-100">
                                            Pokok dan bagi hasil
                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="space-y-4 p-6">

                                <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                    <span class="text-sm text-emerald-100">
                                        Angsuran pokok
                                    </span>

                                    <strong
                                        class="text-right"
                                        x-text="currency(principal)">
                                    </strong>

                                </div>

                                <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                    <span class="text-sm text-emerald-100">
                                        Bagi hasil
                                    </span>

                                    <strong
                                        class="text-right text-amber-300"
                                        x-text="currency(profitShare)">
                                    </strong>

                                </div>

                                <div class="rounded-2xl bg-white p-5 text-emerald-800">

                                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
                                        Total diterima
                                    </p>

                                    <p
                                        class="mt-2 text-3xl font-bold"
                                        x-text="currency(totalPayment)">
                                    </p>

                                </div>

                                <div class="rounded-2xl bg-white/10 p-5">

                                    <p class="text-xs text-emerald-100">
                                        Sisa pokok setelah pembayaran
                                    </p>

                                    <p
                                        class="mt-2 text-xl font-bold"
                                        x-text="currency(remainingPrincipal)">
                                    </p>

                                </div>

                                <div class="rounded-2xl border border-blue-300/20 bg-blue-400/10 p-4">

                                    <div class="flex gap-3">

                                        <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-blue-200"></i>

                                        <p class="text-xs leading-6 text-blue-100">
                                            Biaya administrasi tidak dicatat pada angsuran. Administrasi hanya dicatat ketika pembiayaan dibuat atau dicairkan.
                                        </p>

                                    </div>

                                </div>

                                <button
                                    type="submit"
                                    x-bind:disabled="!canSubmit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">

                                    <i data-lucide="save" class="h-5 w-5"></i>

                                    Simpan Pembayaran
                                </button>

                                <a
                                    href="{{ route('installments.index') }}"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/20 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">

                                    <i data-lucide="x" class="h-5 w-5"></i>

                                    Batal
                                </a>

                            </div>

                        </article>

                    </aside>

                </div>

            </form>

        @endif

    </div>

@endsection
