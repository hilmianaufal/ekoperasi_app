@extends('layouts.app')

@section('title', 'Detail Jurnal')
@section('page-title', 'Detail Jurnal Umum')
@section('page-description', 'Pemeriksaan dan posting jurnal')

@section('content')

    @php
        $statusClass = match ($journalEntry->status) {
            'posted' => 'bg-emerald-100 text-emerald-700',
            'reversed' => 'bg-red-100 text-red-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a
            href="{{ route('journal-entries.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke jurnal umum
        </a>

        <span class="w-fit rounded-full px-4 py-2 text-xs font-semibold {{ $statusClass }}">
            {{ $journalEntry->status_label }}
        </span>

    </div>

    <section class="mt-6 rounded-3xl bg-gradient-to-br from-slate-800 to-slate-950 p-7 text-white">

        <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">
            {{ $journalEntry->entry_number }}
        </p>

        <h1 class="mt-3 text-2xl font-bold">
            {{ $journalEntry->description }}
        </h1>

        <div class="mt-5 flex flex-wrap gap-5 text-sm text-slate-300">

            <span>
                Tanggal:
                <strong class="text-white">
                    {{ $journalEntry->entry_date->translatedFormat('d F Y') }}
                </strong>
            </span>

            <span>
                Sumber:
                <strong class="text-white">
                    {{ $journalEntry->source_label }}
                </strong>
            </span>

            <span>
                Referensi:
                <strong class="text-white">
                    {{ $journalEntry->reference_number ?: '-' }}
                </strong>
            </span>

        </div>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-3">

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5">

            <p class="text-sm text-blue-700">
                Total Debit
            </p>

            <p class="mt-2 text-xl font-bold text-blue-700">
                Rp{{ number_format(
                    $journalEntry->total_debit,
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border border-violet-200 bg-violet-50 p-5">

            <p class="text-sm text-violet-700">
                Total Kredit
            </p>

            <p class="mt-2 text-xl font-bold text-violet-700">
                Rp{{ number_format(
                    $journalEntry->total_credit,
                    0,
                    ',',
                    '.'
                ) }}
            </p>

        </article>

        <article class="rounded-3xl border {{ $journalEntry->is_balanced ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }} p-5">

            <p class="text-sm {{ $journalEntry->is_balanced ? 'text-emerald-700' : 'text-red-700' }}">
                Status Keseimbangan
            </p>

            <p class="mt-2 text-xl font-bold {{ $journalEntry->is_balanced ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $journalEntry->is_balanced ? 'Seimbang' : 'Tidak Seimbang' }}
            </p>

        </article>

    </section>

    <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 p-6">
            <h3 class="font-bold text-slate-900">
                Detail Debit dan Kredit
            </h3>
        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="px-6 py-4">Kode Akun</th>
                        <th class="px-6 py-4">Nama Akun</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-right">Debit</th>
                        <th class="px-6 py-4 text-right">Kredit</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @foreach ($journalEntry->lines as $line)

                        <tr>

                            <td class="px-6 py-4 font-mono text-sm font-bold text-slate-700">
                                {{ $line->account->code }}
                            </td>

                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                {{ $line->account->name }}
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $line->description ?: '-' }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-semibold text-blue-700">
                                {{ (float) $line->debit > 0
                                    ? 'Rp' . number_format($line->debit, 0, ',', '.')
                                    : '-' }}
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-semibold text-violet-700">
                                {{ (float) $line->credit > 0
                                    ? 'Rp' . number_format($line->credit, 0, ',', '.')
                                    : '-' }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

                <tfoot class="bg-slate-50 font-bold">

                    <tr>

                        <td colspan="3" class="px-6 py-4 text-right">
                            TOTAL
                        </td>

                        <td class="px-6 py-4 text-right text-blue-700">
                            Rp{{ number_format($journalEntry->total_debit, 0, ',', '.') }}
                        </td>

                        <td class="px-6 py-4 text-right text-violet-700">
                            Rp{{ number_format($journalEntry->total_credit, 0, ',', '.') }}
                        </td>

                    </tr>

                </tfoot>

            </table>

        </div>

    </section>

    @if ($journalEntry->notes)

        <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-6">
            <p class="text-xs font-semibold uppercase text-slate-400">
                Catatan
            </p>

            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">
                {{ $journalEntry->notes }}
            </p>
        </section>

    @endif

    @if ($journalEntry->status === 'draft')

        <section class="mt-7 flex flex-col justify-between gap-4 rounded-3xl border border-amber-200 bg-amber-50 p-6 sm:flex-row sm:items-center">

            <div>
                <h3 class="font-bold text-amber-900">
                    Jurnal Belum Diposting
                </h3>

                <p class="mt-1 text-sm text-amber-700">
                    Setelah diposting, jurnal akan masuk ke buku besar.
                </p>
            </div>

            <div class="flex gap-3">

                <form
                    action="{{ route('journal-entries.destroy', $journalEntry) }}"
                    method="POST"
                    onsubmit="return confirm('Hapus jurnal draft ini?')">

                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="rounded-2xl border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-600">

                        Hapus Draft
                    </button>

                </form>

                <form
                    action="{{ route('journal-entries.post', $journalEntry) }}"
                    method="POST"
                    onsubmit="return confirm('Posting jurnal ini ke buku besar?')">

                    @csrf

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white">

                        <i data-lucide="badge-check" class="h-5 w-5"></i>
                        Posting Jurnal
                    </button>

                </form>

            </div>

        </section>

    @elseif ($journalEntry->status === 'posted')

        <section
            x-data="{ reverseOpen: false }"
            class="mt-7 rounded-3xl border border-red-200 bg-red-50 p-6">

            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                <div>
                    <h3 class="font-bold text-red-900">
                        Pembalikan Jurnal
                    </h3>

                    <p class="mt-1 text-sm text-red-700">
                        Jurnal yang sudah diposting tidak boleh dihapus.
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="reverseOpen = true"
                    class="rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white">

                    Buat Jurnal Pembalik
                </button>

            </div>

            <div
                x-show="reverseOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">

                <div
                    x-on:click.outside="reverseOpen = false"
                    class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">

                    <h3 class="text-lg font-bold text-slate-900">
                        Konfirmasi Pembalikan
                    </h3>

                    <form
                        action="{{ route('journal-entries.reverse', $journalEntry) }}"
                        method="POST"
                        class="mt-5 space-y-4">

                        @csrf

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Tanggal Pembalikan
                            </label>

                            <input
                                type="date"
                                name="entry_date"
                                value="{{ now()->toDateString() }}"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Alasan
                            </label>

                            <textarea
                                name="reason"
                                rows="4"
                                required
                                class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                                placeholder="Jelaskan alasan pembalikan jurnal"></textarea>
                        </div>

                        <div class="flex gap-3">

                            <button
                                type="button"
                                x-on:click="reverseOpen = false"
                                class="flex-1 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">

                                Batal
                            </button>

                            <button
                                type="submit"
                                class="flex-1 rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white">

                                Proses Pembalikan
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </section>

    @endif

    @if ($reversalEntry)

        <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-6">

            <p class="text-sm text-slate-500">
                Jurnal ini telah dibalik oleh:
            </p>

            <a
                href="{{ route('journal-entries.show', $reversalEntry) }}"
                class="mt-2 inline-flex items-center gap-2 font-bold text-red-600">

                {{ $reversalEntry->entry_number }}
                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            </a>

        </section>

    @endif

@endsection
