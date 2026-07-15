@extends('layouts.app')

@section('title', 'Bayar Angsuran')
@section('page-title', 'Bayar Angsuran')
@section('page-description', 'Catat pembayaran angsuran pinjaman anggota')

@section('content')

    <div class="mx-auto max-w-5xl">

        <div class="mb-6">
            <a
                href="{{ route('installments.index') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

                <i data-lucide="arrow-left" class="h-5 w-5"></i>
                Kembali ke daftar angsuran
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
                <p class="text-sm font-semibold text-red-700">
                    Pembayaran belum dapat disimpan
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('installments.payments.store', $loanInstallment) }}"
            method="POST"
            x-data="{
                method: '{{ old('payment_method', 'cash') }}',
                amount: Number('{{ old('amount', $loanInstallment->remaining_amount) }}'),
                remaining: Number('{{ $loanInstallment->remaining_amount }}'),

                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(
                        Number(value || 0)
                    );
                },

                get remainingAfter() {
                    return Math.max(
                        this.remaining - Number(this.amount || 0),
                        0
                    );
                }
            }">

            @csrf

            <div class="grid gap-6 lg:grid-cols-3">

                <div class="space-y-6 lg:col-span-2">

                    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="mb-6 flex items-center gap-3">

                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="hand-coins" class="h-6 w-6"></i>
                            </div>

                            <div>
                                <h3 class="font-bold text-slate-900">
                                    Informasi Pembayaran
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Pembayaran dapat dilakukan penuh atau sebagian.
                                </p>
                            </div>

                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tanggal pembayaran
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="payment_date"
                                    value="{{ old('payment_date', now()->format('Y-m-d')) }}"
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
                                    x-model="method"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <option value="cash">Tunai</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nominal pembayaran
                                    <span class="text-red-500">*</span>
                                </label>

                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                        Rp
                                    </span>

                                    <input
                                        type="number"
                                        name="amount"
                                        value="{{ old('amount', $loanInstallment->remaining_amount) }}"
                                        min="1"
                                        max="{{ $loanInstallment->remaining_amount }}"
                                        x-model.number="amount"
                                        required
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    Maksimal pembayaran:
                                    Rp{{ number_format($loanInstallment->remaining_amount, 0, ',', '.') }}
                                </p>
                            </div>

                            <div
                                x-show="method === 'transfer'"
                                x-cloak
                                class="md:col-span-2">

                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nomor referensi transfer
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="reference_number"
                                    value="{{ old('reference_number') }}"
                                    x-bind:required="method === 'transfer'"
                                    placeholder="Masukkan nomor referensi transfer"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Catatan
                                </label>

                                <textarea
                                    name="notes"
                                    rows="4"
                                    placeholder="Tambahkan catatan pembayaran"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes') }}</textarea>
                            </div>

                        </div>

                    </section>

                </div>

                <aside class="space-y-5">

                    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">

                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">
                            Informasi Anggota
                        </p>

                        <h3 class="mt-3 text-xl font-bold">
                            {{ $loanInstallment->loan->member->name }}
                        </h3>

                        <p class="mt-1 text-sm text-slate-400">
                            {{ $loanInstallment->loan->member->member_number }}
                        </p>

                        <div class="mt-6 space-y-4 border-t border-white/10 pt-5">

                            <div class="flex justify-between gap-4">
                                <span class="text-xs text-slate-400">
                                    Pinjaman
                                </span>

                                <span class="text-right text-xs font-semibold">
                                    {{ $loanInstallment->loan->loan_number }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-xs text-slate-400">
                                    Angsuran
                                </span>

                                <span class="text-right text-xs font-semibold">
                                    Ke-{{ $loanInstallment->installment_number }}
                                </span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-xs text-slate-400">
                                    Jatuh tempo
                                </span>

                                <span class="text-right text-xs font-semibold">
                                    {{ $loanInstallment->due_date->translatedFormat('d F Y') }}
                                </span>
                            </div>

                        </div>

                    </section>

                    <section class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

                        <p class="text-xs font-medium text-emerald-700">
                            Sisa tagihan saat ini
                        </p>

                        <p class="mt-2 text-2xl font-bold text-emerald-800">
                            Rp{{ number_format($loanInstallment->remaining_amount, 0, ',', '.') }}
                        </p>

                        <div class="my-5 border-t border-emerald-200"></div>

                        <p class="text-xs font-medium text-emerald-700">
                            Sisa setelah pembayaran
                        </p>

                        <p class="mt-2 text-xl font-bold text-emerald-800">
                            Rp<span x-text="formatRupiah(remainingAfter)"></span>
                        </p>

                    </section>

                </aside>

            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('installments.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                    Batal
                </a>

                <button
                    type="button"
                    onclick="confirmInstallmentPayment()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="save" class="h-5 w-5"></i>
                    Simpan Pembayaran
                </button>

            </div>

        </form>

    </div>

@endsection

@push('scripts')
    <script>
        function confirmInstallmentPayment() {
            const form = document.querySelector(
                'form[action*="/payments"]'
            );

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Simpan pembayaran?',
                text: 'Pastikan nominal dan metode pembayaran sudah benar.',
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
