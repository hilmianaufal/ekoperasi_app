@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan Koperasi')
@section('page-description', 'Rekap dan export seluruh aktivitas koperasi')

@section('content')

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">

        <div class="flex items-center gap-3">

            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                <i data-lucide="file-chart-column" class="h-6 w-6"></i>
            </div>

            <div>
                <h3 class="font-bold text-slate-900">
                    Filter Laporan
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Pilih jenis laporan dan periode yang akan ditampilkan.
                </p>
            </div>

        </div>

        <form
            action="{{ route('reports.index') }}"
            method="GET"
            class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">
                    Jenis laporan
                </label>

                <select
                    name="report_type"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    @foreach ($reportTypes as $type => $label)
                        <option
                            value="{{ $type }}"
                            @selected($reportType === $type)>

                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">
                    Tanggal mulai
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">
                    Tanggal selesai
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">
                    {{ $statusLabel }}
                </label>

                <select
                    name="status"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">

                    <option value="">Semua</option>

                    @foreach ($statusOptions as $optionValue => $optionLabel)
                        <option
                            value="{{ $optionValue }}"
                            @selected($status === $optionValue)>

                            {{ $optionLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold text-slate-600">
                    Pencarian
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari data laporan..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500">
            </div>

            <div class="flex flex-col gap-3 md:col-span-2 md:flex-row xl:col-span-5 xl:justify-end">

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">

                    <i data-lucide="list-filter" class="h-5 w-5"></i>
                    Tampilkan
                </button>

                <a
                    href="{{ route('reports.index', [
                        'report_type' => $reportType,
                    ]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                    <i data-lucide="rotate-ccw" class="h-5 w-5"></i>
                    Reset
                </a>

                <a
                    href="{{ route('reports.print', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100">

                    <i data-lucide="printer" class="h-5 w-5"></i>
                    Cetak / PDF
                </a>

                <a
                    href="{{ route('reports.export', request()->query()) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="sheet" class="h-5 w-5"></i>
                    Export Excel
                </a>

            </div>

        </form>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        @foreach ($summaryCards as $card)

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="flex items-center justify-between gap-4">

                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <h3 class="mt-2 text-xl font-bold text-slate-900">
                            @if ($card['format'] === 'currency')
                                Rp{{ number_format($card['value'], 0, ',', '.') }}
                            @else
                                {{ number_format($card['value'], 0, ',', '.') }}
                            @endif
                        </h3>
                    </div>

                    <div class="shrink-0 rounded-2xl p-3 {{ $card['class'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-6 w-6"></i>
                    </div>

                </div>

            </article>

        @endforeach

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col justify-between gap-3 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

            <div>
                <h3 class="font-bold text-slate-900">
                    {{ $reportTitle }}
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Periode
                    {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d F Y') }}
                    sampai
                    {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d F Y') }}
                </p>
            </div>

            <span class="w-fit rounded-full bg-emerald-100 px-4 py-2 text-xs font-semibold text-emerald-700">
                {{ number_format($rows->total(), 0, ',', '.') }} data
            </span>

        </div>

        @include('reports._table')

        @if ($rows->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $rows->links() }}
            </div>
        @endif

    </section>

@endsection
