cat > resources/views/installments/index.blade.php <<'BLADE'
@extends('layouts.app')

@section('title', 'Pembayaran Angsuran')
@section('page-title', 'Pembayaran Angsuran')
@section('page-description', 'Kelola tagihan dan pembayaran pembiayaan anggota')

@section('content')

    <section class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-800 shadow-lg">

        <div class="relative p-6 text-white md:p-8">

            <div class="relative z-10 flex flex-col justify-between gap-5 md:flex-row md:items-center">

                <div>

                    <p class="text-sm font-medium text-emerald-100">
                        Pengelolaan angsuran
                    </p>

                    <h2 class="mt-2 text-2xl font-bold">
                        Pembayaran Pembiayaan Anggota
                    </h2>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-100">
                        Catat pembayaran jadwal pembiayaan baru maupun
                        pembayaran lanjutan dari data pembiayaan hasil migrasi.
                    </p>

                </div>

                @if (
                    \Illuminate\Support\Facades\Route::has(
                        'manual-installments.create'
                    )
                )

                    <a
                        href="{{ route('manual-installments.create') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-bold text-emerald-700 shadow-lg transition hover:bg-emerald-50 md:w-auto">

                        <i data-lucide="circle-plus" class="h-5 w-5"></i>

                        Tambah Angsuran Manual
                    </a>

                @endif

            </div>

            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-24 right-28 h-56 w-56 rounded-full bg-white/5"></div>

        </div>

    </section>

    <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between gap-4">

                <div>

                    <p class="text-sm font-medium text-slate-500">
                        Sisa Pembiayaan
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format(
                            (float) ($statistics['outstanding'] ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
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
                        {{ number_format(
                            (int) ($statistics['overdue'] ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
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
                        {{ number_format(
                            (int) ($statistics['due_this_month'] ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
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
                        Rp{{ number_format(
                            (float) ($statistics['paid_this_month'] ?? 0),
                            0,
                            ',',
                            '.'
                        ) }}
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

            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">

                <div>

                    <h3 class="font-bold text-slate-900">
                        Daftar Tagihan Angsuran
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Pilih tombol Bayar untuk jadwal reguler atau
                        Tambah Angsuran untuk pembiayaan hasil migrasi.
                    </p>

                </div>

                <div class="inline-flex w-fit items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">

                    <i data-lucide="info" class="h-4 w-4"></i>

                    Data migrasi menggunakan sisa pokok pembiayaan

                </div>

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
                        placeholder="Cari pembiayaan atau anggota..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                </div>

                <select
                    name="status"
                    class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">
                        Semua status
                    </option>

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

                <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-5 xl:justify-end">

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

                        <th class="px-6 py-4">
                            Pembiayaan
                        </th>

                        <th class="px-6 py-4">
                            Anggota
                        </th>

                        <th class="px-6 py-4">
                            Angsuran
                        </th>

                        <th class="px-6 py-4">
                            Jatuh Tempo
                        </th>

                        <th class="px-6 py-4 text-right">
                            Pembayaran
                        </th>

                        <th class="px-6 py-4 text-right">
                            Sisa Pembiayaan
                        </th>

                        <th class="px-6 py-4">
                            Status
                        </th>

                        <th class="px-6 py-4 text-right">
                            Aksi
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($installments as $installment)

                        @php
                            $loan = $installment->loan;

                            $isLegacy = (bool) (
                                $loan?->is_legacy
                                ?? false
                            );

                            $legacyOutstanding = (float) (
                                $loan?->outstanding_principal
                                ?? 0
                            );

                            $isLegacyActive = (
                                $isLegacy
                                && $loan?->status === 'active'
                                && $legacyOutstanding > 0
                            );

                            /*
                             * Untuk data migrasi, paid pada baris
                             * angsuran hanya berarti pembayaran lama
                             * tersebut sudah selesai.
                             *
                             * Sisa pembiayaan sebenarnya berada pada
                             * loans.outstanding_principal.
                             */
                            $displayRemaining = $isLegacyActive
                                ? $legacyOutstanding
                                : (float) $installment->remaining_amount;

                            $statusClass = match ($installment->status) {
                                'paid'
                                    => 'bg-emerald-100 text-emerald-700',

                                'partial'
                                    => 'bg-blue-100 text-blue-700',

                                'overdue'
                                    => 'bg-red-100 text-red-700',

                                default
                                    => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-6 py-4">

                                <a
                                    href="{{ route(
                                        'loans.show',
                                        $loan
                                    ) }}"
                                    class="text-sm font-semibold text-slate-800 hover:text-emerald-600">

                                    {{ $loan?->loan_number ?? '-' }}
                                </a>

                                @if ($isLegacy)

                                    <span class="mt-2 block w-fit rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700">
                                        Data Migrasi
                                    </span>

                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $loan?->member?->name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $loan?->member?->member_number ?? '-' }}
                                </p>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4">

                                <p class="text-sm font-semibold text-slate-700">
                                    Ke-{{ $installment->installment_number }}
                                </p>

                                @if ($isLegacy)

                                    <p class="mt-1 text-xs text-slate-400">
                                        Riwayat pembayaran
                                    </p>

                                @endif

                            </td>

                            <td class="whitespace-nowrap px-6 py-4">

                                <p class="text-sm text-slate-700">
                                    {{ $installment->due_date
                                        ?->translatedFormat('d M Y')
                                        ?? '-' }}
                                </p>

                                @if ($installment->status === 'overdue')

                                    <p class="mt-1 text-xs font-semibold text-red-600">
                                        Lewat
                                        {{ $installment->due_date->diffInDays(
                                            today()
                                        ) }}
                                        hari
                                    </p>

                                @endif

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <p class="text-sm font-semibold text-slate-800">
                                    Rp{{ number_format(
                                        (float) $installment->total_amount,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </p>

                                @if ($isLegacy)

                                    <p class="mt-1 text-xs text-slate-400">
                                        Pembayaran lama
                                    </p>

                                @endif

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                <p class="text-sm font-bold {{ $displayRemaining > 0
                                    ? 'text-amber-600'
                                    : 'text-emerald-600' }}">

                                    Rp{{ number_format(
                                        $displayRemaining,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </p>

                                @if ($isLegacyActive)

                                    <p class="mt-1 text-xs font-medium text-amber-600">
                                        Masih harus dibayar
                                    </p>

                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $installment->status_label }}
                                </span>

                                @if ($isLegacyActive)

                                    <p class="mt-2 whitespace-nowrap text-xs font-semibold text-blue-600">
                                        Pembiayaan masih aktif
                                    </p>

                                @elseif ($loan?->status === 'paid')

                                    <p class="mt-2 whitespace-nowrap text-xs font-semibold text-emerald-600">
                                        Pembiayaan sudah lunas
                                    </p>

                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <div class="flex justify-end">

                                    @if (
                                        $isLegacyActive
                                        && \Illuminate\Support\Facades\Route::has(
                                            'manual-installments.create'
                                        )
                                    )

                                        <a
                                            href="{{ route(
                                                'manual-installments.create',
                                                [
                                                    'loan_id' => $loan->id,
                                                ]
                                            ) }}"
                                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">

                                            <i data-lucide="circle-plus" class="h-4 w-4"></i>

                                            Tambah Angsuran
                                        </a>

                                    @elseif (
                                        $loan?->status === 'active'
                                        && $installment->status !== 'paid'
                                    )

                                        <a
                                            href="{{ route(
                                                'installments.pay',
                                                $installment
                                            ) }}"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700">

                                            <i data-lucide="hand-coins" class="h-4 w-4"></i>

                                            Bayar
                                        </a>

                                    @else

                                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-semibold text-emerald-600">

                                            <i data-lucide="circle-check" class="h-4 w-4"></i>

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
                                    Belum ada data angsuran
                                </h4>

                                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
                                    Setujui pembiayaan baru untuk membuat
                                    jadwal atau gunakan tombol Tambah Angsuran
                                    Manual untuk data hasil migrasi.
                                </p>

                                @if (
                                    \Illuminate\Support\Facades\Route::has(
                                        'manual-installments.create'
                                    )
                                )

                                    <a
                                        href="{{ route(
                                            'manual-installments.create'
                                        ) }}"
                                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                                        <i data-lucide="circle-plus" class="h-5 w-5"></i>

                                        Tambah Angsuran Manual
                                    </a>

                                @endif

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

                        <th class="px-6 py-4">
                            Kuitansi
                        </th>

                        <th class="px-6 py-4">
                            Anggota
                        </th>

                        <th class="px-6 py-4">
                            Pembiayaan
                        </th>

                        <th class="px-6 py-4">
                            Tanggal
                        </th>

                        <th class="px-6 py-4">
                            Metode
                        </th>

                        <th class="px-6 py-4 text-right">
                            Nominal
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($recentPayments as $payment)

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4">

                                <a
                                    href="{{ route(
                                        'installment-payments.show',
                                        $payment
                                    ) }}"
                                    class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">

                                    {{ $payment->payment_code }}
                                </a>

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $payment
                                        ->installment
                                        ?->loan
                                        ?->member
                                        ?->name ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $payment
                                        ->installment
                                        ?->loan
                                        ?->member
                                        ?->member_number ?? '-' }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm text-slate-700">
                                    {{ $payment
                                        ->installment
                                        ?->loan
                                        ?->loan_number ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Angsuran ke-{{ $payment
                                        ->installment
                                        ?->installment_number ?? '-' }}
                                </p>

                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                {{ $payment->payment_date
                                    ?->translatedFormat('d M Y')
                                    ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ $payment->payment_method_label }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-emerald-600">
                                Rp{{ number_format(
                                    (float) $payment->amount,
                                    0,
                                    ',',
                                    '.'
                                ) }}
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
BLADE
