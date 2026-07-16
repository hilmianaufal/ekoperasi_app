@extends('layouts.app')

@section('title', 'Preview Import')
@section('page-title', 'Preview Import Data')
@section('page-description', 'Periksa data Excel sebelum dimasukkan ke aplikasi')

@section('content')

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a href="{{ route('data-imports.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke import data

        </a>

        @if ($importBatch->status !== 'completed')
            @php
                $reviewMappingCount = $importBatch->mappings->where('status', 'review')->count();
            @endphp

            @if ($importBatch->members_savings_imported_at)
                <section class="mt-7 rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

                    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">

                        <div class="flex gap-4">

                            <div class="h-fit rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="circle-check-big" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h3 class="font-bold text-emerald-900">
                                    Anggota dan simpanan berhasil diimpor
                                </h3>

                                <p class="mt-2 text-sm leading-7 text-emerald-700">
                                    {{ number_format($importBatch->imported_member_count, 0, ',', '.') }}
                                    anggota dan
                                    {{ number_format($importBatch->imported_saving_count, 0, ',', '.') }}
                                    transaksi simpanan telah dimasukkan.
                                </p>

                                <p class="mt-1 text-xs text-emerald-600">
                                    Diproses
                                    {{ $importBatch->members_savings_imported_at->translatedFormat('d F Y H:i') }}
                                </p>

                            </div>

                        </div>

                        <a href="{{ route('members.index') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                            <i data-lucide="users" class="h-5 w-5"></i>
                            Lihat Data Anggota

                        </a>

                    </div>

                </section>
            @elseif ($reviewMappingCount > 0)
                <section class="mt-7 rounded-3xl border border-amber-200 bg-amber-50 p-6">

                    <div class="flex gap-4">

                        <div class="h-fit rounded-2xl bg-amber-100 p-3 text-amber-600">
                            <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                        </div>

                        <div>

                            <h3 class="font-bold text-amber-900">
                                Import belum dapat diproses
                            </h3>

                            <p class="mt-2 text-sm leading-7 text-amber-700">
                                Masih terdapat {{ $reviewMappingCount }} nama anggota dengan status
                                <strong>Perlu Diperiksa</strong>. Perbaiki nama dan ubah statusnya terlebih dahulu.
                            </p>

                        </div>

                    </div>

                </section>
            @else
                <section class="mt-7 rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm">

                    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">

                        <div class="flex gap-4">

                            <div class="h-fit rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="database-zap" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h3 class="font-bold text-slate-900">
                                    Proses Import Anggota & Simpanan
                                </h3>

                                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">
                                    Sistem akan membuat anggota baru, saldo awal simpanan wajib dan sukarela,
                                    setoran bulanan, penarikan sukarela, serta penyesuaian saldo dari Excel.
                                </p>

                                <p class="mt-2 text-xs font-semibold text-amber-600">
                                    Pembiayaan, angsuran, bagi hasil, dan administrasi belum diproses pada tahap ini.
                                </p>

                            </div>

                        </div>

                        <form
                            @if (!$importBatch->members_savings_imported_at) action="{{ route('data-imports.process-members-savings', $importBatch) }}"
                            method="POST" id="process-members-savings-form">

                            @csrf

                            <button type="button" onclick="confirmProcessMembersSavings()"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700 lg:w-auto">

                                <i data-lucide="database-zap" class="h-5 w-5"></i>
                                Proses Import Data

                            </button>

                        </form> @endif
                            </div>

                </section>
            @endif
            @if ($importBatch->financing_imported_at)

                <section class="mt-7 rounded-3xl border border-blue-200 bg-blue-50 p-6 shadow-sm">

                    <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">

                        <div class="flex gap-4">

                            <div class="h-fit rounded-2xl bg-blue-100 p-3 text-blue-600">
                                <i data-lucide="badge-check" class="h-6 w-6"></i>
                            </div>

                            <div>

                                <h3 class="font-bold text-blue-900">
                                    Pembiayaan dan angsuran berhasil diimpor
                                </h3>

                                <p class="mt-2 text-sm leading-7 text-blue-700">
                                    {{ number_format($importBatch->imported_loan_count ?? 0, 0, ',', '.') }}
                                    pembiayaan,

                                    {{ number_format($importBatch->imported_installment_count ?? 0, 0, ',', '.') }}
                                    angsuran, dan

                                    {{ number_format($importBatch->imported_payment_count ?? 0, 0, ',', '.') }}
                                    pembayaran telah dimasukkan.
                                </p>

                                <p class="mt-1 text-xs text-blue-600">
                                    Diproses
                                    {{ $importBatch->financing_imported_at?->translatedFormat('d F Y H:i') }}
                                </p>

                            </div>

                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">

                            @if (\Illuminate\Support\Facades\Route::has('data-imports.reconciliation'))
                                <a href="{{ route('data-imports.reconciliation', $importBatch) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-600 hover:bg-blue-100">

                                    <i data-lucide="scale" class="h-5 w-5"></i>
                                    Rekonsiliasi
                                </a>
                            @endif

                            <a href="{{ route('loans.index') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">

                                <i data-lucide="landmark" class="h-5 w-5"></i>
                                Lihat Pembiayaan
                            </a>

                        </div>

                    </div>

                </section>

            @endif
            <form action="{{ route('data-imports.destroy', $importBatch) }}" method="POST" id="delete-import-form">

                @csrf
                @method('DELETE')

                <button type="button" onclick="confirmDeleteImport()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-600 hover:bg-red-100">

                    <i data-lucide="trash-2" class="h-5 w-5"></i>
                    Hapus Batch

                </button>

            </form>

        @endif

    </div>

    @if ($importBatch->status === 'failed')

        <section class="rounded-3xl border border-red-200 bg-red-50 p-6">

            <h3 class="font-bold text-red-800">
                Proses pembacaan file gagal
            </h3>

            <p class="mt-3 text-sm leading-7 text-red-700">
                {{ $importBatch->error_message }}
            </p>

        </section>
    @else
        <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-800 p-7 text-white">

            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-100">
                        {{ $importBatch->code }}
                    </p>

                    <h1 class="mt-2 text-2xl font-bold">
                        {{ $importBatch->original_name }}
                    </h1>

                    <p class="mt-2 text-sm text-emerald-100">
                        Cut-off:
                        {{ $importBatch->cutoff_date->translatedFormat('d F Y') }}
                    </p>

                </div>

                <span class="w-fit rounded-full bg-white/15 px-4 py-2 text-xs font-semibold">
                    {{ $importBatch->status_label }}
                </span>

            </div>

        </section>

        <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Sheet Dibaca
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $importBatch->sheet_count }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Anggota Terdeteksi
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $importBatch->member_count }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Total Baris
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $importBatch->row_count }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Perlu Diperiksa
                </p>

                <p class="mt-2 text-3xl font-bold text-amber-600">
                    {{ $importBatch->mappings->where('status', 'review')->count() }}
                </p>

            </article>

        </section>

        @if (!empty($importBatch->warnings))

            <section class="mt-7 rounded-3xl border border-amber-200 bg-amber-50 p-6">

                <div class="flex items-center gap-3">

                    <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                        <i data-lucide="triangle-alert" class="h-6 w-6"></i>
                    </div>

                    <div>
                        <h3 class="font-bold text-amber-900">
                            Peringatan Data
                        </h3>

                        <p class="mt-1 text-xs text-amber-700">
                            Periksa data berikut sebelum melanjutkan.
                        </p>
                    </div>

                </div>

                <div class="mt-5 space-y-3">

                    @foreach ($importBatch->warnings as $warning)
                        <div class="rounded-2xl bg-white/70 p-4 text-sm text-amber-800">
                            {{ $warning['message'] ?? 'Peringatan data.' }}
                        </div>
                    @endforeach

                </div>

            </section>

        @endif

        <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">

                <h3 class="font-bold text-slate-900">
                    Ringkasan Per Bulan
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Total nilai yang terbaca dari setiap sheet Excel.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-4">Periode</th>
                            <th class="px-5 py-4 text-right">Anggota</th>
                            <th class="px-5 py-4 text-right">Simpanan Pokok</th>
                            <th class="px-5 py-4 text-right">Simpanan Wajib</th>
                            <th class="px-5 py-4 text-right">Angsuran</th>
                            <th class="px-5 py-4 text-right">Bagi Hasil</th>
                            <th class="px-5 py-4 text-right">Sukarela</th>
                            <th class="px-5 py-4 text-right">Pembiayaan</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach ($sheetSummaries as $summary)
                            <tr>

                                <td class="whitespace-nowrap px-5 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $summary->sheet_name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ \Carbon\Carbon::parse($summary->period_date)->translatedFormat('F Y') }}
                                    </p>

                                </td>

                                <td class="px-5 py-4 text-right text-sm text-slate-700">
                                    {{ number_format($summary->member_rows, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format($summary->principal_saving, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format($summary->mandatory_saving, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format($summary->principal_installment, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format($summary->profit_share, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-700">
                                    Rp{{ number_format($summary->voluntary_saving, 0, ',', '.') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-blue-600">
                                    Rp{{ number_format($summary->new_financing, 0, ',', '.') }}
                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>

        <section class="mt-7 grid gap-5 sm:grid-cols-3">

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Saldo Wajib Cut-off
                </p>

                <p class="mt-2 text-xl font-bold text-emerald-600">
                    Rp{{ number_format($latestBalances?->mandatory_balance ?? 0, 0, ',', '.') }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Saldo Sukarela Cut-off
                </p>

                <p class="mt-2 text-xl font-bold text-blue-600">
                    Rp{{ number_format($latestBalances?->voluntary_balance ?? 0, 0, ',', '.') }}
                </p>

            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-sm text-slate-500">
                    Sisa Pembiayaan Cut-off
                </p>

                <p class="mt-2 text-xl font-bold text-amber-600">
                    Rp{{ number_format($latestBalances?->remaining_financing ?? 0, 0, ',', '.') }}
                </p>

            </article>

        </section>

        <form action="{{ route('data-imports.mappings.update', $importBatch) }}" method="POST" class="mt-7">

            @csrf
            @method('PUT')

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

                    <div>

                        <h3 class="font-bold text-slate-900">
                            Mapping Nama Anggota
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Perbaiki nama yang salah atau berbeda antarbulan.
                        </p>

                    </div>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                        <i data-lucide="save" class="h-5 w-5"></i>
                        Simpan Mapping

                    </button>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-4">No.</th>
                                <th class="px-5 py-4">Nama Terdeteksi</th>
                                <th class="px-5 py-4">Nama Resmi</th>
                                <th class="px-5 py-4">Anggota Aplikasi</th>
                                <th class="px-5 py-4">Status</th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach ($importBatch->mappings as $mapping)
                                <tr class="{{ $mapping->status === 'review' ? 'bg-amber-50/60' : '' }}">

                                    <td class="px-5 py-4 text-sm font-bold text-slate-700">
                                        {{ $mapping->source_number }}
                                    </td>

                                    <td class="px-5 py-4">

                                        <div class="flex max-w-sm flex-wrap gap-2">

                                            @foreach ($mapping->detected_names as $detectedName)
                                                <span
                                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                    {{ $detectedName }}
                                                </span>
                                            @endforeach

                                        </div>

                                    </td>

                                    <td class="px-5 py-4">

                                        <input type="text" name="mappings[{{ $mapping->id }}][canonical_name]"
                                            value="{{ old("mappings.{$mapping->id}.canonical_name", $mapping->canonical_name) }}"
                                            required
                                            class="min-w-56 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-500">

                                    </td>

                                    <td class="px-5 py-4">

                                        @if ($mapping->member)
                                            <p class="text-sm font-semibold text-emerald-700">
                                                {{ $mapping->member->name }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ $mapping->member->member_number }}
                                            </p>
                                        @else
                                            <span class="text-xs text-slate-400">
                                                Belum ada
                                            </span>
                                        @endif

                                    </td>

                                    <td class="px-5 py-4">

                                        <select name="mappings[{{ $mapping->id }}][status]"
                                            class="min-w-44 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-emerald-500">

                                            <option value="new" @selected($mapping->status === 'new')>
                                                Anggota Baru
                                            </option>

                                            <option value="matched" @selected($mapping->status === 'matched')>
                                                Sudah Cocok
                                            </option>

                                            <option value="review" @selected($mapping->status === 'review')>
                                                Perlu Diperiksa
                                            </option>

                                            <option value="ignored" @selected($mapping->status === 'ignored')>
                                                Abaikan
                                            </option>

                                        </select>

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </section>

        </form>
        @if ($importBatch->members_savings_imported_at && !$importBatch->financing_imported_at)
            <section class="mt-7 rounded-3xl border border-blue-200 bg-white p-6 shadow-sm">

                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">

                    <div class="flex gap-4">

                        <div class="h-fit rounded-2xl bg-blue-100 p-3 text-blue-600">
                            <i data-lucide="landmark" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Proses Pembiayaan & Angsuran Lama
                            </h3>

                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500">
                                Sistem akan membuat pembiayaan lama, riwayat angsuran
                                pokok, bagi hasil, administrasi, serta saldo pembiayaan
                                akhir berdasarkan rekapan Excel.
                            </p>
                        </div>

                    </div>

                    <form action="{{ route('data-imports.process-financing', $importBatch) }}" method="POST"
                        id="process-financing-form">

                        @csrf

                        <button type="button" onclick="confirmProcessFinancing()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 hover:bg-blue-700 lg:w-auto">

                            <i data-lucide="database-zap" class="h-5 w-5"></i>
                            Proses Pembiayaan

                        </button>

                    </form>

                </div>

            </section>
        @endif

        @if ($importBatch->financing_imported_at)
            <section class="mt-7 rounded-3xl border border-blue-200 bg-blue-50 p-6">

                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">

                    <div class="flex gap-4">

                        <div class="h-fit rounded-2xl bg-blue-100 p-3 text-blue-600">
                            <i data-lucide="badge-check" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-blue-900">
                                Pembiayaan dan angsuran berhasil diimpor
                            </h3>

                            <p class="mt-2 text-sm text-blue-700">
                                {{ number_format($importBatch->imported_loan_count, 0, ',', '.') }}
                                pembiayaan dan
                                {{ number_format($importBatch->imported_installment_count, 0, ',', '.') }}
                                angsuran berhasil dibuat.
                            </p>
                        </div>

                    </div>

                    <a href="{{ route('loans.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">

                        <i data-lucide="landmark" class="h-5 w-5"></i>
                        Lihat Pembiayaan

                    </a>

                </div>

            </section>
        @endif
        <section class="mt-7 rounded-3xl border border-blue-200 bg-blue-50 p-6">

            <div class="flex gap-4">

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="info" class="h-6 w-6"></i>
                </div>

                <div>

                    <h3 class="font-bold text-blue-900">
                        Data belum dimasukkan ke aplikasi
                    </h3>

                    <p class="mt-2 text-sm leading-7 text-blue-700">
                        Preview ini hanya menyimpan salinan data Excel. Tahap berikutnya akan membuat anggota, saldo awal
                        simpanan, saldo pembiayaan, serta catatan audit import.
                    </p>

                </div>

            </div>

        </section>

    @endif

@endsection

@push('scripts')
    <script>
        function confirmDeleteImport() {
            Swal.fire({
                icon: 'warning',
                title: 'Hapus batch import?',
                text: 'File dan seluruh data preview batch ini akan dihapus.',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(
                        'delete-import-form'
                    ).submit();
                }
            });
        }


        function confirmProcessMembersSavings() {
            Swal.fire({
                icon: 'warning',
                title: 'Proses import data?',
                html: `
            <div class="text-left text-sm leading-7">
                <p>Data berikut akan dimasukkan ke aplikasi:</p>
                <ul class="mt-2 list-inside list-disc">
                    <li>Data anggota baru</li>
                    <li>Saldo awal simpanan wajib</li>
                    <li>Saldo awal simpanan sukarela</li>
                    <li>Setoran Januari sampai cut-off</li>
                    <li>Penarikan simpanan sukarela</li>
                </ul>
                <p class="mt-3 font-semibold text-amber-600">
                    Pastikan seluruh mapping nama sudah benar.
                </p>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses import',
                cancelButtonText: 'Periksa kembali',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses data',
                        text: 'Mohon tunggu dan jangan menutup halaman.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    document
                        .getElementById(
                            'process-members-savings-form'
                        )
                        .submit();
                }
            });
        }

        function confirmProcessFinancing() {
            Swal.fire({
                icon: 'warning',
                title: 'Proses pembiayaan lama?',
                html: `
            <div class="text-left text-sm leading-7">
                <p>Sistem akan membuat:</p>
                <ul class="mt-2 list-inside list-disc">
                    <li>Pembiayaan lama setiap anggota</li>
                    <li>Riwayat angsuran pokok</li>
                    <li>Pembayaran bagi hasil</li>
                    <li>Catatan biaya administrasi</li>
                    <li>Saldo pembiayaan per cut-off</li>
                </ul>
                <p class="mt-3 font-semibold text-amber-600">
                    Tenor dan tanggal akad lama akan ditandai sebagai data migrasi.
                </p>
            </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses pembiayaan',
                        text: 'Mohon tunggu dan jangan menutup halaman.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });

                    document
                        .getElementById('process-financing-form')
                        .submit();
                }
            });
        }
    </script>
@endpush
