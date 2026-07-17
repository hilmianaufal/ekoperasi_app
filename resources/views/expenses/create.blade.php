@extends('layouts.app')

@section('title', 'Tambah Pengeluaran')
@section('page-title', 'Tambah Pengeluaran')
@section('page-description', 'Catat biaya operasional koperasi')

@section('content')

    <div
        class="mx-auto max-w-6xl"
        x-data="{
            amount: Number(
                @js((float) old(
                    'amount',
                    0
                ))
            ),

            category: @js(
                old(
                    'category',
                    ''
                )
            ),

            paymentMethod: @js(
                old(
                    'payment_method',
                    'cash'
                )
            ),

            description: @js(
                old(
                    'description',
                    ''
                )
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

            get canSubmit() {
                return (
                    this.category !== ''
                    && Number(this.amount || 0) > 0
                    && this.description.trim() !== ''
                );
            }
        }">

        <div class="mb-6">

            <a
                href="{{ route('expenses.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-red-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>

                Kembali ke daftar pengeluaran
            </a>

        </div>

        @if ($errors->any())

            <section class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5">

                <div class="flex gap-4">

                    <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>

                        <h3 class="font-bold text-red-800">
                            Pengeluaran belum dapat disimpan
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
            id="expense-form"
            action="{{ route('expenses.store') }}"
            method="POST">

            @csrf

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

                <section class="space-y-6">

                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                            <div class="rounded-2xl bg-red-100 p-3 text-red-600">
                                <i data-lucide="circle-minus" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Informasi Pengeluaran
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Masukkan tanggal, kategori, dan nominal pengeluaran.
                                </p>

                            </div>

                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">

                            <div>

                                <label
                                    for="transaction_date"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tanggal pengeluaran

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <input
                                    type="date"
                                    name="transaction_date"
                                    id="transaction_date"
                                    value="{{ old(
                                        'transaction_date',
                                        now()->format('Y-m-d')
                                    ) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                                @error('transaction_date')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="category"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Kategori pengeluaran

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <select
                                    name="category"
                                    id="category"
                                    x-model="category"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                                    <option value="">
                                        Pilih kategori
                                    </option>

                                    @foreach ($categories as $categoryOption)

                                        <option value="{{ $categoryOption }}">
                                            {{ $categoryOption }}
                                        </option>

                                    @endforeach

                                </select>

                                @error('category')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                            <div>

                                <label
                                    for="amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Nominal pengeluaran

                                    <span class="text-red-500">
                                        *
                                    </span>

                                </label>

                                <div class="relative">

                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-400">
                                        Rp
                                    </span>

                                    <input
                                        type="number"
                                        name="amount"
                                        id="amount"
                                        x-model.number="amount"
                                        min="1"
                                        step="1"
                                        required
                                        placeholder="Contoh: 500000"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

                                </div>

                                @error('amount')

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
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">

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

                                    <template x-if="paymentMethod === 'cash'">

                                        <span>
                                            Pengeluaran akan mengurangi akun kas.
                                        </span>

                                    </template>

                                    <template x-if="paymentMethod === 'transfer'">

                                        <span>
                                            Pengeluaran akan mengurangi akun bank.
                                        </span>

                                    </template>

                                    <template x-if="paymentMethod === 'other'">

                                        <span>
                                            Pengeluaran akan dicatat menggunakan akun kas.
                                        </span>

                                    </template>

                                </p>

                                @error('payment_method')

                                    <p class="mt-2 text-xs font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>

                        </div>

                    </article>

                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">

                        <div class="flex items-center gap-4 border-b border-slate-100 pb-6">

                            <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                                <i data-lucide="notebook-pen" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h2 class="font-bold text-slate-900">
                                    Keterangan Pengeluaran
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Jelaskan keperluan pengeluaran secara lengkap.
                                </p>

                            </div>

                        </div>

                        <div class="mt-6">

                            <label
                                for="description"
                                class="mb-2 block text-sm font-semibold text-slate-700">

                                Keterangan

                                <span class="text-red-500">
                                    *
                                </span>

                            </label>

                            <textarea
                                name="description"
                                id="description"
                                x-model="description"
                                rows="6"
                                maxlength="2000"
                                required
                                placeholder="Contoh: Pembayaran listrik kantor koperasi bulan Juli 2026"
                                class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm leading-6 outline-none transition focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10">{{ old('description') }}</textarea>

                            <div class="mt-2 flex items-center justify-between gap-4">

                                <p class="text-xs text-slate-400">
                                    Maksimal 2.000 karakter.
                                </p>

                                <p class="text-xs text-slate-400">
                                    <span x-text="description.length"></span>/2000
                                </p>

                            </div>

                            @error('description')

                                <p class="mt-2 text-xs font-medium text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </article>

                </section>

                {{-- Ringkasan --}}
                <aside class="h-fit lg:sticky lg:top-6">

                    <article class="overflow-hidden rounded-3xl bg-gradient-to-br from-red-700 via-rose-800 to-slate-900 text-white shadow-xl">

                        <div class="border-b border-white/10 p-6">

                            <div class="flex items-center gap-3">

                                <div class="rounded-2xl bg-white/10 p-3">
                                    <i data-lucide="receipt-text" class="h-6 w-6"></i>
                                </div>

                                <div>

                                    <h2 class="font-bold">
                                        Ringkasan Pengeluaran
                                    </h2>

                                    <p class="mt-1 text-xs text-red-100">
                                        Periksa sebelum disimpan
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="space-y-4 p-6">

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                <span class="text-sm text-red-100">
                                    Kategori
                                </span>

                                <strong
                                    class="max-w-[180px] text-right"
                                    x-text="
                                        category
                                            ? category
                                            : '-'
                                    ">
                                </strong>

                            </div>

                            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">

                                <span class="text-sm text-red-100">
                                    Metode
                                </span>

                                <strong class="text-right">

                                    <span
                                        x-show="paymentMethod === 'cash'"
                                        x-cloak>
                                        Tunai
                                    </span>

                                    <span
                                        x-show="paymentMethod === 'transfer'"
                                        x-cloak>
                                        Transfer
                                    </span>

                                    <span
                                        x-show="paymentMethod === 'other'"
                                        x-cloak>
                                        Lainnya
                                    </span>

                                </strong>

                            </div>

                            <div class="rounded-2xl bg-white p-5 text-red-800">

                                <p class="text-xs font-semibold uppercase tracking-wider text-red-600">
                                    Total pengeluaran
                                </p>

                                <p
                                    class="mt-2 break-all text-3xl font-bold"
                                    x-text="currency(amount)">
                                </p>

                            </div>

                            <div class="rounded-2xl bg-white/10 p-5">

                                <p class="text-xs font-semibold text-red-100">
                                    Jurnal otomatis
                                </p>

                                <div class="mt-4 space-y-3 text-xs">

                                    <div class="flex items-center justify-between gap-3">

                                        <span class="text-red-100">
                                            Debit Beban
                                        </span>

                                        <strong x-text="currency(amount)"></strong>

                                    </div>

                                    <div class="flex items-center justify-between gap-3">

                                        <span
                                            class="text-red-100"
                                            x-text="
                                                paymentMethod === 'transfer'
                                                    ? 'Kredit Bank'
                                                    : 'Kredit Kas'
                                            ">
                                        </span>

                                        <strong x-text="currency(amount)"></strong>

                                    </div>

                                </div>

                            </div>

                            <div class="rounded-2xl border border-amber-300/20 bg-amber-400/10 p-4">

                                <div class="flex gap-3">

                                    <i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-amber-200"></i>

                                    <p class="text-xs leading-6 text-amber-100">
                                        Setelah disimpan, transaksi akan mengurangi saldo kas atau bank dan langsung masuk ke jurnal akuntansi.
                                    </p>

                                </div>

                            </div>

                            <button
                                type="button"
                                x-bind:disabled="!canSubmit"
                                onclick="confirmExpense()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-bold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">

                                <i data-lucide="save" class="h-5 w-5"></i>

                                Simpan Pengeluaran
                            </button>

                            <a
                                href="{{ route('expenses.index') }}"
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

@push('scripts')

    <script>
        function confirmExpense() {
            const form = document.getElementById(
                'expense-form'
            );

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const amount = Number(
                document.getElementById('amount')?.value
                || 0
            );

            const category = document
                .getElementById('category')
                ?.value
                || '-';

            const formattedAmount =
                new Intl.NumberFormat(
                    'id-ID',
                    {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }
                ).format(amount);

            Swal.fire({
                icon: 'warning',
                title: 'Simpan pengeluaran?',
                html: `
                    <div style="text-align: left; line-height: 1.8;">
                        <p>
                            <strong>Kategori:</strong>
                            ${category}
                        </p>
                        <p>
                            <strong>Nominal:</strong>
                            ${formattedAmount}
                        </p>
                    </div>
                `,
                text: 'Saldo kas atau bank akan berkurang.',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Periksa kembali',
                confirmButtonColor: '#dc2626',
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
