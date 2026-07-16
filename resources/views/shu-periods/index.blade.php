@extends('layouts.app')

@section('title', 'Sisa Hasil Usaha')
@section('page-title', 'Sisa Hasil Usaha')
@section('page-description', 'Kelola periode, perhitungan, dan pembagian SHU anggota')

@section('content')

    <section class="grid gap-5 sm:grid-cols-3">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">
                Total Periode
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ number_format(
                    $statistics['period_count'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>
        </article>

        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm text-amber-700">
                Perlu Diproses
            </p>

            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ number_format(
                    $statistics['draft_count'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>
        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm text-emerald-700">
                SHU Sudah Dibagikan
            </p>

            <p class="mt-2 text-2xl font-bold text-emerald-700">
                Rp{{ number_format(
                    $statistics['distributed_total'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>
        </article>

    </section>

    <div class="mt-7 grid gap-7 xl:grid-cols-[420px_1fr]">

        <section class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-center gap-3">

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="badge-percent" class="h-6 w-6"></i>
                </div>

                <div>
                    <h3 class="font-bold text-slate-900">
                        Buat Periode SHU
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Masukkan ketetapan SHU berdasarkan laporan/RAT.
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
                action="{{ route('shu-periods.store') }}"
                method="POST"
                class="mt-6 space-y-4">

                @csrf

                <div class="grid gap-4 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tahun
                        </label>

                        <input
                            type="number"
                            name="year"
                            value="{{ old('year', 2025) }}"
                            min="2000"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal Perhitungan
                        </label>

                        <input
                            type="date"
                            name="calculation_date"
                            value="{{ old('calculation_date', '2025-12-31') }}"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                </div>

                <div class="grid gap-4 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            JASUS
                        </label>

                        <div class="relative">

                            <input
                                type="number"
                                name="business_service_rate"
                                value="{{ old('business_service_rate', 18) }}"
                                step="0.0001"
                                min="0"
                                max="100"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-10 text-sm">

                            <span class="absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">
                                %
                            </span>

                        </div>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            JASIM
                        </label>

                        <div class="relative">

                            <input
                                type="number"
                                name="saving_service_rate"
                                value="{{ old('saving_service_rate', 6) }}"
                                step="0.0001"
                                min="0"
                                max="100"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-10 text-sm">

                            <span class="absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">
                                %
                            </span>

                        </div>

                    </div>

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Total SHU Tahun Berjalan
                    </label>

                    <input
                        type="number"
                        name="declared_total_shu"
                        value="{{ old('declared_total_shu', 14200000) }}"
                        min="0"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Bagian SHU Anggota
                    </label>

                    <input
                        type="number"
                        name="declared_member_shu"
                        value="{{ old('declared_member_shu', 6390000) }}"
                        min="0"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                </div>

                <div class="grid gap-4 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Total JASUS
                        </label>

                        <input
                            type="number"
                            name="declared_business_service"
                            value="{{ old('declared_business_service', 3550000) }}"
                            min="0"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Total JASIM
                        </label>

                        <input
                            type="number"
                            name="declared_saving_service"
                            value="{{ old('declared_saving_service', 2840000) }}"
                            min="0"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                </div>

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Catatan
                    </label>

                    <textarea
                        name="notes"
                        rows="3"
                        class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        placeholder="Keterangan keputusan RAT atau sumber data">{{ old('notes') }}</textarea>

                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-semibold text-white hover:bg-emerald-700">

                    <i data-lucide="plus-circle" class="h-5 w-5"></i>
                    Buat Periode SHU
                </button>

            </form>

        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">

                <h3 class="font-bold text-slate-900">
                    Daftar Periode SHU
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Riwayat perhitungan dan pembagian SHU.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="px-6 py-4">Periode</th>
                            <th class="px-6 py-4">Persentase</th>
                            <th class="px-6 py-4 text-right">SHU Anggota</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($periods as $period)

                            @php
                                $statusClass = match ($period->status) {
                                    'draft' => 'bg-slate-100 text-slate-600',
                                    'review' => 'bg-amber-100 text-amber-700',
                                    'approved' => 'bg-blue-100 text-blue-700',
                                    'distributed' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <tr>

                                <td class="px-6 py-4">

                                    <p class="text-sm font-bold text-slate-800">
                                        SHU {{ $period->year }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $period->code }}
                                    </p>

                                </td>

                                <td class="px-6 py-4 text-sm text-slate-700">
                                    JASUS {{ number_format($period->business_service_rate, 2, ',', '.') }}%
                                    <br>
                                    JASIM {{ number_format($period->saving_service_rate, 2, ',', '.') }}%
                                </td>

                                <td class="px-6 py-4 text-right">

                                    <p class="text-sm font-bold text-emerald-700">
                                        Rp{{ number_format(
                                            $period->declared_member_shu,
                                            0,
                                            ',',
                                            '.'
                                        ) }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $period->allocations_count }} anggota
                                    </p>

                                </td>

                                <td class="px-6 py-4 text-center">

                                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $period->status_label }}
                                    </span>

                                </td>

                                <td class="px-6 py-4">

                                    <div class="flex justify-end gap-2">

                                        <a
                                            href="{{ route('shu-periods.show', $period) }}"
                                            class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-blue-50 hover:text-blue-600">

                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </a>

                                        @if (
                                            $period->status === 'draft'
                                            && $period->allocations_count === 0
                                            && $period->import_batches_count === 0
                                        )

                                            <form
                                                action="{{ route('shu-periods.destroy', $period) }}"
                                                method="POST">

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-red-50 hover:text-red-600">

                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>

                                            </form>

                                        @endif

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">
                                    Belum ada periode SHU.
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

    </div>

@endsection
