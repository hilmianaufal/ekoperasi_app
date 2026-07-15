@extends('layouts.app')

@section('title', 'Pembayaran Angsuran')
@section('page-title', 'Pembayaran Angsuran')
@section('page-description', 'Kelola tagihan dan pembayaran pinjaman anggota')

@section('content')

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Sisa Tagihan
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['outstanding'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="wallet-cards" class="h-6 w-6"></i>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Angsuran Terlambat
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['overdue'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-red-100 p-3 text-red-600">
                    <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Jatuh Tempo Bulan Ini
                    </p>

                    <h3 class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['due_this_month'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                    <i data-lucide="calendar-clock" class="h-6 w-6"></i>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        Diterima Bulan Ini
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['paid_this_month'], 0, ',', '.') }}
                    </h3>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="circle-dollar-sign" class="h-6 w-6"></i>
                </div>
            </div>
        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-5 md:p-6">

            <div>
                <h3 class="font-bold text-slate-900">
                    Daftar Tagihan Angsuran
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Pilih angsuran yang akan dibayar oleh anggota.
                </p>
            </div>

            <form
                action="{{ route('installments.index') }}"
                method="GET"
                class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-5">

                <div class="relative xl:col-span-2">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <i data-lucide="search" class="h-5 w-5"></i>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari pinjaman atau anggota..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>

                <select
                    name="status"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua status</option>
                    <option value="unpaid" @selected($status === 'unpaid')>
                        Belum Dibayar
                    </option>
                    <option value="partial" @selected($status === 'partial')>
                        Dibayar Sebagian
                    </option>
                    <option value="overdue" @selected($status === 'overdue')>
                        Terlambat
                    </option>
                    <option value="paid" @selected($status === 'paid')>
                        Lunas
                    </option>
                </select>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    title="Jatuh tempo mulai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    title="Jatuh tempo sampai"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                <div class="flex gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                        <i data-lucide="list-filter" class="h-5 w-5"></i>
                        Filter
                    </button>

                    @if ($search || $status || $dateFrom || $dateTo)
                        <a
                            href="{{ route('installments.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                            <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                            Reset
                        </a>
                    @endif

                </div>

            </form>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Pinjaman</th>
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Angsuran</th>
                        <th class="px-6 py-4">Jatuh Tempo</th>
                        <th class="px-6 py-4 text-right">Tagihan</th>
                        <th class="px-6 py-4 text-right">Sisa</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($installments as $installment)

                        @php
                            $statusClass = match ($installment->status) {
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'partial' => 'bg-blue-100 text-blue-700',
                                'overdue' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-6 py-4">
                                <a
                                    href="{{ route('loans.show', $installment->loan) }}"
                                    class="text-sm font-semibold text-slate-800 hover:text-emerald-600">

                                    {{ $installment->loan->loan_number }}
                                </a>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $installment->loan->member->name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $installment->loan->member->member_number }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-700">
                                Ke-{{ $installment->installment_number }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $installment->due_date->translatedFormat('d M Y') }}
                                </p>

                                @if ($installment->status === 'overdue')
                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        Lewat {{ $installment->due_date->diffInDays(today()) }} hari
                                    </p>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-800">
                                Rp{{ number_format($installment->total_amount, 0, ',', '.') }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-amber-600">
                                Rp{{ number_format($installment->remaining_amount, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $installment->status_label }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end">

                                    @if (
                                        $installment->loan->status === 'active'
                                        && $installment->status !== 'paid'
                                    )
                                        <a
                                            href="{{ route('installments.pay', $installment) }}"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700">

                                            <i data-lucide="hand-coins" class="h-4 w-4"></i>
                                            Bayar
                                        </a>
                                    @else
                                        <span class="text-xs font-semibold text-emerald-600">
                                            Selesai
                                        </span>
                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="calendar-check-2" class="h-9 w-9"></i>
                                </div>

                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Belum ada jadwal angsuran
                                </h4>

                                <p class="mt-2 text-sm text-slate-500">
                                    Setujui pengajuan pinjaman untuk membuat jadwal angsuran.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($installments->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $installments->links() }}
            </div>
        @endif

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-6">
            <h3 class="font-bold text-slate-900">
                Pembayaran Terbaru
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Sepuluh transaksi angsuran terakhir.
            </p>
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Kuitansi</th>
                        <th class="px-6 py-4">Anggota</th>
                        <th class="px-6 py-4">Pinjaman</th>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4 text-right">Nominal</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($recentPayments as $payment)

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4">
                                <a
                                    href="{{ route('installment-payments.show', $payment) }}"
                                    class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">

                                    {{ $payment->payment_code }}
                                </a>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $payment->installment->loan->member->name }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $payment->installment->loan->member->member_number }}
                                </p>
                            </td>

                            <td class="px-6 py-4">
                                <p class="text-sm text-slate-700">
                                    {{ $payment->installment->loan->loan_number }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Angsuran ke-{{ $payment->installment->installment_number }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ $payment->payment_date->translatedFormat('d M Y') }}
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ $payment->payment_method_label }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-emerald-600">
                                Rp{{ number_format($payment->amount, 0, ',', '.') }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                                Belum ada pembayaran angsuran.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </section>

@endsection
