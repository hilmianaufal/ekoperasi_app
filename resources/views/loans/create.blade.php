@extends('layouts.app')

@section('title', 'Pengajuan Pinjaman')
@section('page-title', 'Pengajuan Pinjaman')
@section('page-description', 'Buat pengajuan pinjaman anggota koperasi')

@section('content')

    <div class="mx-auto max-w-5xl">

        @if ($errors->any())

            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">

                <p class="text-sm font-semibold text-red-700">
                    Pengajuan belum dapat disimpan
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-red-600">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif

        <form
            action="{{ route('loans.store') }}"
            method="POST"
            x-data="{
                principal: Number(@js((float) old('principal_amount', $setting->minimum_loan_amount))),
                rate: Number(@js((float) old('interest_rate', $setting->default_interest_rate))),
                tenor: Number(@js((int) old('tenor_months', $setting->default_tenor_months))),
                minimumLoan: Number(@js((float) $setting->minimum_loan_amount)),
                maximumLoan: @js($setting->maximum_loan_amount ? (float) $setting->maximum_loan_amount : null),

                formatRupiah(value) {
                    return new Intl.NumberFormat('id-ID').format(
                        Math.round(Number(value || 0))
                    );
                },

                get monthlyInterest() {
                    return this.principal * (this.rate / 100);
                },

                get totalInterest() {
                    return this.monthlyInterest * this.tenor;
                },

                get totalPayment() {
                    return this.principal + this.totalInterest;
                },

                get monthlyPayment() {
                    if (!this.tenor) {
                        return 0;
                    }

                    return this.totalPayment / this.tenor;
                },

                get validPrincipal() {
                    if (this.principal < this.minimumLoan) {
                        return false;
                    }

                    if (
                        this.maximumLoan !== null
                        && this.principal > this.maximumLoan
                    ) {
                        return false;
                    }

                    return true;
                }
            }">

            @csrf

            <div class="grid gap-6 lg:grid-cols-3">

                <section class="space-y-6 lg:col-span-2">

                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                        <div class="mb-6 flex items-center gap-3">

                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">

                                <i data-lucide="hand-coins" class="h-6 w-6"></i>

                            </div>

                            <div>

                                <h3 class="font-bold text-slate-900">
                                    Informasi Pengajuan
                                </h3>

                                <p class="mt-1 text-xs text-slate-500">
                                    Pilih anggota dan masukkan detail pinjaman.
                                </p>

                            </div>

                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div class="md:col-span-2">

                                <label
                                    for="member_id"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Anggota
                                    <span class="text-red-500">*</span>

                                </label>

                                <select
                                    name="member_id"
                                    id="member_id"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

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
                                                ) === (string) $member->id
                                            )>

                                            {{ $member->member_number }}
                                            — {{ $member->name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            <div>

                                <label
                                    for="application_date"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tanggal pengajuan
                                    <span class="text-red-500">*</span>

                                </label>

                                <input
                                    type="date"
                                    name="application_date"
                                    id="application_date"
                                    value="{{ old('application_date', now()->format('Y-m-d')) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            </div>

                            <div>

                                <label
                                    for="principal_amount"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Nominal pinjaman
                                    <span class="text-red-500">*</span>

                                </label>

                                <div class="relative">

                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                        Rp
                                    </span>

                                    <input
                                        type="number"
                                        name="principal_amount"
                                        id="principal_amount"
                                        value="{{ old('principal_amount', (float) $setting->minimum_loan_amount) }}"
                                        min="{{ (float) $setting->minimum_loan_amount }}"
                                        @if ($setting->maximum_loan_amount)
                                            max="{{ (float) $setting->maximum_loan_amount }}"
                                        @endif
                                        required
                                        x-model.number="principal"
                                        placeholder="Masukkan nominal"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                </div>

                                <div class="mt-2 space-y-1">

                                    <p class="text-xs text-slate-500">

                                        Minimal pinjaman:
                                        <strong>
                                            Rp{{ number_format($setting->minimum_loan_amount, 0, ',', '.') }}
                                        </strong>

                                    </p>

                                    @if ($setting->maximum_loan_amount)

                                        <p class="text-xs text-slate-500">

                                            Maksimal pinjaman:
                                            <strong>
                                                Rp{{ number_format($setting->maximum_loan_amount, 0, ',', '.') }}
                                            </strong>

                                        </p>

                                    @else

                                        <p class="text-xs text-slate-500">
                                            Tidak ada batas maksimal pinjaman.
                                        </p>

                                    @endif

                                    <p
                                        x-show="!validPrincipal"
                                        x-cloak
                                        class="text-xs font-semibold text-red-600">

                                        Nominal pinjaman berada di luar batas yang diperbolehkan.

                                    </p>

                                </div>

                            </div>

                            <div>

                                <label
                                    for="interest_rate"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Bunga flat per bulan
                                    <span class="text-red-500">*</span>

                                </label>

                                <div class="relative">

                                    <input
                                        type="number"
                                        name="interest_rate"
                                        id="interest_rate"
                                        value="{{ old('interest_rate', (float) $setting->default_interest_rate) }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        required
                                        x-model.number="rate"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 font-semibold text-slate-500">
                                        %
                                    </span>

                                </div>

                                <p class="mt-2 text-xs text-slate-500">

                                    Nilai default dari pengaturan:
                                    {{ number_format($setting->default_interest_rate, 2, ',', '.') }}%

                                </p>

                            </div>

                            <div>

                                <label
                                    for="tenor_months"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tenor pinjaman
                                    <span class="text-red-500">*</span>

                                </label>

                                <div class="relative">

                                    <input
                                        type="number"
                                        name="tenor_months"
                                        id="tenor_months"
                                        value="{{ old('tenor_months', $setting->default_tenor_months) }}"
                                        min="1"
                                        max="120"
                                        required
                                        x-model.number="tenor"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-20 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-500">
                                        Bulan
                                    </span>

                                </div>

                                <p class="mt-2 text-xs text-slate-500">

                                    Tenor default:
                                    {{ $setting->default_tenor_months }} bulan

                                </p>

                            </div>

                            <div class="md:col-span-2">

                                <label
                                    for="purpose"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Tujuan pinjaman
                                    <span class="text-red-500">*</span>

                                </label>

                                <textarea
                                    name="purpose"
                                    id="purpose"
                                    rows="4"
                                    required
                                    placeholder="Jelaskan tujuan penggunaan pinjaman"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('purpose') }}</textarea>

                            </div>

                            <div class="md:col-span-2">

                                <label
                                    for="notes"
                                    class="mb-2 block text-sm font-semibold text-slate-700">

                                    Catatan tambahan

                                </label>

                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="3"
                                    placeholder="Catatan tambahan jika diperlukan"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('notes') }}</textarea>

                            </div>

                        </div>

                    </article>

                </section>

                <!-- Simulasi -->
                <aside>

                    <article class="sticky top-28 rounded-3xl bg-slate-950 p-6 text-white shadow-xl">

                        <div class="flex items-center gap-3">

                            <div class="rounded-2xl bg-emerald-500/20 p-3 text-emerald-400">

                                <i data-lucide="calculator" class="h-6 w-6"></i>

                            </div>

                            <div>

                                <h3 class="font-bold">
                                    Simulasi Pinjaman
                                </h3>

                                <p class="mt-1 text-xs text-slate-400">
                                    Perhitungan bunga flat
                                </p>

                            </div>

                        </div>

                        <div class="mt-6 space-y-4">

                            <div class="rounded-2xl bg-white/5 p-4">

                                <p class="text-xs text-slate-400">
                                    Pokok pinjaman
                                </p>

                                <p class="mt-2 text-lg font-bold">
                                    Rp<span x-text="formatRupiah(principal)"></span>
                                </p>

                            </div>

                            <div class="grid grid-cols-2 gap-3">

                                <div class="rounded-2xl bg-white/5 p-4">

                                    <p class="text-xs text-slate-400">
                                        Total bunga
                                    </p>

                                    <p class="mt-2 text-sm font-bold text-amber-400">

                                        Rp<span x-text="formatRupiah(totalInterest)"></span>

                                    </p>

                                </div>

                                <div class="rounded-2xl bg-white/5 p-4">

                                    <p class="text-xs text-slate-400">
                                        Total bayar
                                    </p>

                                    <p class="mt-2 text-sm font-bold text-blue-400">

                                        Rp<span x-text="formatRupiah(totalPayment)"></span>

                                    </p>

                                </div>

                            </div>

                            <div class="rounded-2xl bg-emerald-500 p-5">

                                <p class="text-xs text-emerald-100">
                                    Estimasi angsuran per bulan
                                </p>

                                <p class="mt-2 text-2xl font-bold">

                                    Rp<span x-text="formatRupiah(monthlyPayment)"></span>

                                </p>

                                <p class="mt-2 text-xs text-emerald-100">

                                    Selama
                                    <span x-text="tenor || 0"></span>
                                    bulan

                                </p>

                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">

                                <p class="text-xs leading-6 text-slate-400">

                                    Bunga menggunakan metode flat berdasarkan
                                    pokok pinjaman awal setiap bulan.

                                </p>

                            </div>

                        </div>

                    </article>

                </aside>

            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('loans.index') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                    Kembali

                </a>

                <button
                    type="submit"
                    x-bind:disabled="!validPrincipal"
                    x-bind:class="!validPrincipal
                        ? 'cursor-not-allowed bg-slate-400 shadow-none'
                        : 'bg-emerald-600 shadow-lg shadow-emerald-200 hover:bg-emerald-700'"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-3 text-sm font-semibold text-white">

                    <i data-lucide="send" class="h-5 w-5"></i>
                    Buat Pengajuan

                </button>

            </div>

        </form>

    </div>

@endsection
