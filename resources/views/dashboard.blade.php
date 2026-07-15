@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Ringkasan aktivitas dan kondisi koperasi')

@section('content')

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-900 p-6 text-white shadow-xl shadow-emerald-200 md:p-8">

        <div class="relative z-10 max-w-3xl">

            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold backdrop-blur">

                <i data-lucide="sparkles" class="h-4 w-4"></i>
                Selamat datang

            </span>

            <h1 class="mt-5 text-2xl font-bold leading-tight md:text-3xl">

                Halo, {{ auth()->user()->name }}!

            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-7 text-emerald-50">

                Selamat datang di
                <strong>
                    {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}
                </strong>.

                Pantau anggota, simpanan, pinjaman, angsuran,
                dan kondisi keuangan melalui satu dashboard terintegrasi.

            </p>

            @if ($appSetting?->tagline)

                <p class="mt-4 inline-flex rounded-full bg-white/10 px-4 py-2 text-xs text-emerald-100">
                    {{ $appSetting->tagline }}
                </p>

            @endif

        </div>

        <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full bg-white/10"></div>

        <div class="absolute -bottom-36 right-32 h-72 w-72 rounded-full bg-white/5"></div>

        <div class="absolute bottom-6 right-8 hidden opacity-20 md:block">

            @if ($appSetting?->logo_url)

                <img
                    src="{{ $appSetting->logo_url }}"
                    alt="{{ $appSetting->cooperative_name }}"
                    class="h-36 w-36 object-contain">

            @else

                <i data-lucide="landmark" class="h-36 w-36"></i>

            @endif

        </div>

    </section>

    <!-- Statistik -->
    <section class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-sm font-medium text-slate-500">
                        Total Anggota
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['members'], 0, ',', '.') }}
                    </h3>

                </div>

                <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>

            </div>

            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">

                <span class="flex items-center gap-1 font-semibold text-emerald-600">

                    <i data-lucide="user-check" class="h-4 w-4"></i>
                    Aktif

                </span>

                <span>anggota terdaftar</span>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-sm font-medium text-slate-500">
                        Total Simpanan
                    </p>

                    <h3 class="mt-3 text-2xl font-bold text-slate-900">
                        Rp{{ number_format($statistics['savings'], 0, ',', '.') }}
                    </h3>

                </div>

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                    <i data-lucide="wallet-cards" class="h-6 w-6"></i>
                </div>

            </div>

            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">

                <span class="flex items-center gap-1 font-semibold text-emerald-600">

                    <i data-lucide="trending-up" class="h-4 w-4"></i>
                    Terkelola

                </span>

                <span>seluruh simpanan</span>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-sm font-medium text-slate-500">
                        Pinjaman Aktif
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        {{ number_format($statistics['activeLoans'], 0, ',', '.') }}
                    </h3>

                </div>

                <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                    <i data-lucide="hand-coins" class="h-6 w-6"></i>
                </div>

            </div>

            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">

                <span class="flex items-center gap-1 font-semibold text-amber-600">

                    <i data-lucide="clock-3" class="h-4 w-4"></i>
                    Berjalan

                </span>

                <span>pinjaman anggota</span>

            </div>

        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">

            <div class="flex items-start justify-between">

                <div>

                    <p class="text-sm font-medium text-slate-500">
                        Saldo Kas
                    </p>

                    <h3 class="mt-3 text-2xl font-bold {{ $statistics['cashBalance'] >= 0 ? 'text-slate-900' : 'text-red-600' }}">

                        Rp{{ number_format($statistics['cashBalance'], 0, ',', '.') }}

                    </h3>

                </div>

                <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                    <i data-lucide="banknote" class="h-6 w-6"></i>
                </div>

            </div>

            <div class="mt-5 flex items-center gap-2 text-xs text-slate-500">

                <span class="flex items-center gap-1 font-semibold text-violet-600">

                    <i data-lucide="badge-check" class="h-4 w-4"></i>
                    Tersedia

                </span>

                <span>saldo koperasi</span>

            </div>

        </article>

    </section>

    <section class="mt-7 grid gap-6 xl:grid-cols-3">

        <!-- Informasi koperasi -->
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">

            <div class="flex items-center gap-3">

                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">

                    <i data-lucide="building-2" class="h-6 w-6"></i>

                </div>

                <div>

                    <h3 class="font-bold text-slate-900">
                        {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Informasi identitas koperasi
                    </p>

                </div>

            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">

                <div class="rounded-2xl bg-slate-50 p-5">

                    <p class="text-xs text-slate-500">
                        Nomor Badan Hukum
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $appSetting?->registration_number ?: '-' }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-50 p-5">

                    <p class="text-xs text-slate-500">
                        Nomor Telepon
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $appSetting?->phone ?: '-' }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-50 p-5">

                    <p class="text-xs text-slate-500">
                        Alamat Email
                    </p>

                    <p class="mt-2 break-all text-sm font-semibold text-slate-800">
                        {{ $appSetting?->email ?: '-' }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-50 p-5">

                    <p class="text-xs text-slate-500">
                        Ketua Koperasi
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $appSetting?->chairman_name ?: '-' }}
                    </p>

                </div>

                <div class="rounded-2xl bg-slate-50 p-5 sm:col-span-2">

                    <p class="text-xs text-slate-500">
                        Alamat Koperasi
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">
                        {{ $appSetting?->address ?: '-' }}
                    </p>

                </div>

            </div>

        </article>

        <!-- Menu cepat -->
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

            <div>

                <h3 class="font-bold text-slate-900">
                    Menu Cepat
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Akses fitur utama aplikasi
                </p>

            </div>

            <div class="mt-6 space-y-3">

                <a
                    href="{{ route('members.create') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-emerald-200 hover:bg-emerald-50">

                    <div class="rounded-xl bg-blue-100 p-3 text-blue-600">
                        <i data-lucide="user-plus" class="h-5 w-5"></i>
                    </div>

                    <div class="flex-1">

                        <p class="text-sm font-semibold text-slate-800">
                            Tambah Anggota
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Daftarkan anggota baru
                        </p>

                    </div>

                    <i data-lucide="chevron-right" class="h-5 w-5 text-slate-300 group-hover:text-emerald-600"></i>

                </a>

                <a
                    href="{{ route('savings.create') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-emerald-200 hover:bg-emerald-50">

                    <div class="rounded-xl bg-emerald-100 p-3 text-emerald-600">
                        <i data-lucide="circle-dollar-sign" class="h-5 w-5"></i>
                    </div>

                    <div class="flex-1">

                        <p class="text-sm font-semibold text-slate-800">
                            Input Simpanan
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Catat setoran anggota
                        </p>

                    </div>

                    <i data-lucide="chevron-right" class="h-5 w-5 text-slate-300 group-hover:text-emerald-600"></i>

                </a>

                <a
                    href="{{ route('loans.create') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-emerald-200 hover:bg-emerald-50">

                    <div class="rounded-xl bg-amber-100 p-3 text-amber-600">
                        <i data-lucide="file-plus-2" class="h-5 w-5"></i>
                    </div>

                    <div class="flex-1">

                        <p class="text-sm font-semibold text-slate-800">
                            Pengajuan Pinjaman
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Buat pinjaman anggota
                        </p>

                    </div>

                    <i data-lucide="chevron-right" class="h-5 w-5 text-slate-300 group-hover:text-emerald-600"></i>

                </a>

                <a
                    href="{{ route('installments.index') }}"
                    class="group flex items-center gap-4 rounded-2xl border border-slate-200 p-4 hover:border-emerald-200 hover:bg-emerald-50">

                    <div class="rounded-xl bg-violet-100 p-3 text-violet-600">
                        <i data-lucide="receipt-text" class="h-5 w-5"></i>
                    </div>

                    <div class="flex-1">

                        <p class="text-sm font-semibold text-slate-800">
                            Bayar Angsuran
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Catat pembayaran cicilan
                        </p>

                    </div>

                    <i data-lucide="chevron-right" class="h-5 w-5 text-slate-300 group-hover:text-emerald-600"></i>

                </a>

            </div>

        </article>

    </section>

@endsection
