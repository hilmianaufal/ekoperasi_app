@extends('layouts.app')

@section('title', 'Tambah Transaksi Kas')
@section('page-title', 'Tambah Transaksi Kas')
@section('page-description', 'Catat pemasukan atau pengeluaran koperasi')

@section('content')

    <div class="mx-auto max-w-4xl">

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">

                <p class="text-sm font-semibold text-red-700">
                    Transaksi belum dapat disimpan
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        <form
            action="{{ route('cash-transactions.store') }}"
            method="POST"
            x-data="{
                direction: '{{ old('direction', 'income') }}',
            }">

            @csrf

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-7 flex items-center gap-3">

                    <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                        <i data-lucide="arrow-left-right" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900">
                            Informasi Transaksi
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Gunakan form ini untuk pemasukan atau pengeluaran manual.
                        </p>
                    </div>

                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">

                        <p class="mb-2 text-sm font-semibold text-slate-700">
                            Jenis transaksi
                            <span class="text-red-500">*</span>
                        </p>

                        <div class="grid gap-3 sm:grid-cols-2">

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="direction"
                                    value="income"
                                    x-model="direction"
                                    class="peer sr-only">

                                <div class="rounded-2xl border-2 border-slate-200 p-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-50">

                                    <div class="flex items-center gap-3">

                                        <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                                            <i data-lucide="arrow-down-to-line" class="h-5 w-5"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                Kas Masuk
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Menambah saldo koperasi
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            </label>

                            <label class="cursor-pointer">

                                <input
                                    type="radio"
                                    name="direction"
                                    value="expense"
                                    x-model="direction"
                                    class="peer sr-only">

                                <div class="rounded-2xl border-2 border-slate-200 p-4 peer-checked:border-red-500 peer-checked:bg-red-50">

                                    <div class="flex items-center gap-3">

                                        <div class="rounded-xl bg-red-100 p-3 text-red-600">
                                            <i data-lucide="arrow-up-from-line" class="h-5 w-5"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                Kas Keluar
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Mengurangi saldo koperasi
                                            </p>
                                        </div>

                                    </div>

                                </div>

                            </label>

                        </div>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal transaksi
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="date"
                            name="transaction_date"
                            value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Metode pembayaran
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="payment_method"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            <option
                                value="cash"
                                @selected(old('payment_method', 'cash') === 'cash')>
                                Tunai
                            </option>

                            <option
                                value="transfer"
                                @selected(old('payment_method') === 'transfer')>
                                Transfer
                            </option>

                            <option
                                value="other"
                                @selected(old('payment_method') === 'other')>
                                Lainnya
                            </option>

                        </select>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Kategori transaksi
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="category"
                            value="{{ old('category') }}"
                            required
                            list="category-options"
                            placeholder="Contoh: Biaya Operasional"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                        <datalist id="category-options">
                            <option value="Pendapatan Lain">
                            <option value="Biaya Operasional">
                            <option value="Pembelian ATK">
                            <option value="Listrik dan Internet">
                            <option value="Transportasi">
                            <option value="Honorarium">
                            <option value="Perawatan">
                            <option value="Lainnya">
                        </datalist>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nominal
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">

                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                Rp
                            </span>

                            <input
                                type="number"
                                name="amount"
                                value="{{ old('amount') }}"
                                min="1"
                                required
                                placeholder="Masukkan nominal"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                        </div>

                    </div>

                    <div class="md:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Keterangan transaksi
                            <span class="text-red-500">*</span>
                        </label>

                        <textarea
                            name="description"
                            rows="4"
                            required
                            placeholder="Jelaskan transaksi kas"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('description') }}</textarea>

                    </div>

                </div>

            </section>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('cash-transactions.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                    Kembali
                </a>

                <button
                    type="button"
                    onclick="confirmCashTransaction()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="save" class="h-5 w-5"></i>
                    Simpan Transaksi
                </button>

            </div>

        </form>

    </div>

@endsection

@push('scripts')
    <script>
        function confirmCashTransaction() {
            const form = document.querySelector(
                'form[action="{{ route('cash-transactions.store') }}"]'
            );

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Simpan transaksi kas?',
                text: 'Pastikan jenis dan nominal transaksi sudah benar.',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
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
