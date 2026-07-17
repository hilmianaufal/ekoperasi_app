@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Ringkasan kondisi koperasi dan transaksi bulan berjalan')

@section('content')

    @php
        $rupiah = static fn ($value): string => 'Rp' . number_format(
            (float) $value,
            0,
            ',',
            '.'
        );

        $primaryCards = [
            [
                'label' => 'Total Anggota',
                'value' => number_format((int) ($memberSummary['total'] ?? 0), 0, ',', '.'),
                'suffix' => 'orang',
                'description' => number_format((int) ($memberSummary['active'] ?? 0), 0, ',', '.') . ' anggota aktif',
                'icon' => 'users-round',
                'iconClass' => 'bg-blue-100 text-blue-700',
                'link' => route('members.index'),
            ],
            [
                'label' => 'Total Simpanan',
                'value' => $rupiah($savingSummary['total'] ?? 0),
                'suffix' => null,
                'description' => 'Saldo bersih seluruh simpanan anggota',
                'icon' => 'piggy-bank',
                'iconClass' => 'bg-violet-100 text-violet-700',
                'link' => route('savings.index'),
            ],
            [
                'label' => 'Pinjaman Dicairkan',
                'value' => $rupiah($loanSummary['disbursed'] ?? 0),
                'suffix' => null,
                'description' => number_format((int) ($loanSummary['active_count'] ?? 0), 0, ',', '.') . ' pinjaman aktif',
                'icon' => 'hand-coins',
                'iconClass' => 'bg-amber-100 text-amber-700',
                'link' => route('loans.index'),
            ],
            [
                'label' => 'Sisa Pinjaman',
                'value' => $rupiah($loanSummary['outstanding'] ?? 0),
                'suffix' => null,
                'description' => number_format((int) ($loanSummary['pending_count'] ?? 0), 0, ',', '.') . ' menunggu persetujuan',
                'icon' => 'landmark',
                'iconClass' => 'bg-orange-100 text-orange-700',
                'link' => route('loans.index'),
            ],
            [
                'label' => 'Saldo Kas & Bank',
                'value' => $rupiah($cashSummary['total'] ?? 0),
                'suffix' => null,
                'description' => 'Total kas masuk dikurangi kas keluar',
                'icon' => 'wallet-cards',
                'iconClass' => 'bg-emerald-100 text-emerald-700',
                'link' => route('cash-transactions.index'),
            ],
        ];

        $monthlyCards = [
            [
                'label' => 'Angsuran Masuk',
                'value' => $monthlySummary['installments'] ?? 0,
                'icon' => 'calendar-check-2',
                'class' => 'bg-emerald-50 text-emerald-700',
            ],
            [
                'label' => 'Pokok Angsuran',
                'value' => $monthlySummary['principal_installments'] ?? 0,
                'icon' => 'circle-dollar-sign',
                'class' => 'bg-cyan-50 text-cyan-700',
            ],
            [
                'label' => 'Bagi Hasil',
                'value' => $monthlySummary['profit_share'] ?? 0,
                'icon' => 'badge-percent',
                'class' => 'bg-amber-50 text-amber-700',
            ],
            [
                'label' => 'Administrasi',
                'value' => $monthlySummary['administration'] ?? 0,
                'icon' => 'receipt-text',
                'class' => 'bg-blue-50 text-blue-700',
            ],
            [
                'label' => 'Simpanan Masuk',
                'value' => $monthlySummary['saving_deposits'] ?? 0,
                'icon' => 'arrow-down-to-line',
                'class' => 'bg-violet-50 text-violet-700',
            ],
            [
                'label' => 'Penarikan Simpanan',
                'value' => $monthlySummary['saving_withdrawals'] ?? 0,
                'icon' => 'arrow-up-from-line',
                'class' => 'bg-rose-50 text-rose-700',
            ],
            [
                'label' => 'Pinjaman Dicairkan',
                'value' => $monthlySummary['loan_disbursements'] ?? 0,
                'icon' => 'banknote-arrow-down',
                'class' => 'bg-orange-50 text-orange-700',
            ],
            [
                'label' => 'Pengeluaran',
                'value' => $monthlySummary['cash_out'] ?? 0,
                'icon' => 'circle-minus',
                'class' => 'bg-red-50 text-red-700',
            ],
        ];

        $paymentMethodLabels = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer/Bank',
            'other' => 'Lainnya',
        ];
    @endphp

    <div class="space-y-7">

        {{-- HERO --}}
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 p-6 text-white shadow-xl shadow-slate-300/50 md:p-8">

            <div class="relative">

                <div class="pointer-events-none absolute -right-20 -top-28 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl"></div>

                <div class="relative flex flex-col justify-between gap-6 xl:flex-row xl:items-center">

                    <div class="max-w-2xl">

                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100 backdrop-blur">
                            <i data-lucide="calendar-days" class="h-4 w-4"></i>
                            {{ now()->translatedFormat('l, d F Y') }}
                        </div>

                        <h1 class="mt-5 text-2xl font-bold leading-tight sm:text-3xl">
                            Selamat datang, {{ auth()->user()->name }}
                        </h1>

                        <p class="mt-3 max-w-xl text-sm leading-7 text-slate-300">
                            Pantau total anggota, simpanan, pinjaman, saldo kas, serta transaksi koperasi bulan berjalan dalam satu halaman.
                        </p>

                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">

                        @if (Route::has('monthly-reports.index'))
                            <a
                                href="{{ route('monthly-reports.index') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">

                                <i data-lucide="calendar-range" class="h-5 w-5"></i>
                                Rekapan Bulanan

                            </a>
                        @endif

                        <a
                            href="{{ route('cash-transactions.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-400">

                            <i data-lucide="arrow-left-right" class="h-5 w-5"></i>
                            Lihat Buku Kas

                        </a>

                    </div>

                </div>

            </div>

        </section>

        {{-- RINGKASAN UTAMA --}}
        <section>

            <div class="mb-4 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">
                        Ringkasan Utama
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                        Total Keseluruhan Koperasi
                    </h2>
                </div>

                <p class="text-xs text-slate-500">
                    Dihitung otomatis dari seluruh transaksi tersimpan.
                </p>

            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

                @foreach ($primaryCards as $card)

                    <a
                        href="{{ $card['link'] }}"
                        class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg hover:shadow-slate-200/70">

                        <div class="flex items-start justify-between gap-4">

                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $card['iconClass'] }}">
                                <i data-lucide="{{ $card['icon'] }}" class="h-6 w-6"></i>
                            </div>

                            <div class="rounded-xl p-2 text-slate-300 transition group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                            </div>

                        </div>

                        <p class="mt-5 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <div class="mt-2 flex flex-wrap items-end gap-1.5">
                            <p class="break-all text-2xl font-bold tracking-tight text-slate-900">
                                {{ $card['value'] }}
                            </p>

                            @if ($card['suffix'])
                                <span class="pb-1 text-xs font-semibold text-slate-400">
                                    {{ $card['suffix'] }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-3 min-h-10 text-xs leading-5 text-slate-500">
                            {{ $card['description'] }}
                        </p>

                    </a>

                @endforeach

            </div>

        </section>

        {{-- RINCIAN SALDO --}}
        <div class="grid gap-6 xl:grid-cols-2">

            {{-- Rincian simpanan --}}
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between gap-4 border-b border-slate-100 p-6">

                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-100 text-violet-700">
                            <i data-lucide="piggy-bank" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Rincian Simpanan
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Saldo bersih setelah dikurangi penarikan.
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('savings.index') }}"
                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700"
                        title="Lihat simpanan">
                        <i data-lucide="arrow-up-right" class="h-5 w-5"></i>
                    </a>

                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3">

                    <article class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-blue-700">
                                Simpanan Pokok
                            </span>
                            <i data-lucide="wallet-cards" class="h-5 w-5 text-blue-600"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold text-slate-900">
                            {{ $rupiah($savingSummary['principal'] ?? 0) }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700">
                                Simpanan Wajib
                            </span>
                            <i data-lucide="calendar-check-2" class="h-5 w-5 text-indigo-600"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold text-slate-900">
                            {{ $rupiah($savingSummary['mandatory'] ?? 0) }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-teal-100 bg-teal-50/70 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-teal-700">
                                Simpanan Sukarela
                            </span>
                            <i data-lucide="badge-dollar-sign" class="h-5 w-5 text-teal-600"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold text-slate-900">
                            {{ $rupiah($savingSummary['voluntary'] ?? 0) }}
                        </p>
                    </article>

                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <span class="text-sm font-semibold text-slate-600">
                            Total seluruh simpanan
                        </span>
                        <span class="break-all text-xl font-bold text-violet-700">
                            {{ $rupiah($savingSummary['total'] ?? 0) }}
                        </span>
                    </div>
                </div>

            </section>

            {{-- Rincian kas --}}
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="flex items-center justify-between gap-4 border-b border-slate-100 p-6">

                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <i data-lucide="wallet" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h2 class="font-bold text-slate-900">
                                Rincian Kas & Bank
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Saldo bersih berdasarkan metode pembayaran.
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ route('cash-transactions.index') }}"
                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700"
                        title="Lihat buku kas">
                        <i data-lucide="arrow-up-right" class="h-5 w-5"></i>
                    </a>

                </div>

                <div class="grid gap-4 p-6 sm:grid-cols-3">

                    <article class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">
                                Saldo Tunai
                            </span>
                            <i data-lucide="banknote" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold {{ ($cashSummary['cash'] ?? 0) < 0 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ $rupiah($cashSummary['cash'] ?? 0) }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-cyan-100 bg-cyan-50/70 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-cyan-700">
                                Saldo Bank
                            </span>
                            <i data-lucide="landmark" class="h-5 w-5 text-cyan-600"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold {{ ($cashSummary['bank'] ?? 0) < 0 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ $rupiah($cashSummary['bank'] ?? 0) }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">
                                Metode Lain
                            </span>
                            <i data-lucide="ellipsis" class="h-5 w-5 text-slate-500"></i>
                        </div>
                        <p class="mt-4 break-all text-xl font-bold {{ ($cashSummary['other'] ?? 0) < 0 ? 'text-red-600' : 'text-slate-900' }}">
                            {{ $rupiah($cashSummary['other'] ?? 0) }}
                        </p>
                    </article>

                </div>

                <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">
                    <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <span class="text-sm font-semibold text-slate-600">
                            Total kas dan bank
                        </span>
                        <span class="break-all text-xl font-bold {{ ($cashSummary['total'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $rupiah($cashSummary['total'] ?? 0) }}
                        </span>
                    </div>
                </div>

            </section>

        </div>

        {{-- BULAN BERJALAN --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600">
                        Bulan Berjalan
                    </p>

                    <h2 class="mt-1 text-xl font-bold text-slate-900">
                        Transaksi {{ $periodStart->translatedFormat('F Y') }}
                    </h2>

                    <p class="mt-1 text-xs text-slate-500">
                        Periode {{ $periodStart->translatedFormat('d M Y') }} sampai {{ $periodEnd->translatedFormat('d M Y') }}.
                    </p>
                </div>

                @if (Route::has('monthly-reports.index'))
                    <a
                        href="{{ route('monthly-reports.index', [
                            'month' => $periodStart->month,
                            'year' => $periodStart->year,
                        ]) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">

                        <i data-lucide="file-chart-column" class="h-4 w-4"></i>
                        Lihat Rekapan

                    </a>
                @endif

            </div>

            <div class="grid gap-px bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">

                @foreach ($monthlyCards as $card)

                    <article class="bg-white p-5">

                        <div class="flex items-center justify-between gap-4">

                            <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['class'] }}">
                                <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                            </div>

                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                Bulan ini
                            </span>

                        </div>

                        <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-2 break-all text-xl font-bold text-slate-900">
                            {{ $rupiah($card['value']) }}
                        </p>

                    </article>

                @endforeach

            </div>

        </section>

        {{-- TRANSAKSI TERBARU --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col justify-between gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center">

                <div>
                    <h2 class="font-bold text-slate-900">
                        Transaksi Kas Terbaru
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Delapan aktivitas kas terakhir yang tercatat pada sistem.
                    </p>
                </div>

                <a
                    href="{{ route('cash-transactions.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 transition hover:text-emerald-700">

                    Lihat semua
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>

                </a>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Transaksi</th>
                            <th class="px-6 py-4">Metode</th>
                            <th class="px-6 py-4">Petugas</th>
                            <th class="px-6 py-4 text-right">Nominal</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($recentTransactions as $transaction)

                            @php
                                $isIncome = $transaction->direction === 'income';
                                $transactionDate = $transaction->transaction_date;
                                $method = $paymentMethodLabels[$transaction->payment_method] ?? ucfirst((string) $transaction->payment_method);
                            @endphp

                            <tr class="transition hover:bg-slate-50/80">

                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $transactionDate?->translatedFormat('d M Y') ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $transactionDate?->format('H:i') ?? '' }}
                                    </p>
                                </td>

                                <td class="min-w-72 px-6 py-4">
                                    <div class="flex items-start gap-3">

                                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isIncome ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                            <i data-lucide="{{ $isIncome ? 'arrow-down-left' : 'arrow-up-right' }}" class="h-4 w-4"></i>
                                        </div>

                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">
                                                {{ $transaction->description ?: ($transaction->category ?: 'Transaksi kas') }}
                                            </p>

                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                                <span>
                                                    {{ $transaction->transaction_code ?? '-' }}
                                                </span>

                                                @if ($transaction->category)
                                                    <span>•</span>
                                                    <span class="capitalize">
                                                        {{ str_replace('_', ' ', $transaction->category) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                    {{ $method ?: '-' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                    {{ $transaction->user?->name ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <span class="text-sm font-bold {{ $isIncome ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $isIncome ? '+' : '-' }}{{ $rupiah($transaction->amount) }}
                                    </span>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="receipt-text" class="h-7 w-7"></i>
                                    </div>

                                    <h3 class="mt-4 font-semibold text-slate-700">
                                        Belum ada transaksi kas
                                    </h3>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Transaksi terbaru akan tampil pada bagian ini.
                                    </p>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

    </div>

@endsection
