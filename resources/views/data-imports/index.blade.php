@extends('layouts.app')

@section('title', 'Import Data Awal')
@section('page-title', 'Import Data Awal')
@section('page-description', 'Unggah dan periksa data lama koperasi sebelum dimasukkan ke aplikasi')

@section('content')

    <div class="grid gap-7 xl:grid-cols-[420px_1fr]">

        <section class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="file-up" class="h-6 w-6"></i>
                </div>

                <div>
                    <h3 class="font-bold text-slate-900">
                        Upload Rekapan Excel
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Gunakan file REKAPAN ANGSURAN 2026.
                    </p>
                </div>

            </div>

            @if ($errors->any())

                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4">

                    <ul class="list-inside list-disc space-y-1 text-xs text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif

            <form
                action="{{ route('data-imports.store') }}"
                method="POST"
                enctype="multipart/form-data"
                class="mt-7 space-y-5">

                @csrf

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        File Excel
                        <span class="text-red-500">*</span>
                    </label>

                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center hover:border-emerald-400 hover:bg-emerald-50">

                        <div class="rounded-2xl bg-white p-4 text-emerald-600 shadow-sm">
                            <i data-lucide="sheet" class="h-8 w-8"></i>
                        </div>

                        <p class="mt-4 text-sm font-semibold text-slate-700">
                            Pilih file XLSX
                        </p>

                        <p class="mt-2 text-xs text-slate-500">
                            Maksimal 10 MB
                        </p>

                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,.xls"
                            required
                            class="mt-5 block w-full text-xs text-slate-500
                                file:mr-3 file:rounded-xl file:border-0
                                file:bg-emerald-600 file:px-4 file:py-2.5
                                file:text-xs file:font-semibold file:text-white">

                    </label>

                </div>

                <div>

                    <label
                        for="cutoff_date"
                        class="mb-2 block text-sm font-semibold text-slate-700">

                        Tanggal cut-off
                        <span class="text-red-500">*</span>

                    </label>

                    <input
                        type="date"
                        name="cutoff_date"
                        id="cutoff_date"
                        value="{{ old('cutoff_date', '2026-06-30') }}"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Sheet setelah tanggal ini tidak akan dibaca.
                    </p>

                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">

                    <div class="flex gap-3">

                        <i data-lucide="shield-check" class="h-5 w-5 shrink-0 text-amber-600"></i>

                        <p class="text-xs leading-6 text-amber-700">
                            Tahap ini hanya membuat preview. Data anggota dan transaksi belum dimasukkan ke tabel utama.
                        </p>

                    </div>

                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                    <i data-lucide="scan-search" class="h-5 w-5"></i>
                    Baca dan Preview File

                </button>

            </form>

        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">

                <h3 class="font-bold text-slate-900">
                    Riwayat Batch Import
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Daftar file yang sudah pernah diunggah.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4">Batch</th>
                            <th class="px-6 py-4">File</th>
                            <th class="px-6 py-4">Cut-off</th>
                            <th class="px-6 py-4">Data</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($batches as $batch)

                            @php
                                $statusClass = match ($batch->status) {
                                    'previewed' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'processing' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <tr class="hover:bg-slate-50">

                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $batch->code }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $batch->created_at->translatedFormat('d M Y H:i') }}
                                    </p>

                                </td>

                                <td class="px-6 py-4">

                                    <p class="max-w-56 truncate text-sm text-slate-700">
                                        {{ $batch->original_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Oleh {{ $batch->user?->name ?? 'Sistem' }}
                                    </p>

                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                    {{ $batch->cutoff_date?->translatedFormat('d M Y') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">

                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ $batch->member_count }} anggota
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $batch->row_count }} baris
                                    </p>

                                </td>

                                <td class="px-6 py-4">

                                    <span class="inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $batch->status_label }}
                                    </span>

                                </td>

                                <td class="px-6 py-4">

                                    <div class="flex justify-end">

                                        <a
                                            href="{{ route('data-imports.show', $batch) }}"
                                            class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600">

                                            <i data-lucide="eye" class="h-4 w-4"></i>

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="px-6 py-16 text-center">

                                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i data-lucide="folder-open" class="h-9 w-9"></i>
                                    </div>

                                    <p class="mt-5 text-sm font-semibold text-slate-700">
                                        Belum ada file import
                                    </p>

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
