@extends('layouts.app')

@section('title', 'Preview Import SHU')
@section('page-title', 'Preview Import SHU')
@section('page-description', 'Periksa mapping anggota dan hasil perhitungan SHU')

@section('content')

    <a
        href="{{ route(
            'shu-periods.show',
            $shuImportBatch->period
        ) }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke periode SHU
    </a>

    <section class="mt-6 rounded-3xl bg-gradient-to-br from-emerald-700 to-slate-900 p-7 text-white">

        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">
            {{ $shuImportBatch->code }}
        </p>

        <h1 class="mt-2 text-2xl font-bold">
            {{ $shuImportBatch->original_name }}
        </h1>

        <p class="mt-2 text-sm text-emerald-100">
            Periode SHU {{ $shuImportBatch->period->year }}
            · JASUS
            {{ number_format(
                $shuImportBatch->period->business_service_rate,
                2,
                ',',
                '.'
            ) }}%
            · JASIM
            {{ number_format(
                $shuImportBatch->period->saving_service_rate,
                2,
                ',',
                '.'
            ) }}%
        </p>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-slate-500">
                Baris Dibaca
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ $shuImportBatch->row_count }}
            </p>

        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

            <p class="text-sm text-emerald-700">
                Sudah Cocok
            </p>

            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ $shuImportBatch->matched_count }}
            </p>

        </article>

        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">

            <p class="text-sm text-amber-700">
                Perlu Diperiksa
            </p>

            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ $shuImportBatch->review_count }}
            </p>

        </article>

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">

            <p class="text-sm text-blue-700">
                Total SHU File
            </p>

            <p class="mt-2 text-xl font-bold text-blue-700">
                Rp{{ number_format(
                    $summary['source_total_shu'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

    </section>

    <section class="mt-7 grid gap-5 lg:grid-cols-3">

        <article class="rounded-3xl border border-slate-200 bg-white p-5">

            <p class="text-sm text-slate-500">
                Total JASUS File
            </p>

            <p class="mt-2 text-xl font-bold text-slate-900">
                Rp{{ number_format(
                    $summary['source_business_service'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5">

            <p class="text-sm text-slate-500">
                Total JASIM File
            </p>

            <p class="mt-2 text-xl font-bold text-slate-900">
                Rp{{ number_format(
                    $summary['source_saving_service'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border {{ abs($summary['difference']) >= 0.01 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5">

            <p class="text-sm {{ abs($summary['difference']) >= 0.01 ? 'text-amber-700' : 'text-emerald-700' }}">
                Selisih Hitung Ulang
            </p>

            <p class="mt-2 text-xl font-bold {{ abs($summary['difference']) >= 0.01 ? 'text-amber-700' : 'text-emerald-700' }}">
                Rp{{ number_format(
                    $summary['difference'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

    </section>

    @if (!empty($shuImportBatch->warnings))

        <section class="mt-7 rounded-3xl border border-amber-200 bg-amber-50 p-6">

            <h3 class="font-bold text-amber-900">
                Peringatan Import
            </h3>

            <div class="mt-4 space-y-3">

                @foreach ($shuImportBatch->warnings as $warning)

                    <p class="rounded-2xl bg-white/70 p-4 text-sm text-amber-800">
                        {{ $warning['message'] ?? 'Data perlu diperiksa.' }}
                    </p>

                @endforeach

            </div>

        </section>

    @endif

    @if (!$shuImportBatch->processed_at)

        <form
            action="{{ route(
                'shu-imports.rows.update',
                $shuImportBatch
            ) }}"
            method="POST"
            class="mt-7">

            @csrf
            @method('PUT')

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

                    <div>

                        <h3 class="font-bold text-slate-900">
                            Mapping dan Rekonsiliasi Anggota
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Nilai file tetap dipakai sebagai alokasi. Hasil hitung ulang hanya menjadi pemeriksaan.
                        </p>

                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                        <i data-lucide="save" class="h-5 w-5"></i>
                        Simpan Pemeriksaan
                    </button>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                                <th class="px-4 py-4">No.</th>
                                <th class="px-4 py-4">Nama File</th>
                                <th class="px-4 py-4">Anggota Aplikasi</th>
                                <th class="px-4 py-4 text-right">Bagi Hasil</th>
                                <th class="px-4 py-4 text-right">Simpanan</th>
                                <th class="px-4 py-4 text-right">JASUS</th>
                                <th class="px-4 py-4 text-right">JASIM</th>
                                <th class="px-4 py-4 text-right">SHU File</th>
                                <th class="px-4 py-4 text-right">Hitung Ulang</th>
                                <th class="px-4 py-4 text-right">Selisih</th>
                                <th class="px-4 py-4">Status</th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach ($shuImportBatch->rows as $row)

                                <tr class="{{ $row->status === 'review' ? 'bg-amber-50/60' : '' }}">

                                    <td class="px-4 py-4 text-sm font-bold text-slate-700">
                                        {{ $row->source_number }}
                                    </td>

                                    <td class="px-4 py-4">

                                        <p class="min-w-36 text-sm font-semibold text-slate-800">
                                            {{ $row->source_name }}
                                        </p>

                                        @if ($row->notes)

                                            <p class="mt-1 max-w-64 text-xs leading-5 text-amber-600">
                                                {{ $row->notes }}
                                            </p>

                                        @endif

                                    </td>

                                    <td class="px-4 py-4">

                                        <select
                                            name="rows[{{ $row->id }}][member_id]"
                                            class="min-w-60 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">

                                            <option value="">
                                                Pilih anggota
                                            </option>

                                            @foreach ($members as $member)

                                                <option
                                                    value="{{ $member->id }}"
                                                    @selected(
                                                        $row->member_id
                                                        === $member->id
                                                    )>

                                                    {{ $member->member_number }}
                                                    — {{ $member->name }}
                                                </option>

                                            @endforeach

                                        </select>

                                    </td>

                                    <td class="px-4 py-4 text-right text-sm">
                                        Rp{{ number_format(
                                            $row->profit_share_base,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm">
                                        Rp{{ number_format(
                                            $row->saving_balance,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm">
                                        Rp{{ number_format(
                                            $row->source_business_service,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm">
                                        Rp{{ number_format(
                                            $row->source_saving_service,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm font-bold text-blue-700">
                                        Rp{{ number_format(
                                            $row->source_total_shu,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm">
                                        Rp{{ number_format(
                                            $row->calculated_total_shu,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4 text-right text-sm font-semibold {{ abs((float) $row->difference) >= 0.01 ? 'text-amber-600' : 'text-slate-400' }}">
                                        Rp{{ number_format(
                                            $row->difference,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </td>

                                    <td class="px-4 py-4">

                                        <select
                                            name="rows[{{ $row->id }}][status]"
                                            class="min-w-40 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">

                                            <option
                                                value="matched"
                                                @selected(
                                                    $row->status
                                                    === 'matched'
                                                )>

                                                Sudah Cocok
                                            </option>

                                            <option
                                                value="review"
                                                @selected(
                                                    in_array(
                                                        $row->status,
                                                        ['new', 'review'],
                                                        true
                                                    )
                                                )>

                                                Perlu Diperiksa
                                            </option>

                                            <option
                                                value="ignored"
                                                @selected(
                                                    $row->status
                                                    === 'ignored'
                                                )>

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

        @if ($shuImportBatch->review_count === 0)

            <section class="mt-7 rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">

                    <div>

                        <h3 class="font-bold text-emerald-900">
                            Data siap dialokasikan
                        </h3>

                        <p class="mt-2 text-sm leading-7 text-emerald-700">
                            Sistem akan membuat pembagian SHU anggota berdasarkan nilai JASUS, JASIM, dan jumlah SHU yang tertulis pada file client.
                        </p>

                    </div>

                    <form
                        action="{{ route(
                            'shu-imports.process',
                            $shuImportBatch
                        ) }}"
                        method="POST"
                        id="process-shu-form">

                        @csrf

                        <button
                            type="button"
                            onclick="confirmProcessShu()"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white hover:bg-emerald-700">

                            <i data-lucide="database-zap" class="h-5 w-5"></i>
                            Proses Alokasi SHU
                        </button>

                    </form>

                </div>

            </section>

        @else

            <section class="mt-7 rounded-3xl border border-amber-200 bg-amber-50 p-6">

                <h3 class="font-bold text-amber-900">
                    Alokasi belum dapat diproses
                </h3>

                <p class="mt-2 text-sm leading-7 text-amber-700">
                    Masih terdapat
                    {{ $shuImportBatch->review_count }}
                    baris berstatus Perlu Diperiksa. Pilih anggota yang benar dan ubah status menjadi Sudah Cocok.
                </p>

            </section>

        @endif

    @else

        <section class="mt-7 rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

            <h3 class="font-bold text-emerald-900">
                Alokasi SHU berhasil diproses
            </h3>

            <p class="mt-2 text-sm text-emerald-700">
                {{ $shuImportBatch->imported_count }}
                anggota telah mendapatkan alokasi SHU.
            </p>

        </section>

    @endif

@endsection

@push('scripts')

    <script>
        function confirmProcessShu() {
            Swal.fire({
                icon: 'warning',
                title: 'Proses alokasi SHU?',
                html: `
                    <div class="text-left text-sm leading-7">
                        <p>Sistem akan membuat alokasi SHU per anggota berdasarkan file client.</p>
                        <p class="mt-3 font-semibold text-amber-600">
                            Pastikan seluruh mapping anggota sudah benar.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses alokasi',
                cancelButtonText: 'Periksa kembali',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document
                        .getElementById(
                            'process-shu-form'
                        )
                        .submit();
                }
            });
        }
    </script>

@endpush
