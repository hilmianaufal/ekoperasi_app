@extends('layouts.app')

@section('title', 'Detail SHU')
@section('page-title', 'Detail Periode SHU')
@section('page-description', 'Perhitungan, persetujuan, dan pembayaran SHU anggota')

@section('content')

    @php
        $statusClass = match ($shuPeriod->status) {
            'draft' => 'bg-white/15 text-white',
            'review' => 'bg-amber-400/20 text-amber-100',
            'approved' => 'bg-blue-400/20 text-blue-100',
            'distributed' => 'bg-emerald-400/20 text-emerald-100',
            default => 'bg-white/15 text-white',
        };
    @endphp

    <a href="{{ route('shu-periods.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-emerald-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke periode SHU
    </a>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a href="{{ route('shu-periods.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke periode SHU
        </a>

        @if ((int) $summary['member_count'] > 0)
            <a href="{{ route('shu-reports.show', $shuPeriod) }}"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">

                <i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i>
                Laporan SHU
            </a>
        @endif

    </div>
    {{-- HEADER --}}
    <section
        class="mt-6 overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-emerald-800 to-slate-950 p-7 text-white shadow-lg">

        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">

            <div>

                <div class="flex flex-wrap items-center gap-3">

                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">
                        {{ $shuPeriod->code }}
                    </p>

                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $shuPeriod->status_label }}
                    </span>

                </div>

                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                    Sisa Hasil Usaha {{ $shuPeriod->year }}
                </h1>

                <p class="mt-3 text-sm text-emerald-100">

                    Perhitungan per

                    <strong>
                        {{ $shuPeriod->calculation_date?->translatedFormat('d F Y') ?? 'Belum tersedia' }}
                    </strong>

                </p>

            </div>

            <div class="grid grid-cols-2 gap-3 sm:min-w-80">

                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">

                    <p class="text-xs text-emerald-100">
                        Tarif JASUS
                    </p>

                    <p class="mt-1 text-2xl font-bold">
                        {{ number_format((float) $shuPeriod->business_service_rate, 2, ',', '.') }}%
                    </p>

                </div>

                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">

                    <p class="text-xs text-emerald-100">
                        Tarif JASIM
                    </p>

                    <p class="mt-1 text-2xl font-bold">
                        {{ number_format((float) $shuPeriod->saving_service_rate, 2, ',', '.') }}%
                    </p>

                </div>

            </div>

        </div>

    </section>

    {{-- RINGKASAN --}}
    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm text-slate-500">
                        Ketetapan SHU Anggota
                    </p>

                    <p class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format((float) $shuPeriod->declared_member_shu, 0, ',', '.') }}
                    </p>

                </div>

                <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                    <i data-lucide="landmark" class="h-5 w-5"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm text-blue-700">
                        Hasil Alokasi
                    </p>

                    <p class="mt-2 text-xl font-bold text-blue-700">
                        Rp{{ number_format((float) $summary['allocated_total'], 0, ',', '.') }}
                    </p>

                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="pie-chart" class="h-5 w-5"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm text-emerald-700">
                        Sudah Dibayar
                    </p>

                    <p class="mt-2 text-xl font-bold text-emerald-700">
                        Rp{{ number_format((float) $summary['paid_total'], 0, ',', '.') }}
                    </p>

                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
                </div>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <p class="text-sm text-slate-500">
                        Jumlah Anggota
                    </p>

                    <p class="mt-2 text-3xl font-bold text-slate-900">
                        {{ number_format((int) $summary['member_count'], 0, ',', '.') }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-100 p-3 text-slate-600">
                    <i data-lucide="users" class="h-5 w-5"></i>
                </div>

            </div>

        </article>

    </section>

    {{-- REKONSILIASI --}}
    @if ($summary['member_count'] > 0)
        <section class="mt-7 grid gap-5 lg:grid-cols-3">

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total JASUS
                </p>

                <p class="mt-2 text-xl font-bold text-slate-900">
                    Rp{{ number_format((float) $summary['business_service'], 0, ',', '.') }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total JASIM
                </p>

                <p class="mt-2 text-xl font-bold text-slate-900">
                    Rp{{ number_format((float) $summary['saving_service'], 0, ',', '.') }}
                </p>

            </article>

            <article
                class="rounded-3xl border p-5 shadow-sm
                    {{ abs((float) $summary['difference']) >= 0.01
                        ? 'border-amber-200 bg-amber-50'
                        : 'border-emerald-200 bg-emerald-50' }}">

                <p
                    class="text-sm
                        {{ abs((float) $summary['difference']) >= 0.01 ? 'text-amber-700' : 'text-emerald-700' }}">

                    Selisih Alokasi
                </p>

                <p
                    class="mt-2 text-xl font-bold
                        {{ abs((float) $summary['difference']) >= 0.01 ? 'text-amber-700' : 'text-emerald-700' }}">

                    Rp{{ number_format((float) $summary['difference'], 0, ',', '.') }}
                </p>

            </article>

        </section>
    @endif

    {{-- PANEL PERSETUJUAN --}}
    @include('shu-periods.partials.approval-panel')

    {{-- UPLOAD IMPORT --}}
    @include('shu-periods.partials.allocations-section')
    @if (!in_array($shuPeriod->status, ['approved', 'distributed'], true) && (int) $summary['member_count'] === 0)
        <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="file-up" class="h-6 w-6"></i>
                </div>

                <div>

                    <h3 class="font-bold text-slate-900">
                        Import Rincian SHU Anggota
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Gunakan file Excel rincian SHU anggota.
                    </p>

                </div>

            </div>

            <form
                action="{{ route('shu-imports.store', $shuPeriod) }}"
                method="POST" enctype="multipart/form-data" class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-end">

                @csrf

                <div class="flex-1">

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        File Excel SHU
                    </label>

                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="block w-full rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-emerald-700">

                    @error('file')
                        <p class="mt-2 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-700">

                    <i data-lucide="scan-search" class="h-5 w-5"></i>
                    Baca dan Preview
                </button>

            </form>

        </section>
    @endif

    {{-- RIWAYAT IMPORT --}}
    @if ($importBatches->isNotEmpty())

        <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

                <div>

                    <h3 class="font-bold text-slate-900">
                        Riwayat Import SHU
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Riwayat file yang digunakan untuk alokasi SHU.
                    </p>

                </div>

                <span class="w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    {{ $importBatches->count() }} batch
                </span>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                            <th class="px-6 py-4">
                                Batch
                            </th>

                            <th class="px-6 py-4">
                                File
                            </th>

                            <th class="px-6 py-4 text-center">
                                Data
                            </th>

                            <th class="px-6 py-4 text-center">
                                Status
                            </th>

                            <th class="px-6 py-4 text-right">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach ($importBatches as $batch)
                            @php
                                $batchStatusClass = match ($batch->status) {
                                    'uploaded' => 'bg-slate-100 text-slate-600',
                                    'previewed' => 'bg-amber-100 text-amber-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-slate-100 text-slate-600',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <tr class="transition hover:bg-slate-50/70">

                                <td class="px-6 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $batch->code }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $batch->created_at->translatedFormat('d M Y H:i') }}
                                    </p>

                                </td>

                                <td class="px-6 py-4">

                                    <p class="max-w-64 truncate text-sm text-slate-700">
                                        {{ $batch->original_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Oleh {{ $batch->user?->name ?? 'Administrator' }}
                                    </p>

                                </td>

                                <td class="px-6 py-4 text-center">

                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ number_format((int) $batch->row_count, 0, ',', '.') }}
                                        baris
                                    </p>

                                    @if ((int) $batch->review_count > 0)
                                        <p class="mt-1 text-xs font-semibold text-amber-600">
                                            {{ $batch->review_count }}
                                            perlu diperiksa
                                        </p>
                                    @else
                                        <p class="mt-1 text-xs font-semibold text-emerald-600">
                                            Semua data cocok
                                        </p>
                                    @endif

                                </td>

                                <td class="px-6 py-4 text-center">

                                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $batchStatusClass }}">
                                        {{ $batch->status_label }}
                                    </span>

                                </td>

                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        <a href="{{ route('shu-imports.show', $batch) }}"
                                            title="Lihat import"
                                            class="inline-flex rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600">

                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </a>

                                    </div>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>

    @endif

    {{-- ALOKASI DAN PEMBAYARAN --}}
    @include('shu-periods.partials.allocations-section')

@endsection
