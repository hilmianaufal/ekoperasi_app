@extends('layouts.app')

@section('title', 'Edit Pembayaran Angsuran')
@section('page-title', 'Edit Pembayaran Angsuran')
@section('page-description', 'Perbarui pembayaran terbaru anggota koperasi')

@section('content')

    <div
        class="mx-auto max-w-7xl"
        x-data="{
            principal: Number(
                @js((float) old(
                    'principal_amount',
                    $allocation['principal']
                ))
            ),

            profitShare: Number(
                @js((float) old(
                    'profit_share_amount',
                    $allocation['profit_share']
                ))
            ),

            paymentMethod: @js(
                old(
                    'payment_method',
                    $installmentPayment->payment_method
                )
            ),

            principalLimit: Number(
                @js((float) $limits['principal'])
            ),

            profitShareLimit: @js(
                $limits['profit_share'] !== null
                    ? (float) $limits['profit_share']
                    : null
            ),

            isLegacy: @js(
                (bool) $loan->is_legacy
            ),

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

            get totalPayment() {
                return (
                    Number(this.principal || 0)
                    + Number(this.profitShare || 0)
                );
            },

            get validPrincipal() {
                const principal = Number(
                    this.principal || 0
                );

                return (
                    principal >= 0
                    && principal <= this.principalLimit
                );
            },

            get validProfitShare() {
                const profitShare = Number(
                    this.profitShare || 0
                );

                if (profitShare < 0) {
                    return false;
                }

                if (this.profitShareLimit === null) {
                    return true;
                }

                return (
                    profitShare
                    <= Number(this.profitShareLimit)
                );
            },

            get estimatedRemainingPrincipal() {
                if (!this.isLegacy) {
                    return null;
                }

                return Math.max(
                    this.principalLimit
                    - Number(this.principal || 0),
                    0
                );
            },

            get canSubmit() {
                return (
                    this.validPrincipal
                    && this.validProfitShare
                    && this.totalPayment > 0
                );
            }
        }">

        <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

            <a
                href="{{ route(
                    'installment-payments.show',
                    $installmentPayment
                ) }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>

                Kembali ke detail pembayaran
            </a>

            <div class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">

                <i data-lucide="shield-alert" class="h-4 w-4"></i>

                Hanya pembayaran terbaru yang dapat diedit
            </div>

        </div>

        @if ($errors->any())

            <section class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5">

                <div class="flex gap-4">

                    <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">

                        <i
                            data-lucide="triangle-alert"
                            class="h-6 w-6">
                        </i>

                    </div>

                    <div>

                        <h3 class="font-bold text-red-800">
                            Pembayaran belum dapat diperbarui
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

        <form
            id="payment-edit-form"
            action="{{ route(
                'installment-payments.update',
                $installmentPayment
            ) }}"
            method="POST">

            @csrf
            @method('PUT')

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">

                <section class="space-y-6">

                    {{-- Informasi pinjaman --}}
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">

                                <i
                                    data-lucide="hand-coins"
                                    class="h-6 w-6">
                                </i>

                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Informasi Pembayaran
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Data anggota dan pembiayaan yang akan diperbarui.
                                </p>

                            </div>

                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                            <div class="rounded-2xl bg-slate-50 p-4">

                                <p class="text-xs font-medium text-slate-400">
                                    Kode pembayaran
                                </p>

                                <p class="mt-2 break-all text-sm font-bold text-slate-800">
                                    {{ $installmentPayment->payment_code }}
                                </p>

                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4">

                                <p class="text-xs font-medium text-slate-400">
                                    Nomor pinjaman
                                </p>

                                <p class="mt-2 text-sm font-bold text-slate-800">
                                    {{ $loan->loan_number }}
                                </p>

                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4">

                                <p class="text-xs font-medium text-slate-400">
                                    Angsuran
                                </p>

                                <p class="mt-2 text-sm font-bold text-slate-800">

                                    Ke-{{ $installmentPayment
                                        ->installment
                                        ->installment_number }}

                                </p>

                            </div>

                            <div class="rounded-2xl bg-slate-50 p-4">

                                <p class="text-xs font-medium text-slate-400">
                                    Status pembiayaan
                                </p>

                                <p class="mt-2 text-sm font-bold text-slate-800">
                                    {{ $loan->status_label }}
                                </p>

                            </div>

                        </div>

                        <div class="mt-5 rounded-3xl border border-emerald-100 bg-emerald-50 p-5">

                            <div class="flex items-center gap-4">

                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white">

                                    <i
                                        data-lucide="user-round"
                                        class="h-6 w-6">
                                    </i>

                                </div>

                                <div class="min-w-0">

                                    <p class="truncate font-bold text-emerald-900">

                                        {{ $loan->member?->name ?? '-' }}

                                    </p>

                                    <p class="mt-1 text-xs text-emerald-700">

                                        {{ $loan
                                            ->member
                                            ?->member_number
                                            ?? '-' }}

                                    </p>

                                </div>

                            </div>

                        </div>

                    </article>

                    {{-- Data pembayaran --}}
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                            <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">

                                <i
                                    data-lucide="pencil-line"
                                    class="h-6 w-6">
                                </i>

                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Perbarui Pembayaran
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Angsuran hanya terdiri dari pokok dan bagi hasil.
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
                                        $installmentPayment
                                            ->payment_date
                                            ->format('Y-m-d')
                                    ) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                @error('payment_date')

                                    <p class="mt-2 text-xs font-medium text-red-600">
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

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="principal_amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Angsuran pokok

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
                                        min="0"
                                        x-bind:max="principalLimit"
                                        step="1"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                </div>

                                <p class="mt-2 text-xs text-slate-400">

                                    Maksimal

                                    <strong
                                        class="text-slate-600"
                                        x-text="currency(principalLimit)">
                                    </strong>

                                </p>

                                <p
                                    x-show="!validPrincipal"
                                    x-cloak
                                    class="mt-2 text-xs font-medium text-red-600">

                                    Angsuran pokok melebihi batas yang tersedia.

                                </p>

                                @error('principal_amount')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="profit_share_amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Bagi hasil
                                </label>

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
                                        x-bind:max="
                                            profitShareLimit !== null
                                                ? profitShareLimit
                                                : undefined
                                        "
                                        step="1"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                </div>

                                <template x-if="profitShareLimit !== null">

                                    <p class="mt-2 text-xs text-slate-400">

                                        Maksimal

                                        <strong
                                            class="text-slate-600"
                                            x-text="currency(
                                                profitShareLimit
                                            )">
                                        </strong>

                                    </p>

                                </template>

                                <template x-if="profitShareLimit === null">

                                    <p class="mt-2 text-xs text-slate-400">
                                        Isi berdasarkan catatan bagi hasil koperasi.
                                    </p>

                                </template>

                                <p
                                    x-show="!validProfitShare"
                                    x-cloak
                                    class="mt-2 text-xs font-medium text-red-600">

                                    Nominal bagi hasil melebihi batas yang tersedia.

                                </p>

                                @error('profit_share_amount')

                                    <p class="mt-2 text-xs font-medium text-red-600">
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
                                    value="{{ old(
                                        'reference_number',
                                        $installmentPayment
                                            ->reference_number
                                    ) }}"
                                    maxlength="150"
                                    x-bind:required="
                                        paymentMethod === 'transfer'
                                    "
                                    placeholder="Nomor transfer atau transaksi"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                @error('reference_number')

                                    <p class="mt-2 text-xs font-medium text-red-600">
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
                                    placeholder="Catatan pembayaran"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old(
                                        'notes',
                                        $installmentPayment->notes
                                    ) }}</textarea>

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
                <aside class="h-fit xl:sticky xl:top-6">

                    <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-800 to-slate-900 text-white shadow-xl">

                        <div class="border-b border-white/10 p-6">

                            <div class="flex items-center gap-3">

                                <div class="rounded-2xl bg-white/10 p-3">

                                    <i
                                        data-lucide="calculator"
                                        class="h-6 w-6">
                                    </i>

                                </div>

                                <div>

                                    <h2 class="font-bold">
                                        Ringkasan Perubahan
                                    </h2>

                                    <p class="mt-1 text-xs text-emerald-100">
                                        Nilai pembayaran terbaru
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
                                    Total pembayaran
                                </p>

                                <p
                                    class="mt-2 break-all text-3xl font-bold"
                                    x-text="currency(totalPayment)">
                                </p>

                            </div>

                            <div
                                x-show="isLegacy"
                                x-cloak
                                class="rounded-2xl bg-white/10 p-5">

                                <p class="text-xs text-emerald-100">
                                    Perkiraan sisa pokok
                                </p>

                                <p
                                    class="mt-2 text-xl font-bold"
                                    x-text="currency(
                                        estimatedRemainingPrincipal
                                    )">
                                </p>

                            </div>

                            <div class="rounded-2xl border border-amber-300/20 bg-amber-400/10 p-4">

                                <div class="flex gap-3">

                                    <i
                                        data-lucide="triangle-alert"
                                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-200">
                                    </i>

                                    <p class="text-xs leading-6 text-amber-100">
                                        Perubahan nominal akan memperbarui saldo pinjaman, buku kas, dan jurnal akuntansi.
                                    </p>

                                </div>

                            </div>

                            <button
                                type="button"
                                x-bind:disabled="!canSubmit"
                                onclick="confirmPaymentUpdate()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">

                                <i
                                    data-lucide="save"
                                    class="h-5 w-5">
                                </i>

                                Simpan Perubahan
                            </button>

                            <a
                                href="{{ route(
                                    'installment-payments.show',
                                    $installmentPayment
                                ) }}"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-white/20 px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">

                                <i
                                    data-lucide="x"
                                    class="h-5 w-5">
                                </i>

                                Batal
                            </a>

                        </div>

                    </article>

                </aside>

            </div>

        </form>

    </div>

@endsection

@push('scripts')

    <script>
        function confirmPaymentUpdate() {
            const form = document.getElementById(
                'payment-edit-form'
            );

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const principal = Number(
                document.getElementById(
                    'principal_amount'
                )?.value || 0
            );

            const profitShare = Number(
                document.getElementById(
                    'profit_share_amount'
                )?.value || 0
            );

            const total = principal + profitShare;

            const formattedTotal =
                new Intl.NumberFormat(
                    'id-ID',
                    {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }
                ).format(total);

            Swal.fire({
                icon: 'warning',
                title: 'Simpan perubahan?',
                html: `
                    <div style="text-align:left;line-height:1.8">
                        <p>
                            <strong>Total pembayaran:</strong>
                            ${formattedTotal}
                        </p>
                        <p style="margin-top:8px">
                            Saldo pinjaman, buku kas, dan jurnal
                            akan dihitung ulang.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, perbarui',
                cancelButtonText: 'Periksa kembali',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>

@endpush
