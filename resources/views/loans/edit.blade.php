@extends('layouts.app')

@section('title', 'Edit Pinjaman')
@section('page-title', 'Edit Pinjaman')
@section('page-description', 'Perbarui data pengajuan atau keterangan pinjaman')

@section('content')

    @php
        $minimumLoan = (float) ($setting->minimum_loan_amount ?? 0);
        $maximumLoan = $setting->maximum_loan_amount !== null
            ? (float) $setting->maximum_loan_amount
            : null;
    @endphp

    <div class="mx-auto max-w-6xl">

        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <a
                href="{{ route('loans.show', $loan) }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>
                Kembali ke detail pinjaman
            </a>

            <span @class([
                'inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold',
                'bg-amber-50 text-amber-700' => $financialEditable,
                'bg-blue-50 text-blue-700' => !$financialEditable,
            ])>
                <i
                    data-lucide="{{ $financialEditable ? 'pencil-line' : 'shield-check' }}"
                    class="h-4 w-4">
                </i>

                {{ $financialEditable
                    ? 'Data keuangan dapat diedit'
                    : 'Hanya tujuan dan catatan dapat diedit' }}
            </span>

        </div>

        @if ($errors->any())

            <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5">
                <div class="flex gap-4">
                    <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <p class="font-bold text-red-800">
                            Perubahan belum dapat disimpan
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

        @unless ($financialEditable)

            <div class="mb-6 rounded-3xl border border-blue-200 bg-blue-50 p-5">
                <div class="flex gap-4">
                    <div class="h-fit rounded-2xl bg-blue-100 p-3 text-blue-600">
                        <i data-lucide="lock-keyhole" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <p class="font-bold text-blue-800">
                            Nilai keuangan sudah dikunci
                        </p>

                        <p class="mt-2 text-sm leading-6 text-blue-700">
                            Pinjaman sudah aktif atau merupakan data migrasi. Pokok, bagi hasil,
                            tenor, administrasi, dan anggota tidak dapat diubah agar buku kas,
                            piutang, jadwal angsuran, serta jurnal tetap konsisten.
                        </p>
                    </div>
                </div>
            </div>

        @endunless

        <form
            action="{{ route('loans.update', $loan) }}"
            method="POST"
            x-data="{
                principal: Number(@js((float) old('principal_amount', $loan->principal_amount))),
                rate: Number(@js((float) old('interest_rate', $loan->interest_rate))),
                tenor: Number(@js((int) old('tenor_months', $loan->tenor_months))),
                administration: Number(@js((float) old('administration_fee', $loan->administration_fee))),
                collectionMethod: @js(old(
                    'administration_collection_method',
                    $loan->administration_collection_method ?: 'separate'
                )),
                minimumLoan: Number(@js($minimumLoan)),
                maximumLoan: @js($maximumLoan),

                currency(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(Number(value || 0));
                },

                get totalInterest() {
                    return Math.max(Number(this.principal || 0), 0)
                        * (Math.max(Number(this.rate || 0), 0) / 100);
                },

                get totalPayment() {
                    return Math.max(Number(this.principal || 0), 0)
                        + this.totalInterest;
                },

                get monthlyPayment() {
                    const tenor = Number(this.tenor || 0);
                    return tenor >= 1 ? this.totalPayment / tenor : 0;
                },

                get netDisbursement() {
                    const principal = Math.max(Number(this.principal || 0), 0);
                    const administration = Math.max(Number(this.administration || 0), 0);

                    return this.collectionMethod === 'deducted'
                        ? Math.max(principal - administration, 0)
                        : principal;
                },

                get validPrincipal() {
                    const principal = Number(this.principal || 0);

                    if (principal < this.minimumLoan) {
                        return false;
                    }

                    if (
                        this.maximumLoan !== null
                        && principal > Number(this.maximumLoan)
                    ) {
                        return false;
                    }

                    return true;
                },

                get validTenor() {
                    const tenor = Number(this.tenor || 0);
                    return Number.isInteger(tenor) && tenor >= 1 && tenor <= 10;
                },

                get validAdministration() {
                    const administration = Number(this.administration || 0);
                    const principal = Number(this.principal || 0);

                    return administration >= 0 && administration < principal;
                },

                get canSubmit() {
                    return this.validPrincipal
                        && this.validTenor
                        && this.validAdministration;
                }
            }">

            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_370px]">

                <section class="space-y-6">

                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="mb-7 flex items-center gap-4">
                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="hand-coins" class="h-6 w-6"></i>
                            </div>

                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Data Pinjaman
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Nomor {{ $loan->loan_number }} · Status {{ $loan->status_label }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div class="md:col-span-2">
                                <label
                                    for="member_id"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Anggota <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="member_id"
                                    id="member_id"
                                    @disabled(!$financialEditable)
                                    @required($financialEditable)
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">

                                    @foreach ($members as $member)
                                        <option
                                            value="{{ $member->id }}"
                                            @selected(
                                                (string) old('member_id', $loan->member_id)
                                                === (string) $member->id
                                            )>
                                            {{ $member->member_number }} — {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('member_id')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="application_date"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tanggal pengajuan <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="application_date"
                                    id="application_date"
                                    value="{{ old(
                                        'application_date',
                                        optional($loan->application_date)->format('Y-m-d')
                                    ) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    @disabled(!$financialEditable)
                                    @required($financialEditable)
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">

                                @error('application_date')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="principal_amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Pokok pinjaman <span class="text-red-500">*</span>
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
                                        @disabled(!$financialEditable)
                                        @required($financialEditable)
                                        class="w-full rounded-2xl border border-slate-200 py-3.5 pl-12 pr-4 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">
                                </div>

                                @error('principal_amount')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="interest_rate"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Bagi hasil keseluruhan <span class="text-red-500">*</span>
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
                                        @disabled(!$financialEditable)
                                        @required($financialEditable)
                                        class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 pr-12 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-400">
                                        %
                                    </span>
                                </div>

                                <p class="mt-2 text-xs text-slate-400">
                                    Dihitung satu kali dari seluruh pokok pinjaman.
                                </p>

                                @error('interest_rate')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="tenor_months"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tenor <span class="text-red-500">*</span>
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
                                        @disabled(!$financialEditable)
                                        @required($financialEditable)
                                        class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 pr-20 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-400">
                                        bulan
                                    </span>
                                </div>

                                @error('tenor_months')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="administration_fee"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Biaya administrasi <span class="text-red-500">*</span>
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
                                        @disabled(!$financialEditable)
                                        @required($financialEditable)
                                        class="w-full rounded-2xl border border-slate-200 py-3.5 pl-12 pr-4 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">
                                </div>

                                @error('administration_fee')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="administration_collection_method"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Cara administrasi <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="administration_collection_method"
                                    id="administration_collection_method"
                                    x-model="collectionMethod"
                                    @disabled(!$financialEditable)
                                    @required($financialEditable)
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">
                                    <option value="separate">Dibayar terpisah saat pencairan</option>
                                    <option value="deducted">Dipotong dari uang pencairan</option>
                                </select>

                                @error('administration_collection_method')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    for="administration_payment_method"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Metode pembayaran administrasi <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="administration_payment_method"
                                    id="administration_payment_method"
                                    @disabled(!$financialEditable)
                                    @required($financialEditable)
                                    class="w-full rounded-2xl border border-slate-200 px-4 py-3.5 text-sm outline-none transition {{ $financialEditable ? 'bg-slate-50 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10' : 'cursor-not-allowed bg-slate-100 text-slate-500' }}">
                                    <option
                                        value="cash"
                                        @selected(old(
                                            'administration_payment_method',
                                            $loan->administration_payment_method
                                        ) === 'cash')>
                                        Tunai
                                    </option>

                                    <option
                                        value="transfer"
                                        @selected(old(
                                            'administration_payment_method',
                                            $loan->administration_payment_method
                                        ) === 'transfer')>
                                        Transfer
                                    </option>

                                    <option
                                        value="other"
                                        @selected(old(
                                            'administration_payment_method',
                                            $loan->administration_payment_method
                                        ) === 'other')>
                                        Lainnya
                                    </option>
                                </select>

                                @error('administration_payment_method')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                    </article>

                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="mb-7 flex items-center gap-4">
                            <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                                <i data-lucide="file-pen-line" class="h-6 w-6"></i>
                            </div>

                            <div>
                                <h2 class="font-bold text-slate-900">
                                    Tujuan dan Catatan
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Bagian ini tetap dapat diperbarui selama pinjaman aktif.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label
                                    for="purpose"
                                    class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tujuan pinjaman <span class="text-red-500">*</span>
                                </label>

                                <textarea
                                    name="purpose"
                                    id="purpose"
                                    rows="5"
                                    maxlength="2000"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('purpose', $loan->purpose) }}</textarea>

                                @error('purpose')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
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
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes', $loan->notes) }}</textarea>

                                @error('notes')
                                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                    </article>

                </section>

                <aside class="h-fit lg:sticky lg:top-6">

                    <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-800 to-slate-900 text-white shadow-xl">

                        <div class="border-b border-white/10 p-6">
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-white/10 p-3">
                                    <i data-lucide="calculator" class="h-6 w-6"></i>
                                </div>

                                <div>
                                    <h2 class="font-bold">Ringkasan Perubahan</h2>
                                    <p class="mt-1 text-xs text-emerald-100">
                                        Bagi hasil dihitung satu kali
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-6">
                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                <span class="text-sm text-emerald-100">Pokok</span>
                                <strong x-text="currency(principal)"></strong>
                            </div>

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                <span class="text-sm text-emerald-100">Bagi hasil total</span>
                                <strong class="text-amber-300" x-text="currency(totalInterest)"></strong>
                            </div>

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                <span class="text-sm text-emerald-100">Total tagihan</span>
                                <strong x-text="currency(totalPayment)"></strong>
                            </div>

                            <div class="rounded-2xl bg-white p-5 text-emerald-800">
                                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
                                    Angsuran bulanan
                                </p>

                                <p class="mt-2 text-3xl font-bold" x-text="currency(monthlyPayment)"></p>

                                <p class="mt-2 text-xs text-slate-500">
                                    Selama <strong x-text="tenor"></strong> bulan
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white/10 p-5">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="text-sm text-emerald-100">Administrasi</span>
                                    <strong x-text="currency(administration)"></strong>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-4">
                                    <span class="text-sm text-emerald-100">Dana diterima</span>
                                    <strong class="text-blue-200" x-text="currency(netDisbursement)"></strong>
                                </div>
                            </div>

                            <button
                                type="submit"
                                @if ($financialEditable)
                                    x-bind:disabled="!canSubmit"
                                @endif
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">
                                <i data-lucide="save" class="h-5 w-5"></i>
                                Simpan Perubahan
                            </button>

                            <a
                                href="{{ route('loans.show', $loan) }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/20 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                <i data-lucide="x" class="h-5 w-5"></i>
                                Batal
                            </a>
                        </div>

                    </article>

                </aside>

            </div>

        </form>

    </div>

@endsection