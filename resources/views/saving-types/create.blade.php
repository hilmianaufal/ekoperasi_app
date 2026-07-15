@extends('layouts.app')

@section('title', 'Tambah Jenis Simpanan')
@section('page-title', 'Tambah Jenis Simpanan')
@section('page-description', 'Tambahkan kategori simpanan koperasi')

@section('content')

    <div class="mx-auto max-w-4xl">
        @include('saving-types._form')
    </div>

@endsection
