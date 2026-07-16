@extends('layouts.app')

@section('title', 'Tambah Angsuran Manual')
@section('page-title', 'Tambah Angsuran Manual')
@section('page-description', 'Catat pembayaran lanjutan untuk pembiayaan hasil migrasi')

@section('content')

    <a
        href="{{ route('installments.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke angsuran
    </a>

    @if ($loanOptions->isEmpty())

        <section class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">

            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                <i data-lucide="badge-check" class="h-8 w-8"></i>
            </div>

            <h2 class="mt-5 text-xl font-bold text-slate-900">
                Tidak ada pembiayaan yang perlu dibayar
            </h2>

            <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                Semua pembiayaan hasil migrasi sudah lunas atau tidak memiliki sisa pokok.
            </p>

        </section>

    @else

        <form
            action="{{ route('manual-installments.store') }}"
            method="POST"
            class="mt-6"
            x-data="{
                loans: @js($loanOptions),

                loanId: @js((string) old(
                    'loan_id',
                    $selectedLoanId
                )),

                principal: @js((string) old(
                    'principal_amount',
                    ''
                )),

                profitShare: @js((string) old(
                    'profit_share_amount',
                    '0'
                )),

                administration: @js((string) old(
                    'administration_fee',
                    '0'
                )),

                paymentMethod: @js((string) old(
                    'payment_method',
                    'cash'
                )),

                get selectedLoan() {
                    return this.loans.find(
                        (loan) =>
                            String(loan.id)
                            === String(this.loanId)
                    ) || null;
                },

                get totalPayment() {
                    return (
                        Number(this.principal || 0)
                        + Number(this.profitShare || 0)
                        + Number(this.administration || 0)
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
                        - Number(this.principal || 0),
                        0
                    );
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

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                    <div class="flex items-center gap-3 border-b border-slate-100 pb-5">

                        <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                            <i data-lucide="hand-coins" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Data Pembayaran
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                Pilih pembiayaan hasil import yang masih memiliki saldo.
                            </p>
                        </div>

                    </div>

                    <div class="mt-6">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Pembiayaan anggota
                        </label>

                        <select
                            name="loan_id"
                            x-model="loanId"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

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
                                        $loanOption['outstanding_principal'],
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </option>

                            @endforeach

                        </select>

                        @error('loan_id')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <div
                        x-show="selectedLoan"
                        x-cloak
                        class="mt-5 grid gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-5 sm:grid-cols-3">

                        <div>
                            <p class="text-xs text-blue-500">
                                Anggota
                            </p>

                            <p
                                class="mt-1 text-sm font-bold text-blue-900"
                                x-text="selectedLoan?.member_name">
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-blue-500">
                                Angsuran berikutnya
                            </p>

                            <p class="mt-1 text-sm font-bold text-blue-900">
                                Ke-<span x-text="selectedLoan?.next_installment_number"></span>
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

                    </div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Tanggal pembayaran
                            </label>

                            <input
                                type="date"
                                name="payment_date"
                                value="{{ old(
                                    'payment_date',
                                    now()->format('Y-m-d')
                                ) }}"
                                max="{{ now()->format('Y-m-d') }}"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                            @error('payment_date')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Metode pembayaran
                            </label>

                            <select
                                name="payment_method"
                                x-model="paymentMethod"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

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
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">

                                <label class="block text-sm font-semibold text-slate-700">
                                    Angsuran pokok
                                </label>

                                <button
                                    type="button"
                                    x-on:click="fillAllPrincipal()"
                                    x-bind:disabled="!selectedLoan"
                                    class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 disabled:cursor-not-allowed disabled:text-slate-300">

                                    Isi seluruh sisa
                                </button>

                            </div>

                            <input
                                type="number"
                                name="principal_amount"
                                x-model="principal"
                                min="1"
                                step="0.01"
                                x-bind:max="selectedLoan
                                    ? selectedLoan.outstanding_principal
                                    : undefined"
                                required
                                placeholder="Contoh: 500000"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                            @error('principal_amount')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">

                                <label class="block text-sm font-semibold text-slate-700">
                                    Bagi hasil
                                </label>

                                <button
                                    type="button"
                                    x-on:click="fillProfitSuggestion()"
                                    x-bind:disabled="!selectedLoan"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 disabled:cursor-not-allowed disabled:text-slate-300">

                                    Estimasi 1,5%
                                </button>

                            </div>

                            <input
                                type="number"
                                name="profit_share_amount"
                                x-model="profitShare"
                                min="0"
                                step="0.01"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                Tombol 1,5% hanya alat bantu. Nominal tetap disesuaikan dengan catatan koperasi.
                            </p>

                            @error('profit_share_amount')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Biaya administrasi
                            </label>

                            <input
                                type="number"
                                name="administration_fee"
                                x-model="administration"
                                min="0"
                                step="0.01"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                            @error('administration_fee')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div x-show="paymentMethod === 'transfer'" x-cloak>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Nomor referensi transfer
                            </label>

                            <input
                                type="text"
                                name="reference_number"
                                value="{{ old('reference_number') }}"
                                maxlength="150"
                                x-bind:required="paymentMethod === 'transfer'"
                                placeholder="Nomor transfer atau transaksi"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">

                            @error('reference_number')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-5">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Catatan
                        </label>

                        <textarea
                            name="notes"
                            rows="4"
                            maxlength="1000"
                            placeholder="Catatan tambahan pembayaran"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('notes') }}</textarea>

                        @error('notes')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                </section>

                <aside class="h-fit rounded-3xl bg-gradient-to-br from-emerald-700 to-slate-900 p-6 text-white shadow-lg">

                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">
                        Ringkasan pembayaran
                    </p>

                    <div class="mt-6 space-y-4">

                        <div class="flex justify-between gap-4 border-b border-white/10 pb-4">
                            <span class="text-sm text-emerald-100">
                                Angsuran pokok
                            </span>

                            <strong x-text="currency(principal)">
                            </strong>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-white/10 pb-4">
                            <span class="text-sm text-emerald-100">
                                Bagi hasil
                            </span>

                            <strong x-text="currency(profitShare)">
                            </strong>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-white/10 pb-4">
                            <span class="text-sm text-emerald-100">
                                Administrasi
                            </span>

                            <strong x-text="currency(administration)">
                            </strong>
                        </div>

                        <div class="pt-2">
                            <p class="text-sm text-emerald-100">
                                Total diterima
                            </p>

                            <p
                                class="mt-2 text-3xl font-bold"
                                x-text="currency(totalPayment)">
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs text-emerald-100">
                                Sisa pokok setelah pembayaran
                            </p>

                            <p
                                class="mt-2 text-xl font-bold"
                                x-text="currency(remainingPrincipal)">
                            </p>
                        </div>

                    </div>

                    <button
                        type="submit"
                        x-bind:disabled="
                            !selectedLoan
                            || Number(principal) <= 0
                            || Number(principal)
                                > Number(
                                    selectedLoan
                                        .outstanding_principal
                                )
                        "
                        class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">

                        <i data-lucide="save" class="h-5 w-5"></i>
                        Simpan Pembayaran
                    </button>

                </aside>

            </div>

        </form>

    @endif

@endsection
