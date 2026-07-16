@extends('layouts.app')

@section('title', 'Riwayat Pembayaran SHU')
@section('page-title', 'Riwayat Pembayaran SHU')
@section('page-description', 'Rekap seluruh pembayaran SHU anggota')

@section('content')

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-slate-500">
                Jumlah Pembayaran
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format(
                    $statistics['payment_count'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

            <p class="text-sm text-emerald-700">
                Total Dibayarkan
            </p>

            <p class="mt-2 text-xl font-bold text-emerald-700">
                Rp{{ number_format(
                    $statistics['total_paid'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

            <p class="text-sm text-blue-700">
                Pembayaran Tunai
            </p>

            <p class="mt-2 text-xl font-bold text-blue-700">
                Rp{{ number_format(
                    $statistics['cash_total'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">

            <p class="text-sm text-violet-700">
                Pembayaran Transfer
            </p>

            <p class="mt-2 text-xl font-bold text-violet-700">
                Rp{{ number_format(
                    $statistics['transfer_total'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            action="{{ route('shu-payments.index') }}"
            method="GET"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">

            <div class="xl:col-span-2">

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Pencarian
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Nama, nomor anggota, atau kuitansi"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Tahun
                </label>

                <select
                    name="year"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    <option value="">
                        Semua tahun
                    </option>

                    @foreach ($periodYears as $periodYear)

                        <option
                            value="{{ $periodYear }}"
                            @selected(
                                (string) $year
                                === (string) $periodYear
                            )>

                            {{ $periodYear }}
                        </option>

                    @endforeach

                </select>

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Metode
                </label>

                <select
                    name="payment_method"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    <option value="">
                        Semua metode
                    </option>

                    <option value="cash" @selected($paymentMethod === 'cash')>
                        Tunai
                    </option>

                    <option value="transfer" @selected($paymentMethod === 'transfer')>
                        Transfer
                    </option>

                    <option value="other" @selected($paymentMethod === 'other')>
                        Lainnya
                    </option>

                </select>

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Dari
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Sampai
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div class="flex gap-3 md:col-span-2 xl:col-span-6">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">

                    <i data-lucide="search" class="h-4 w-4"></i>
                    Terapkan Filter
                </button>

                <a
                    href="{{ route('shu-payments.index') }}"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">

                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    Reset
                </a>

            </div>

        </form>

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-6">

            <h3 class="font-bold text-slate-900">
                Daftar Pembayaran
            </h3>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="px-5 py-4">Kuitansi</th>
                        <th class="px-5 py-4">Anggota</th>
                        <th class="px-5 py-4">Periode</th>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Metode</th>
                        <th class="px-5 py-4 text-right">Nominal</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($payments as $payment)

                        @php
                            $allocation = $payment->allocation;
                            $member = $allocation?->member;
                            $period = $allocation?->period;

                            $methodLabel = match ($payment->payment_method) {
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer',
                                'other' => 'Lainnya',
                                default => ucfirst($payment->payment_method),
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $payment->payment_code }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $payment->reference_number ?: 'Tanpa referensi' }}
                                </p>

                            </td>

                            <td class="px-5 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $member?->name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $member?->member_number ?? '-' }}
                                </p>

                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">
                                SHU {{ $period?->year ?? '-' }}
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">
                                {{ $payment->payment_date->translatedFormat('d M Y') }}
                            </td>

                            <td class="px-5 py-4">

                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                    {{ $methodLabel }}
                                </span>

                            </td>

                            <td class="px-5 py-4 text-right text-sm font-bold text-emerald-700">
                                Rp{{ number_format(
                                    $payment->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </td>

                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route(
                                            'shu-payments.show',
                                            $payment
                                        ) }}"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-blue-50 hover:text-blue-600">

                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>

                                    <a
                                        href="{{ route(
                                            'shu-payments.receipt',
                                            $payment
                                        ) }}"
                                        target="_blank"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600">

                                        <i data-lucide="printer" class="h-4 w-4"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-16 text-center text-sm text-slate-500">

                                Belum ada pembayaran SHU.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($payments->hasPages())

            <div class="border-t border-slate-200 p-6">
                {{ $payments->links() }}
            </div>

        @endif

    </section>

@endsection
