@extends('layouts.app')

@section('title', 'Edit Jenis Simpanan')
@section('page-title', 'Edit Jenis Simpanan')
@section('page-description', 'Perbarui pengaturan jenis simpanan')

@section('content')

    <div class="mx-auto max-w-4xl">
        @include('saving-types._form', [
            'savingType' => $savingType,
        ])
    </div>

@endsection
