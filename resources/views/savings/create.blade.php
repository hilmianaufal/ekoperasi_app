@extends('layouts.app')

@section('title', 'Transaksi Simpanan')
@section('page-title', 'Transaksi Simpanan')
@section('page-description', 'Catat setoran atau penarikan simpanan anggota')

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
            action="{{ route('savings.store') }}"
            method="POST"
            x-data="{
                memberId: '{{ old('member_id', $selectedMemberId) }}',
                savingTypeId: '{{ old('saving_type_id', $selectedSavingTypeId) }}',
                transactionType: '{{ old('transaction_type', 'deposit') }}',
                balance: 0,
                loadingBalance: false,

                async loadBalance() {
                    if (!this.memberId || !this.savingTypeId) {
                        this.balance = 0;
                        return;
                    }

                    this.loadingBalance = true;

                    try {
                        const url = new URL(
                            @js(route('savings.balance')),
                            window.location.origin
                        );

                        url.searchParams.set('member_id', this.memberId);
                        url.searchParams.set('saving_type_id', this.savingTypeId);

                        const response = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Gagal mengambil saldo');
                        }

                        const data = await response.json();

                        this.balance = Number(data.balance || 0);
                    } catch (error) {
                        this.balance = 0;
                    } finally {
                        this.loadingBalance = false;
                    }
                },

                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(value || 0);
                },
            }"
            x-init="loadBalance()">

            @csrf

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6 flex items-center gap-3">
                    <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                        <i data-lucide="wallet-cards" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900">
                            Data Transaksi
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Pilih anggota dan jenis simpanan.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label for="member_id" class="mb-2 block text-sm font-semibold text-slate-700">
                            Anggota
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="member_id"
                            id="member_id"
                            required
                            x-model="memberId"
                            x-on:change="loadBalance()"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            <option value="">Pilih anggota</option>

                            @foreach ($members as $member)
                                <option value="{{ $member->id }}">
                                    {{ $member->member_number }} — {{ $member->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="saving_type_id" class="mb-2 block text-sm font-semibold text-slate-700">
                            Jenis simpanan
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="saving_type_id"
                            id="saving_type_id"
                            required
                            x-model="savingTypeId"
                            x-on:change="loadBalance()"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            <option value="">Pilih jenis simpanan</option>

                            @foreach ($savingTypes as $savingType)
                                <option value="{{ $savingType->id }}">
                                    {{ $savingType->name }}
                                    — Rp{{ number_format($savingType->default_amount, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="transaction_date" class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal transaksi
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="date"
                            name="transaction_date"
                            id="transaction_date"
                            value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                            max="{{ now()->format('Y-m-d') }}"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div class="md:col-span-2">
                        <p class="mb-2 block text-sm font-semibold text-slate-700">
                            Jenis transaksi
                            <span class="text-red-500">*</span>
                        </p>

                        <div class="grid gap-3 sm:grid-cols-2">

                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="transaction_type"
                                    value="deposit"
                                    x-model="transactionType"
                                    class="peer sr-only">

                                <div class="rounded-2xl border-2 border-slate-200 p-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-50">
                                    <div class="flex items-center gap-3">

                                        <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                                            <i data-lucide="arrow-down-to-line" class="h-5 w-5"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                Setoran
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Menambah saldo simpanan
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="transaction_type"
                                    value="withdrawal"
                                    x-model="transactionType"
                                    class="peer sr-only">

                                <div class="rounded-2xl border-2 border-slate-200 p-4 peer-checked:border-amber-500 peer-checked:bg-amber-50">
                                    <div class="flex items-center gap-3">

                                        <div class="rounded-xl bg-amber-100 p-3 text-amber-600">
                                            <i data-lucide="arrow-up-from-line" class="h-5 w-5"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                Penarikan
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Mengurangi saldo simpanan
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </label>

                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">

                            <p class="text-xs font-medium text-blue-600">
                                Saldo simpanan saat ini
                            </p>

                            <p class="mt-2 text-2xl font-bold text-blue-800">
                                <span x-show="loadingBalance">
                                    Memuat...
                                </span>

                                <span x-show="!loadingBalance">
                                    Rp<span x-text="formatRupiah(balance)"></span>
                                </span>
                            </p>

                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="amount" class="mb-2 block text-sm font-semibold text-slate-700">
                            Nominal transaksi
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                Rp
                            </span>

                            <input
                                type="number"
                                name="amount"
                                id="amount"
                                value="{{ old('amount') }}"
                                min="1"
                                required
                                placeholder="Masukkan nominal"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        </div>

                        <p
                            x-show="transactionType === 'withdrawal'"
                            class="mt-2 text-xs text-amber-600">

                            Nominal penarikan tidak boleh melebihi saldo yang tersedia.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="mb-2 block text-sm font-semibold text-slate-700">
                            Keterangan
                        </label>

                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            placeholder="Tambahkan keterangan transaksi"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes') }}</textarea>
                    </div>

                </div>

            </section>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('savings.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                    Kembali
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="save" class="h-5 w-5"></i>
                    Simpan Transaksi
                </button>

            </div>

        </form>

    </div>

@endsection
