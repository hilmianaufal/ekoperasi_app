@extends('layouts.app')

@section('title', 'Edit Anggota')
@section('page-title', 'Edit Anggota')
@section('page-description', 'Perbarui informasi anggota koperasi')

@section('content')

    <div class="mb-7">
        <div class="flex items-center gap-3">
            <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                <i data-lucide="user-pen" class="h-6 w-6"></i>
            </div>

            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Edit Data {{ $member->name }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Nomor anggota: {{ $member->member_number }}
                </p>
            </div>
        </div>
    </div>

    @include('members._form', ['member' => $member])

@endsection
