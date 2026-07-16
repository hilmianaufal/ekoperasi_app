@extends('layouts.app')

@section('title', 'Daftar Akun')
@section('page-title', 'Daftar Akun')
@section('page-description', 'Chart of Accounts koperasi')

@section('content')

    <section class="rounded-3xl bg-gradient-to-br from-indigo-700 to-slate-950 p-7 text-white shadow-lg">

        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-200">
            Chart of Accounts
        </p>

        <h1 class="mt-3 text-3xl font-bold">
            Daftar Akun Akuntansi
        </h1>

        <p class="mt-3 max-w-2xl text-sm leading-7 text-indigo-100">
            Daftar akun yang digunakan untuk jurnal, buku besar, neraca saldo, dan laporan keuangan.
        </p>

    </section>

    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Jumlah Akun</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">
                {{ $statistics['total'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm text-emerald-700">Akun Aktif</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">
                {{ $statistics['active'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
            <p class="text-sm text-violet-700">Akun Header</p>
            <p class="mt-2 text-3xl font-bold text-violet-700">
                {{ $statistics['headers'] }}
            </p>
        </article>

        <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <p class="text-sm text-blue-700">Akun Transaksi</p>
            <p class="mt-2 text-3xl font-bold text-blue-700">
                {{ $statistics['transaction_accounts'] }}
            </p>
        </article>

    </section>

    <section class="mt-7 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            action="{{ route('accounting-accounts.index') }}"
            method="GET"
            class="grid gap-4 md:grid-cols-3">

            <div class="md:col-span-2">

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Pencarian
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari kode atau nama akun"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

            </div>

            <div>

                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">
                    Kelompok
                </label>

                <select
                    name="type"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    <option value="">Semua kelompok</option>
                    <option value="asset" @selected($type === 'asset')>Aset</option>
                    <option value="liability" @selected($type === 'liability')>Liabilitas</option>
                    <option value="equity" @selected($type === 'equity')>Ekuitas</option>
                    <option value="revenue" @selected($type === 'revenue')>Pendapatan</option>
                    <option value="expense" @selected($type === 'expense')>Beban</option>

                </select>

            </div>

            <div class="flex gap-3 md:col-span-3">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">

                    <i data-lucide="search" class="h-4 w-4"></i>
                    Terapkan
                </button>

                <a
                    href="{{ route('accounting-accounts.index') }}"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">

                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
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
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Akun</th>
                        <th class="px-6 py-4">Kelompok</th>
                        <th class="px-6 py-4">Saldo Normal</th>
                        <th class="px-6 py-4">Mapping</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($accounts as $account)

                        @php
                            $typeClass = match ($account->type) {
                                'asset' => 'bg-blue-100 text-blue-700',
                                'liability' => 'bg-amber-100 text-amber-700',
                                'equity' => 'bg-violet-100 text-violet-700',
                                'revenue' => 'bg-emerald-100 text-emerald-700',
                                'expense' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="{{ $account->is_header ? 'bg-slate-50' : '' }}">

                            <td class="px-6 py-4">
                                <p class="font-mono text-sm font-bold text-slate-800">
                                    {{ $account->code }}
                                </p>
                            </td>

                            <td class="px-6 py-4">

                                <p class="text-sm {{ $account->is_header ? 'font-bold uppercase' : 'font-semibold' }} text-slate-800">
                                    {{ $account->name }}
                                </p>

                                @if ($account->parent)

                                    <p class="mt-1 text-xs text-slate-400">
                                        Induk:
                                        {{ $account->parent->code }}
                                        {{ $account->parent->name }}
                                    </p>

                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $typeClass }}">
                                    {{ $account->type_label }}
                                </span>

                            </td>

                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                                {{ $account->normal_balance_label }}
                            </td>

                            <td class="px-6 py-4">

                                @if ($account->mapping)

                                    <code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-600">
                                        {{ $account->mapping->mapping_key }}
                                    </code>

                                @else

                                    <span class="text-xs text-slate-400">
                                        -
                                    </span>

                                @endif

                            </td>

                            <td class="px-6 py-4 text-center">

                                <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $account->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $account->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">
                                Data akun tidak ditemukan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($accounts->hasPages())

            <div class="border-t border-slate-200 p-6">
                {{ $accounts->links() }}
            </div>

        @endif

    </section>

@endsection
