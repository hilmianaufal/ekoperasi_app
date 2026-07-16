@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('page-title', 'Laporan Keuangan')
@section('page-description', 'Laporan posisi keuangan dan rekonsiliasi SAK EP')

@section('content')

    <section class="rounded-3xl bg-gradient-to-br from-blue-700 to-slate-950 p-7 text-white shadow-lg">

        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200">
            Financial Reporting
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Laporan Keuangan Koperasi
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-blue-100">
            Periksa posisi aset, liabilitas, ekuitas, serta selisih antara data aplikasi dan laporan keuangan client.
        </p>

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-6">

            <h3 class="font-bold text-slate-900">
                Periode Laporan
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Pilih periode untuk melihat hasil rekonsiliasi.
            </p>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Tanggal Laporan</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($periods as $period)

                        @php
                            $statusClass = match ($period->status) {
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'review' => 'bg-amber-100 text-amber-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/70">

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $period->code }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $period->report_date->translatedFormat('d F Y') }}
                                </p>

                            </td>

                            <td class="px-6 py-4">

                                <p class="max-w-xl text-sm text-slate-600">
                                    {{ $period->notes ?: '-' }}
                                </p>

                            </td>

                            <td class="px-6 py-4 text-center">

                                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $period->status_label }}
                                </span>

                            </td>

                            <td class="px-6 py-4 text-right">

                                <a
                                    href="{{ route(
                                        'financial-statements.show',
                                        $period
                                    ) }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-blue-700">

                                    <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                                    Buka Laporan
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">
                                Belum ada periode laporan keuangan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($periods->hasPages())
            <div class="border-t border-slate-200 p-6">
                {{ $periods->links() }}
            </div>
        @endif

    </section>

@endsection
