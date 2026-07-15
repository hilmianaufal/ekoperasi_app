@extends('layouts.app')

@section('title', 'Detail Anggota')
@section('page-title', 'Detail Anggota')
@section('page-description', 'Informasi lengkap anggota koperasi')

@section('content')

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a
            href="{{ route('members.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke daftar anggota
        </a>

        <a
            href="{{ route('members.edit', $member) }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-200 hover:bg-amber-600">

            <i data-lucide="user-pen" class="h-5 w-5"></i>
            Edit Anggota
        </a>

    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="relative bg-gradient-to-br from-emerald-600 to-teal-800 px-6 pb-28 pt-8 text-white">

            <div class="relative z-10">
                <span class="rounded-full bg-white/15 px-4 py-2 text-xs font-semibold backdrop-blur">
                    {{ $member->member_number }}
                </span>
            </div>

            <div class="absolute -right-12 -top-24 h-64 w-64 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-32 right-32 h-64 w-64 rounded-full bg-white/10"></div>

        </div>

        <div class="px-6 pb-8">

            <div class="-mt-20 flex flex-col items-center gap-5 sm:flex-row sm:items-end">

                @if ($member->photo)
                    <img
                        src="{{ asset('storage/' . $member->photo) }}"
                        alt="{{ $member->name }}"
                        class="relative h-40 w-40 rounded-[2rem] border-4 border-white object-cover shadow-xl">
                @else
                    <div class="relative flex h-40 w-40 items-center justify-center rounded-[2rem] border-4 border-white bg-emerald-100 text-5xl font-bold text-emerald-700 shadow-xl">
                        {{ strtoupper(substr($member->name, 0, 1)) }}
                    </div>
                @endif

                <div class="pb-3 text-center sm:text-left">

                    <div class="flex flex-col items-center gap-3 sm:flex-row">

                        <h1 class="text-2xl font-bold text-slate-900">
                            {{ $member->name }}
                        </h1>

                        @if ($member->status === 'active')
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Anggota Aktif
                            </span>
                        @else
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                Tidak Aktif
                            </span>
                        @endif

                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        Bergabung sejak
                        {{ $member->join_date->translatedFormat('d F Y') }}
                    </p>

                </div>

            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-2">

                <article class="rounded-3xl border border-slate-200 p-6">

                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                            <i data-lucide="contact" class="h-5 w-5"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Identitas Anggota
                            </h3>

                            <p class="text-xs text-slate-500">
                                Informasi pribadi anggota
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-5">

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Jenis kelamin</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $member->gender_label }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Tempat lahir</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $member->place_of_birth ?: '-' }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Tanggal lahir</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $member->date_of_birth
                                    ? $member->date_of_birth->translatedFormat('d F Y')
                                    : '-' }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-slate-500">Nomor anggota</dt>
                            <dd class="text-right text-sm font-semibold text-emerald-600">
                                {{ $member->member_number }}
                            </dd>
                        </div>

                    </dl>

                </article>

                <article class="rounded-3xl border border-slate-200 p-6">

                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                            <i data-lucide="map-pinned" class="h-5 w-5"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Kontak dan Alamat
                            </h3>

                            <p class="text-xs text-slate-500">
                                Informasi komunikasi anggota
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 space-y-5">

                        <div class="border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">
                                Nomor telepon
                            </dt>

                            <dd class="mt-1 text-sm font-semibold text-slate-800">
                                {{ $member->phone ?: '-' }}
                            </dd>
                        </div>

                        <div class="border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">
                                Alamat email
                            </dt>

                            <dd class="mt-1 break-all text-sm font-semibold text-slate-800">
                                {{ $member->email ?: '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm text-slate-500">
                                Alamat lengkap
                            </dt>

                            <dd class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">
                                {{ $member->address ?: '-' }}
                            </dd>
                        </div>

                    </dl>

                </article>

            </div>

        </div>

    </section>

@endsection
