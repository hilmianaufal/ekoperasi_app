@extends('layouts.app')

@section('title', 'Jurnal Umum')
@section('page-title', 'Jurnal Umum')
@section('page-description', 'Pencatatan debit dan kredit transaksi koperasi')

@section('content')

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <div>
            <h2 class="text-xl font-bold text-slate-900">
                Daftar Jurnal
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola jurnal manual dan jurnal otomatis koperasi.
            </p>
        </div>

        <a
            href="{{ route('journal-entries.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

            <i data-lucide="plus" class="h-5 w-5"></i>
            Tambah Jurnal
        </a>

    </div>

    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-700">Jurnal Draft</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ $statistics['draft_count'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-sm text-emerald-700">Sudah Diposting</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ $statistics['posted_count'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-red-200 bg-red-50 p-5">
            <p class="text-sm text-red-700">Jurnal Dibalik</p>
            <p class="mt-2 text-3xl font-bold text-red-700">
                {{ $statistics['reversed_count'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
            <p class="text-sm text-blue-700">Total Debit Diposting</p>
            <p class="mt-2 text-xl font-bold text-blue-700">
                Rp{{ number_format(
                    $statistics['posted_debit'],
                    0,
                    ',',
                    '.'
                ) }}
            </p>
        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            action="{{ route('journal-entries.index') }}"
            method="GET"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">

            <div class="xl:col-span-2">

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Pencarian
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Nomor jurnal, referensi, atau keterangan"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Status
                </label>

                <select
                    name="status"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    <option value="">Semua status</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="posted" @selected($status === 'posted')>Diposting</option>
                    <option value="reversed" @selected($status === 'reversed')>Dibalik</option>

                </select>

            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Dari
                </label>

                <input
                    type="date"
                    name="date_from"
                    value="{{ $dateFrom }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Sampai
                </label>

                <input
                    type="date"
                    name="date_to"
                    value="{{ $dateTo }}"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
            </div>

            <div class="flex gap-3 md:col-span-2 xl:col-span-5">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">

                    <i data-lucide="search" class="h-4 w-4"></i>
                    Terapkan
                </button>

                <a
                    href="{{ route('journal-entries.index') }}"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">

                    Reset
                </a>

            </div>

        </form>

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="px-5 py-4">Nomor Jurnal</th>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Keterangan</th>
                        <th class="px-5 py-4">Sumber</th>
                        <th class="px-5 py-4 text-right">Debit</th>
                        <th class="px-5 py-4 text-right">Kredit</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($entries as $entry)

                        @php
                            $statusClass = match ($entry->status) {
                                'posted' => 'bg-emerald-100 text-emerald-700',
                                'reversed' => 'bg-red-100 text-red-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/70">

                            <td class="px-5 py-4">
                                <p class="text-sm font-bold text-slate-800">
                                    {{ $entry->entry_number }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $entry->reference_number ?: 'Tanpa referensi' }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">
                                {{ $entry->entry_date->translatedFormat('d M Y') }}
                            </td>

                            <td class="px-5 py-4">
                                <p class="max-w-sm text-sm text-slate-700">
                                    {{ $entry->description }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $entry->source_label }}
                            </td>

                            <td class="px-5 py-4 text-right text-sm font-semibold text-blue-700">
                                Rp{{ number_format($entry->total_debit, 0, ',', '.') }}
                            </td>

                            <td class="px-5 py-4 text-right text-sm font-semibold text-violet-700">
                                Rp{{ number_format($entry->total_credit, 0, ',', '.') }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                    {{ $entry->status_label }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">

                                <a
                                    href="{{ route('journal-entries.show', $entry) }}"
                                    class="inline-flex rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:bg-blue-50 hover:text-blue-600">

                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-sm text-slate-500">
                                Belum ada jurnal umum.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($entries->hasPages())
            <div class="border-t border-slate-200 p-6">
                {{ $entries->links() }}
            </div>
        @endif

    </section>

@endsection
