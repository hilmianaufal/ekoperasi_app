@extends('layouts.app')

@section('title', 'Tambah Jurnal')
@section('page-title', 'Tambah Jurnal Umum')
@section('page-description', 'Pencatatan jurnal debit dan kredit manual')

@section('content')

    <a
        href="{{ route('journal-entries.index') }}"
        class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

        <i data-lucide="arrow-left" class="h-5 w-5"></i>
        Kembali ke jurnal umum
    </a>

    <form
        action="{{ route('journal-entries.store') }}"
        method="POST"
        class="mt-6 space-y-7">

        @csrf

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="font-bold text-slate-900">
                Informasi Jurnal
            </h3>

            <div class="mt-5 grid gap-5 md:grid-cols-2">

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Tanggal Jurnal
                    </label>

                    <input
                        type="date"
                        name="entry_date"
                        value="{{ old('entry_date', now()->toDateString()) }}"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    @error('entry_date')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Nomor Referensi
                    </label>

                    <input
                        type="text"
                        name="reference_number"
                        value="{{ old('reference_number') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        placeholder="Opsional">
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Keterangan Jurnal
                    </label>

                    <textarea
                        name="description"
                        rows="3"
                        required
                        class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        placeholder="Contoh: Pencatatan saldo awal aset tetap">{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

        </section>

        <section
            x-data="{
                lines: @js(old('lines', [
                    [
                        'accounting_account_id' => '',
                        'description' => '',
                        'debit' => '',
                        'credit' => '',
                    ],
                    [
                        'accounting_account_id' => '',
                        'description' => '',
                        'debit' => '',
                        'credit' => '',
                    ],
                ])),

                addLine() {
                    this.lines.push({
                        accounting_account_id: '',
                        description: '',
                        debit: '',
                        credit: '',
                    });
                },

                removeLine(index) {
                    if (this.lines.length > 2) {
                        this.lines.splice(index, 1);
                    }
                },

                number(value) {
                    return Number.parseFloat(value) || 0;
                },

                totalDebit() {
                    return this.lines.reduce(
                        (total, line) =>
                            total + this.number(line.debit),
                        0
                    );
                },

                totalCredit() {
                    return this.lines.reduce(
                        (total, line) =>
                            total + this.number(line.credit),
                        0
                    );
                },

                difference() {
                    return this.totalDebit()
                        - this.totalCredit();
                },

                format(value) {
                    return new Intl.NumberFormat(
                        'id-ID'
                    ).format(value);
                }
            }"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 p-6">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Detail Debit dan Kredit
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Total debit dan kredit harus sama.
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click="addLine()"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white">

                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Tambah Baris
                </button>

            </div>

            @error('lines')
                <div class="border-b border-red-200 bg-red-50 px-6 py-4 text-sm text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="px-4 py-4">Akun</th>
                            <th class="px-4 py-4">Keterangan</th>
                            <th class="px-4 py-4 text-right">Debit</th>
                            <th class="px-4 py-4 text-right">Kredit</th>
                            <th class="px-4 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <template
                            x-for="(line, index) in lines"
                            x-bind:key="index">

                            <tr>

                                <td class="min-w-72 px-4 py-4">

                                    <select
                                        x-bind:name="`lines[${index}][accounting_account_id]`"
                                        x-model="line.accounting_account_id"
                                        required
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">

                                        <option value="">Pilih akun</option>

                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} — {{ $account->name }}
                                            </option>
                                        @endforeach

                                    </select>

                                </td>

                                <td class="min-w-64 px-4 py-4">

                                    <input
                                        type="text"
                                        x-bind:name="`lines[${index}][description]`"
                                        x-model="line.description"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"
                                        placeholder="Opsional">

                                </td>

                                <td class="min-w-44 px-4 py-4">

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        x-bind:name="`lines[${index}][debit]`"
                                        x-model="line.debit"
                                        x-on:input="
                                            if (number(line.debit) > 0) {
                                                line.credit = '';
                                            }
                                        "
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-right text-sm">

                                </td>

                                <td class="min-w-44 px-4 py-4">

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        x-bind:name="`lines[${index}][credit]`"
                                        x-model="line.credit"
                                        x-on:input="
                                            if (number(line.credit) > 0) {
                                                line.debit = '';
                                            }
                                        "
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-right text-sm">

                                </td>

                                <td class="px-4 py-4 text-center">

                                    <button
                                        type="button"
                                        x-on:click="removeLine(index)"
                                        x-bind:disabled="lines.length <= 2"
                                        class="rounded-xl p-2.5 text-red-500 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-30">

                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                    <tfoot class="bg-slate-50">

                        <tr class="font-bold">

                            <td colspan="2" class="px-4 py-4 text-right text-sm text-slate-700">
                                TOTAL
                            </td>

                            <td class="px-4 py-4 text-right text-blue-700">
                                Rp<span x-text="format(totalDebit())"></span>
                            </td>

                            <td class="px-4 py-4 text-right text-violet-700">
                                Rp<span x-text="format(totalCredit())"></span>
                            </td>

                            <td></td>

                        </tr>

                    </tfoot>

                </table>

            </div>

            <div
                class="border-t p-5"
                x-bind:class="
                    Math.abs(difference()) < 0.01
                    && totalDebit() > 0
                        ? 'border-emerald-200 bg-emerald-50'
                        : 'border-amber-200 bg-amber-50'
                ">

                <p
                    class="text-sm font-semibold"
                    x-bind:class="
                        Math.abs(difference()) < 0.01
                        && totalDebit() > 0
                            ? 'text-emerald-700'
                            : 'text-amber-700'
                    ">

                    Selisih:
                    Rp<span x-text="format(Math.abs(difference()))"></span>

                    <span
                        x-show="
                            Math.abs(difference()) < 0.01
                            && totalDebit() > 0
                        ">
                        — Jurnal seimbang
                    </span>

                </p>

            </div>

        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <label class="mb-2 block text-sm font-semibold text-slate-700">
                Catatan
            </label>

            <textarea
                name="notes"
                rows="3"
                class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                placeholder="Catatan tambahan">{{ old('notes') }}</textarea>

            <div class="mt-6 flex justify-end gap-3">

                <a
                    href="{{ route('journal-entries.index') }}"
                    class="rounded-2xl border border-slate-200 px-6 py-3 text-sm font-semibold text-slate-600">

                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                    <i data-lucide="save" class="h-5 w-5"></i>
                    Simpan sebagai Draft
                </button>

            </div>

        </section>

    </form>

@endsection
