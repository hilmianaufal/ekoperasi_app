@extends('layouts.app')

@section('title', 'Rekonsiliasi Import')
@section('page-title', 'Rekonsiliasi Import')
@section('page-description', 'Bandingkan data Excel dengan data yang tersimpan di aplikasi')

@section('content')

    @php
        $summary = $reconciliation['summary'];

        $formatValue = function (
            mixed $value,
            string $format
        ): string {
            if ($format === 'money') {
                return 'Rp' . number_format(
                    (float) $value,
                    0,
                    ',',
                    '.'
                );
            }

            return number_format(
                (int) $value,
                0,
                ',',
                '.'
            );
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a
            href="{{ route('data-imports.show', $importBatch) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke preview import
        </a>

        <button
            type="button"
            onclick="window.print()"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

            <i data-lucide="printer" class="h-5 w-5"></i>
            Cetak Rekonsiliasi
        </button>

    </div>

    <section
        class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-emerald-900 p-7 text-white">

        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">
                    {{ $importBatch->code }}
                </p>

                <h1 class="mt-2 text-2xl font-bold">
                    {{ $importBatch->original_name }}
                </h1>

                <p class="mt-2 text-sm text-emerald-100">
                    Cut-off:
                    {{ $importBatch->cutoff_date?->translatedFormat('d F Y') ?? 'Tidak tersedia' }}
                </p>

            </div>

            @if ($summary['all_matched'])

                <div class="flex w-fit items-center gap-3 rounded-2xl bg-emerald-400/20 px-5 py-4">

                    <div class="rounded-xl bg-emerald-400 p-2 text-emerald-950">
                        <i data-lucide="circle-check-big" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <p class="text-xs text-emerald-100">
                            Status Rekonsiliasi
                        </p>

                        <p class="mt-1 font-bold">
                            Semua Data Sesuai
                        </p>
                    </div>

                </div>

            @else

                <div class="flex w-fit items-center gap-3 rounded-2xl bg-amber-400/20 px-5 py-4">

                    <div class="rounded-xl bg-amber-400 p-2 text-amber-950">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <p class="text-xs text-amber-100">
                            Status Rekonsiliasi
                        </p>

                        <p class="mt-1 font-bold">
                            Ada Perbedaan Data
                        </p>
                    </div>

                </div>

            @endif

        </div>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-3">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-slate-500">
                        Total Pemeriksaan
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format($summary['metric_count'], 0, ',', '.') }}
                    </p>
                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="list-checks" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm text-emerald-700">
                        Data Sesuai
                    </p>

                    <p class="mt-2 text-3xl font-bold text-emerald-700">
                        {{ number_format($summary['matched_count'], 0, ',', '.') }}
                    </p>
                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="circle-check" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

        <article
            class="rounded-3xl border {{ $summary['difference_count'] > 0 ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm">

            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm {{ $summary['difference_count'] > 0 ? 'text-red-700' : 'text-slate-500' }}">
                        Data Berbeda
                    </p>

                    <p class="mt-2 text-3xl font-bold {{ $summary['difference_count'] > 0 ? 'text-red-700' : 'text-slate-900' }}">
                        {{ number_format($summary['difference_count'], 0, ',', '.') }}
                    </p>
                </div>

                <div class="rounded-2xl {{ $summary['difference_count'] > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-500' }} p-3">
                    <i data-lucide="circle-x" class="h-6 w-6"></i>
                </div>

            </div>

        </article>

    </section>

    @foreach ($reconciliation['sections'] as $section)

        <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">

                <h3 class="font-bold text-slate-900">
                    {{ $section['title'] }}
                </h3>

                <p class="mt-1 text-xs leading-6 text-slate-500">
                    {{ $section['description'] }}
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4">Komponen</th>
                            <th class="px-6 py-4 text-right">Data Excel</th>
                            <th class="px-6 py-4 text-right">Data Aplikasi</th>
                            <th class="px-6 py-4 text-right">Selisih</th>
                            <th class="px-6 py-4 text-center">Status</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach ($section['metrics'] as $metric)

                            <tr class="{{ !$metric['matched'] ? 'bg-red-50/50' : '' }}">

                                <td class="px-6 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $metric['label'] }}
                                    </p>

                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                    {{ $formatValue(
                                        $metric['source'],
                                        $metric['format']
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-800">
                                    {{ $formatValue(
                                        $metric['actual'],
                                        $metric['format']
                                    ) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <span class="text-sm font-semibold {{ $metric['matched'] ? 'text-slate-400' : 'text-red-600' }}">
                                        {{ $formatValue(
                                            $metric['difference'],
                                            $metric['format']
                                        ) }}
                                    </span>

                                </td>

                                <td class="px-6 py-4 text-center">

                                    @if ($metric['matched'])

                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-semibold text-emerald-700">

                                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                            Sesuai

                                        </span>

                                    @else

                                        <span class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">

                                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                            Berbeda

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>

    @endforeach

    @if ($summary['all_matched'])

        <section class="mt-7 rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

            <div class="flex gap-4">

                <div class="h-fit rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="shield-check" class="h-6 w-6"></i>
                </div>

                <div>

                    <h3 class="font-bold text-emerald-900">
                        Rekonsiliasi berhasil
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-emerald-700">
                        Seluruh angka yang dibaca dari Excel sudah sesuai dengan
                        data anggota, simpanan, pembiayaan, dan angsuran di aplikasi.
                    </p>

                </div>

            </div>

        </section>

    @else

        <section class="mt-7 rounded-3xl border border-red-200 bg-red-50 p-6">

            <div class="flex gap-4">

                <div class="h-fit rounded-2xl bg-red-100 p-3 text-red-600">
                    <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                </div>

                <div>

                    <h3 class="font-bold text-red-900">
                        Ditemukan perbedaan
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-red-700">
                        Periksa komponen berstatus berbeda sebelum data dipindahkan
                        ke server production atau digunakan sebagai laporan resmi.
                    </p>

                </div>

            </div>

        </section>

    @endif

    <p class="mt-6 text-center text-xs text-slate-400">
        Dibuat pada
        {{ $reconciliation['generated_at']->translatedFormat('d F Y H:i') }}
    </p>

@endsection

@push('styles')

    <style>
        @media print {
            aside,
            header,
            button,
            a {
                display: none !important;
            }

            main {
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: white !important;
            }

            section {
                break-inside: avoid;
                box-shadow: none !important;
            }
        }
    </style>

@endpush
