@extends('layouts.app')

@section('title', 'Rekapan Bulanan')
@section('page-title', 'Rekapan Bulanan')
@section('page-description', 'Lihat dan export laporan transaksi koperasi setiap bulan')

@section('content')

    @php
        $selectedType = $filters['type'];
        $selectedTitle = $reports[$selectedType] ?? 'Rekapan Bulanan';

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $reportMeta = [
            'installments' => [
                'icon' => 'hand-coins',
                'description' => 'Pokok, bagi hasil, dan total angsuran anggota.',
                'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                'active' => 'border-emerald-500 bg-emerald-600 text-white shadow-emerald-200',
            ],
            'profit-share' => [
                'icon' => 'badge-percent',
                'description' => 'Pendapatan bagi hasil dari pembayaran angsuran.',
                'class' => 'border-amber-200 bg-amber-50 text-amber-700',
                'active' => 'border-amber-500 bg-amber-500 text-white shadow-amber-200',
            ],
            'administration' => [
                'icon' => 'receipt-text',
                'description' => 'Administrasi pinjaman yang diterima koperasi.',
                'class' => 'border-blue-200 bg-blue-50 text-blue-700',
                'active' => 'border-blue-500 bg-blue-600 text-white shadow-blue-200',
            ],
            'principal-savings' => [
                'icon' => 'wallet-cards',
                'description' => 'Setoran simpanan pokok anggota.',
                'class' => 'border-violet-200 bg-violet-50 text-violet-700',
                'active' => 'border-violet-500 bg-violet-600 text-white shadow-violet-200',
            ],
            'mandatory-savings' => [
                'icon' => 'calendar-check-2',
                'description' => 'Setoran simpanan wajib anggota.',
                'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
                'active' => 'border-indigo-500 bg-indigo-600 text-white shadow-indigo-200',
            ],
            'voluntary-savings' => [
                'icon' => 'piggy-bank',
                'description' => 'Setoran simpanan sukarela anggota.',
                'class' => 'border-teal-200 bg-teal-50 text-teal-700',
                'active' => 'border-teal-500 bg-teal-600 text-white shadow-teal-200',
            ],
            'loans' => [
                'icon' => 'landmark',
                'description' => 'Pinjaman yang dicairkan pada periode laporan.',
                'class' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
                'active' => 'border-cyan-500 bg-cyan-600 text-white shadow-cyan-200',
            ],
            'transport-expenses' => [
                'icon' => 'car-front',
                'description' => 'Pengeluaran kategori transportasi.',
                'class' => 'border-orange-200 bg-orange-50 text-orange-700',
                'active' => 'border-orange-500 bg-orange-600 text-white shadow-orange-200',
            ],
            'other-expenses' => [
                'icon' => 'circle-minus',
                'description' => 'Pengeluaran kategori lain-lain.',
                'class' => 'border-red-200 bg-red-50 text-red-700',
                'active' => 'border-red-500 bg-red-600 text-white shadow-red-200',
            ],
            'voluntary-withdrawals' => [
                'icon' => 'arrow-down-to-line',
                'description' => 'Penarikan simpanan sukarela anggota.',
                'class' => 'border-rose-200 bg-rose-50 text-rose-700',
                'active' => 'border-rose-500 bg-rose-600 text-white shadow-rose-200',
            ],
            'mandatory-withdrawals' => [
                'icon' => 'undo-2',
                'description' => 'Penarikan simpanan wajib anggota.',
                'class' => 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700',
                'active' => 'border-fuchsia-500 bg-fuchsia-600 text-white shadow-fuchsia-200',
            ],
        ];

        $expenseTypes = [
            'transport-expenses',
            'other-expenses',
        ];

        $showMember = !in_array($selectedType, $expenseTypes, true);
        $showPrincipal = in_array($selectedType, ['installments', 'loans'], true);
        $showProfitShare = in_array($selectedType, ['installments', 'profit-share'], true);
        $showAdministration = $selectedType === 'administration';

        $columnCount = 7
            + ($showMember ? 1 : 0)
            + ($showPrincipal ? 1 : 0)
            + ($showProfitShare ? 1 : 0)
            + ($showAdministration ? 1 : 0);

        $exportParameters = array_filter([
            'type' => $filters['type'],
            'month' => $filters['month'],
            'year' => $filters['year'],
            'member_id' => $filters['member_id'],
        ], fn ($value) => $value !== null && $value !== '');

        $summaryCards = [
            [
                'label' => 'Jumlah Transaksi',
                'value' => number_format($summary['transaction_count'], 0, ',', '.'),
                'icon' => 'receipt-text',
                'class' => 'bg-slate-100 text-slate-700',
            ],
            [
                'label' => 'Total Nominal',
                'value' => 'Rp' . number_format($summary['total_amount'], 0, ',', '.'),
                'icon' => 'circle-dollar-sign',
                'class' => 'bg-emerald-100 text-emerald-700',
            ],
        ];

        if ($showPrincipal) {
            $summaryCards[] = [
                'label' => 'Total Pokok',
                'value' => 'Rp' . number_format($summary['principal_total'], 0, ',', '.'),
                'icon' => 'landmark',
                'class' => 'bg-blue-100 text-blue-700',
            ];
        }

        if ($showProfitShare) {
            $summaryCards[] = [
                'label' => 'Total Bagi Hasil',
                'value' => 'Rp' . number_format($summary['profit_share_total'], 0, ',', '.'),
                'icon' => 'badge-percent',
                'class' => 'bg-amber-100 text-amber-700',
            ];
        }

        if ($showAdministration) {
            $summaryCards[] = [
                'label' => 'Total Administrasi',
                'value' => 'Rp' . number_format($summary['administration_total'], 0, ',', '.'),
                'icon' => 'receipt-text',
                'class' => 'bg-violet-100 text-violet-700',
            ];
        }

        if (count($summaryCards) < 4) {
            $summaryCards[] = [
                'label' => 'Periode Laporan',
                'value' => $periodStart->translatedFormat('F Y'),
                'icon' => 'calendar-days',
                'class' => 'bg-cyan-100 text-cyan-700',
            ];
        }
    @endphp

    <div
        class="space-y-7"
        x-data="{
            reportType: @js($filters['type']),
            expenseTypes: ['transport-expenses', 'other-expenses'],

            get memberFilterAvailable() {
                return !this.expenseTypes.includes(this.reportType);
            }
        }">

        {{-- Pilihan jenis rekapan --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">

            <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">

                <div>

                    <h2 class="font-bold text-slate-900">
                        Pilih Jenis Rekapan
                    </h2>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Pilih laporan yang ingin dilihat untuk periode bulanan tertentu.
                    </p>

                </div>

                <a
                    href="{{ route('monthly-reports.excel', $exportParameters) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700">

                    <i data-lucide="file-spreadsheet" class="h-5 w-5"></i>

                    Export Excel
                </a>

            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                @foreach ($reports as $type => $reportTitle)

                    @php
                        $meta = $reportMeta[$type];
                        $isActive = $selectedType === $type;

                        $reportParameters = array_filter([
                            'type' => $type,
                            'month' => $filters['month'],
                            'year' => $filters['year'],
                            'member_id' => in_array($type, $expenseTypes, true)
                                ? null
                                : $filters['member_id'],
                        ], fn ($value) => $value !== null && $value !== '');
                    @endphp

                    <a
                        href="{{ route('monthly-reports.index', $reportParameters) }}"
                        @class([
                            'group rounded-3xl border p-5 transition hover:-translate-y-0.5 hover:shadow-lg',
                            $meta['active'] . ' shadow-lg' => $isActive,
                            $meta['class'] => !$isActive,
                        ])>

                        <div class="flex items-start gap-4">

                            <div
                                @class([
                                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl transition',
                                    'bg-white/15 text-white' => $isActive,
                                    'bg-white shadow-sm' => !$isActive,
                                ])>

                                <i
                                    data-lucide="{{ $meta['icon'] }}"
                                    class="h-6 w-6">
                                </i>

                            </div>

                            <div class="min-w-0">

                                <h3 class="text-sm font-bold">
                                    {{ $reportTitle }}
                                </h3>

                                <p
                                    @class([
                                        'mt-2 text-xs leading-5',
                                        'text-white/80' => $isActive,
                                        'opacity-80' => !$isActive,
                                    ])>

                                    {{ $meta['description'] }}

                                </p>

                            </div>

                        </div>

                    </a>

                @endforeach

            </div>

        </section>

        {{-- Filter laporan --}}
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">

            <form
                action="{{ route('monthly-reports.index') }}"
                method="GET"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">

                <div class="xl:col-span-2">

                    <label
                        for="type"
                        class="mb-2 block text-xs font-semibold text-slate-600">

                        Jenis rekapan
                    </label>

                    <select
                        name="type"
                        id="type"
                        x-model="reportType"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                        @foreach ($reports as $type => $reportTitle)

                            <option
                                value="{{ $type }}"
                                @selected($filters['type'] === $type)>

                                {{ $reportTitle }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label
                        for="month"
                        class="mb-2 block text-xs font-semibold text-slate-600">

                        Bulan
                    </label>

                    <select
                        name="month"
                        id="month"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                        @foreach ($months as $monthNumber => $monthName)

                            <option
                                value="{{ $monthNumber }}"
                                @selected((int) $filters['month'] === $monthNumber)>

                                {{ $monthName }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label
                        for="year"
                        class="mb-2 block text-xs font-semibold text-slate-600">

                        Tahun
                    </label>

                    <input
                        type="number"
                        name="year"
                        id="year"
                        value="{{ $filters['year'] }}"
                        min="2000"
                        max="{{ now()->year + 1 }}"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                </div>

                <div
                    x-show="memberFilterAvailable"
                    x-cloak>

                    <label
                        for="member_id"
                        class="mb-2 block text-xs font-semibold text-slate-600">

                        Anggota
                    </label>

                    <select
                        name="member_id"
                        id="member_id"
                        x-bind:disabled="!memberFilterAvailable"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 disabled:cursor-not-allowed disabled:opacity-50">

                        <option value="">
                            Semua anggota
                        </option>

                        @foreach ($members as $member)

                            <option
                                value="{{ $member->id }}"
                                @selected(
                                    (string) $filters['member_id']
                                    === (string) $member->id
                                )>

                                {{ $member->member_number }}
                                —
                                {{ $member->name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="flex flex-col gap-3 md:flex-row xl:col-span-5 xl:justify-end">

                    <a
                        href="{{ route('monthly-reports.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">

                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>

                        Reset
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">

                        <i data-lucide="search" class="h-4 w-4"></i>

                        Tampilkan Laporan
                    </button>

                </div>

            </form>

        </section>

        {{-- Statistik --}}
        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            @foreach ($summaryCards as $card)

                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="flex items-center justify-between gap-4">

                        <div class="min-w-0">

                            <p class="text-sm font-medium text-slate-500">
                                {{ $card['label'] }}
                            </p>

                            <h3 class="mt-2 truncate text-xl font-bold text-slate-900">
                                {{ $card['value'] }}
                            </h3>

                        </div>

                        <div class="rounded-2xl p-3 {{ $card['class'] }}">

                            <i
                                data-lucide="{{ $card['icon'] }}"
                                class="h-6 w-6">
                            </i>

                        </div>

                    </div>

                </article>

            @endforeach

        </section>

        {{-- Tabel laporan --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-5 md:p-6">

                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">

                    <div>

                        <div class="flex flex-wrap items-center gap-3">

                            <h2 class="text-lg font-bold text-slate-900">
                                {{ $selectedTitle }}
                            </h2>

                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ $periodStart->translatedFormat('F Y') }}
                            </span>

                        </div>

                        <p class="mt-2 text-xs leading-5 text-slate-500">

                            @if ($filters['member_id'])

                                Laporan telah difilter berdasarkan anggota yang dipilih.

                            @else

                                Menampilkan transaksi seluruh anggota pada periode ini.

                            @endif

                        </p>

                    </div>

                    <a
                        href="{{ route('monthly-reports.excel', $exportParameters) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">

                        <i data-lucide="download" class="h-5 w-5"></i>

                        Download Excel
                    </a>

                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">

                            <th class="whitespace-nowrap px-5 py-4 text-center">
                                No
                            </th>

                            <th class="whitespace-nowrap px-5 py-4">
                                Tanggal
                            </th>

                            <th class="whitespace-nowrap px-5 py-4">
                                Kode Transaksi
                            </th>

                            @if ($showMember)

                                <th class="whitespace-nowrap px-5 py-4">
                                    Anggota
                                </th>

                            @endif

                            <th class="whitespace-nowrap px-5 py-4">
                                Referensi
                            </th>

                            <th class="min-w-[240px] px-5 py-4">
                                Keterangan
                            </th>

                            @if ($showPrincipal)

                                <th class="whitespace-nowrap px-5 py-4 text-right">
                                    Pokok
                                </th>

                            @endif

                            @if ($showProfitShare)

                                <th class="whitespace-nowrap px-5 py-4 text-right">
                                    Bagi Hasil
                                </th>

                            @endif

                            @if ($showAdministration)

                                <th class="whitespace-nowrap px-5 py-4 text-right">
                                    Administrasi
                                </th>

                            @endif

                            <th class="whitespace-nowrap px-5 py-4 text-right">
                                Nominal
                            </th>

                            <th class="whitespace-nowrap px-5 py-4">
                                Petugas
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($paginatedRows as $row)

                            @php
                                $rowDate = $row['date'] instanceof \Carbon\Carbon
                                    ? $row['date']
                                    : \Carbon\Carbon::parse($row['date']);
                            @endphp

                            <tr class="align-top transition hover:bg-slate-50">

                                <td class="whitespace-nowrap px-5 py-4 text-center text-sm text-slate-500">
                                    {{ $paginatedRows->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">

                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $rowDate->translatedFormat('d M Y') }}
                                    </p>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4">

                                    <p class="text-sm font-bold text-slate-800">
                                        {{ $row['code'] }}
                                    </p>

                                </td>

                                @if ($showMember)

                                    <td class="px-5 py-4">

                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $row['member_name'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $row['member_number'] }}
                                        </p>

                                    </td>

                                @endif

                                <td class="px-5 py-4">

                                    <span class="inline-flex rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                        {{ $row['reference'] }}
                                    </span>

                                </td>

                                <td class="px-5 py-4">

                                    <p class="text-sm leading-6 text-slate-600">
                                        {{ $row['description'] }}
                                    </p>

                                </td>

                                @if ($showPrincipal)

                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-blue-700">
                                        Rp{{ number_format($row['principal_amount'], 0, ',', '.') }}
                                    </td>

                                @endif

                                @if ($showProfitShare)

                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-amber-700">
                                        Rp{{ number_format($row['profit_share_amount'], 0, ',', '.') }}
                                    </td>

                                @endif

                                @if ($showAdministration)

                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-violet-700">
                                        Rp{{ number_format($row['administration_amount'], 0, ',', '.') }}
                                    </td>

                                @endif

                                <td class="whitespace-nowrap px-5 py-4 text-right">

                                    <p class="text-sm font-bold text-emerald-700">
                                        Rp{{ number_format($row['amount'], 0, ',', '.') }}
                                    </p>

                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="text-sm font-medium text-slate-600">
                                        {{ $row['user_name'] }}
                                    </span>
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="{{ $columnCount }}"
                                    class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                                        <i data-lucide="file-search" class="h-7 w-7"></i>

                                    </div>

                                    <h3 class="mt-4 font-bold text-slate-800">
                                        Data laporan belum tersedia
                                    </h3>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Tidak ada transaksi untuk jenis laporan dan periode yang dipilih.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                    @if ($paginatedRows->isNotEmpty())

                        <tfoot class="border-t-2 border-emerald-200 bg-emerald-50">

                            <tr class="text-sm font-bold text-emerald-900">

                                <td
                                    colspan="{{ 5 + ($showMember ? 1 : 0) }}"
                                    class="px-5 py-4 text-right">

                                    TOTAL PERIODE
                                </td>

                                @if ($showPrincipal)

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        Rp{{ number_format($summary['principal_total'], 0, ',', '.') }}
                                    </td>

                                @endif

                                @if ($showProfitShare)

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        Rp{{ number_format($summary['profit_share_total'], 0, ',', '.') }}
                                    </td>

                                @endif

                                @if ($showAdministration)

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        Rp{{ number_format($summary['administration_total'], 0, ',', '.') }}
                                    </td>

                                @endif

                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    Rp{{ number_format($summary['total_amount'], 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4"></td>

                            </tr>

                        </tfoot>

                    @endif

                </table>

            </div>

            @if ($paginatedRows->hasPages())

                <div class="border-t border-slate-200 p-5 md:p-6">
                    {{ $paginatedRows->links() }}
                </div>

            @endif

        </section>

    </div>

@endsection
