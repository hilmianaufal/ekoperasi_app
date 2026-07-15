@extends('layouts.app')

@section('title', 'Tambah Anggota')
@section('page-title', 'Tambah Anggota')
@section('page-description', 'Daftarkan anggota baru ke dalam koperasi')

@section('content')

    <div class="mb-7">
        <div class="flex items-center gap-3">
            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                <i data-lucide="user-plus" class="h-6 w-6"></i>
            </div>

            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Formulir Anggota Baru
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Nomor anggota akan dibuat secara otomatis.
                </p>
            </div>
        </div>
    </div>

    @include('members._form')

@endsection
