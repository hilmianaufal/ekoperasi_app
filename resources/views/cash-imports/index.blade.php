@extends('layouts.app')

@section('title', 'Import Kas Bulanan')
@section('page-title', 'Import Kas Bulanan')
@section('page-description', 'Impor biaya operasional dan rekonsiliasi kas koperasi')

@section('content')

    <div class="grid gap-7 xl:grid-cols-[420px_1fr]">

        <section class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="file-spreadsheet" class="h-6 w-6"></i>
                </div>

                <div>
                    <h3 class="font-bold text-slate-900">
                        Upload Rekapan Kas
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Gunakan REKAPAN BULANAN 2026.xlsx
                    </p>
                </div>

            </div>

            @if ($errors->any())

                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                    <ul class="list-inside list-disc space-y-1 text-xs text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            @endif

            <form
                action="{{ route('cash-imports.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-6 space-y-5">

                @csrf

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Batch Data Utama
                    </label>

                    <select
                        name="data_import_batch_id"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-blue-500">

                        <option value="">
                            Pilih batch import
                        </option>

                        @foreach ($dataImportBatches as $batch)

                            <option
                                value="{{ $batch->id }}"
                                @selected(
                                    old('data_import_batch_id')
                                    == $batch->id
                                )>

                                {{ $batch->code }}
                                — {{ $batch->original_name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        File Excel
                    </label>

                    <input
                        type="file"
                        name="file"
                        accept=".xlsx,.xls"
                        required
                        class="block w-full rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm">

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Tanggal cut-off
                    </label>

                    <input
                        type="date"
                        name="cutoff_date"
                        value="{{ old('cutoff_date', '2026-06-30') }}"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                    <p class="text-xs leading-6 text-amber-700">
                        File hanya dipakai untuk biaya operasional dan rekonsiliasi. Simpanan, angsuran, dan pembiayaan diambil dari import anggota agar tidak tercatat dua kali.
                    </p>

                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-semibold text-white hover:bg-blue-700">

                    <i data-lucide="scan-search" class="h-5 w-5"></i>
                    Baca dan Preview
                </button>

            </form>

        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">
                <h3 class="font-bold text-slate-900">
                    Riwayat Import Kas
                </h3>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="px-6 py-4">Batch</th>
                            <th class="px-6 py-4">File</th>
                            <th class="px-6 py-4">Data Utama</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($batches as $batch)

                            <tr>

                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $batch->code }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $batch->created_at->translatedFormat('d M Y H:i') }}
                                    </p>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $batch->original_name }}
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $batch->dataImportBatch?->code }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                        {{ $batch->status_label }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">

                                    <a
                                        href="{{ route('cash-imports.show', $batch) }}"
                                        class="inline-flex rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-blue-50 hover:text-blue-600">

                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">
                                    Belum ada import kas.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if ($batches->hasPages())
                <div class="border-t border-slate-200 p-6">
                    {{ $batches->links() }}
                </div>
            @endif

        </section>

    </div>

@endsection
