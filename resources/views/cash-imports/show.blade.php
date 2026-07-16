@extends('layouts.app')

@section('title', 'Preview Import Kas')
@section('page-title', 'Preview Import Kas')
@section('page-description', 'Periksa rekapan kas sebelum dimasukkan ke buku kas')

@section('content')

    <a
        href="{{ route('cash-imports.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-blue-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke import kas
    </a>

    <section class="mt-6 rounded-3xl bg-gradient-to-br from-blue-700 to-slate-900 p-7 text-white">

        <p class="text-xs font-semibold uppercase tracking-wider text-blue-200">
            {{ $cashImportBatch->code }}
        </p>

        <h1 class="mt-2 text-2xl font-bold">
            {{ $cashImportBatch->original_name }}
        </h1>

        <p class="mt-2 text-sm text-blue-100">
            Data utama:
            {{ $cashImportBatch->dataImportBatch?->code }}
            · Cut-off:
            {{ $cashImportBatch->cutoff_date->translatedFormat('d F Y') }}
        </p>

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-6">
            <h3 class="font-bold text-slate-900">
                Ringkasan Per Bulan
            </h3>
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="px-5 py-4">Periode</th>
                        <th class="px-5 py-4 text-right">Pembiayaan</th>
                        <th class="px-5 py-4 text-right">Angsuran</th>
                        <th class="px-5 py-4 text-right">Bagi Hasil</th>
                        <th class="px-5 py-4 text-right">Administrasi</th>
                        <th class="px-5 py-4 text-right">Transport</th>
                        <th class="px-5 py-4 text-right">Biaya Lain</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @foreach ($monthlySummaries as $summary)

                        <tr>

                            <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                                {{ $summary->sheet_name }}
                            </td>

                            @foreach ([
                                'financing_expense',
                                'installment_income',
                                'profit_share_income',
                                'administration_income',
                                'transport_expense',
                                'other_expense',
                            ] as $field)

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format(
                                        (float) $summary->{$field},
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                            @endforeach

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </section>

    @if (!$cashImportBatch->processed_at)

        <section class="mt-7 rounded-3xl border border-blue-200 bg-blue-50 p-6">

            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">

                <div>
                    <h3 class="font-bold text-blue-900">
                        Proses Import Kas
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-blue-700">
                        Sistem akan membuat buku kas dari transaksi anggota dan mengimpor biaya transportasi serta biaya operasional lainnya.
                    </p>
                </div>

                <form
                    action="{{ route('cash-imports.process', $cashImportBatch) }}"
                    method="POST"
                    id="process-cash-form">

                    @csrf

                    <button
                        type="button"
                        onclick="confirmProcessCash()"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white hover:bg-blue-700">

                        <i data-lucide="database-zap" class="h-5 w-5"></i>
                        Proses Import Kas
                    </button>

                </form>

            </div>

        </section>

    @else

        @php
            $summary = $reconciliation['summary'];
        @endphp

        <section class="mt-7 grid gap-5 sm:grid-cols-3">

            <article class="rounded-3xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Pemeriksaan</p>
                <p class="mt-2 text-3xl font-bold">
                    {{ $summary['metric_count'] }}
                </p>
            </article>

            <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-sm text-emerald-700">Sesuai</p>
                <p class="mt-2 text-3xl font-bold text-emerald-700">
                    {{ $summary['matched_count'] }}
                </p>
            </article>

            <article class="rounded-3xl border border-red-200 bg-red-50 p-5">
                <p class="text-sm text-red-700">Berbeda</p>
                <p class="mt-2 text-3xl font-bold text-red-700">
                    {{ $summary['difference_count'] }}
                </p>
            </article>

        </section>

        <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white">

            <div class="border-b border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">
                    Rekonsiliasi Kas
                </h3>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="px-6 py-4">Komponen</th>
                            <th class="px-6 py-4 text-right">Excel</th>
                            <th class="px-6 py-4 text-right">Aplikasi</th>
                            <th class="px-6 py-4 text-right">Selisih</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach ($reconciliation['metrics'] as $metric)

                            <tr class="{{ !$metric['matched'] ? 'bg-red-50/60' : '' }}">

                                <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                    {{ $metric['label'] }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm">
                                    Rp{{ number_format($metric['source'], 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm">
                                    Rp{{ number_format($metric['actual'], 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 text-right text-sm font-semibold {{ $metric['matched'] ? 'text-slate-400' : 'text-red-600' }}">
                                    Rp{{ number_format($metric['difference'], 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 text-center">

                                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $metric['matched'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $metric['matched'] ? 'Sesuai' : 'Berbeda' }}
                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>

        <div class="mt-7 flex justify-end">

            <a
                href="{{ route('cash-transactions.index') }}"
                class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                Lihat Buku Kas
            </a>

        </div>

    @endif

@endsection

@push('scripts')

    <script>
        function confirmProcessCash() {
            Swal.fire({
                icon: 'warning',
                title: 'Proses import kas?',
                html: `
                    <div class="text-left text-sm leading-7">
                        <p>Sistem akan membuat:</p>
                        <ul class="mt-2 list-inside list-disc">
                            <li>Kas masuk simpanan</li>
                            <li>Kas keluar pencairan pembiayaan</li>
                            <li>Kas masuk angsuran dan bagi hasil</li>
                            <li>Pendapatan administrasi</li>
                            <li>Biaya transportasi dan operasional</li>
                        </ul>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document
                        .getElementById('process-cash-form')
                        .submit();
                }
            });
        }
    </script>

@endpush
