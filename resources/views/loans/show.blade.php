@extends('layouts.app')

@section('title', 'Detail Pinjaman')
@section('page-title', 'Detail Pinjaman')
@section('page-description', 'Informasi dan jadwal angsuran pinjaman')

@section('content')

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

        <a href="{{ route('loans.index') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-600">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali ke daftar pinjaman
        </a>

        @if ($loan->status === 'pending')
            <form action="{{ route('loans.cancel', $loan) }}" method="POST" id="cancel-loan-form">

                @csrf

                <button type="button" onclick="confirmCancelLoan()"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-600 hover:bg-red-100">

                    <i data-lucide="circle-x" class="h-5 w-5"></i>
                    Batalkan Pengajuan
                </button>

            </form>
        @endif

    </div>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="relative bg-gradient-to-br from-emerald-600 to-teal-800 p-7 text-white">

            <div class="relative z-10 flex flex-col justify-between gap-5 md:flex-row md:items-center">

                <div>

                    <p class="text-sm text-emerald-100">
                        Nomor pinjaman
                    </p>

                    <h1 class="mt-2 text-2xl font-bold">
                        {{ $loan->loan_number }}
                    </h1>

                    <p class="mt-2 text-sm text-emerald-100">
                        {{ $loan->member->member_number }}
                        — {{ $loan->member->name }}
                    </p>

                </div>

                @php
                    $statusClass = match ($loan->status) {
                        'pending' => 'bg-amber-400 text-amber-950',
                        'active' => 'bg-blue-400 text-blue-950',
                        'paid' => 'bg-white text-emerald-700',
                        'rejected' => 'bg-red-400 text-red-950',
                        default => 'bg-white/20 text-white',
                    };
                @endphp

                <span class="w-fit rounded-full px-4 py-2 text-xs font-bold {{ $statusClass }}">
                    {{ $loan->status_label }}
                </span>

            </div>

            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10"></div>

        </div>

        <div class="p-6 md:p-8">

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                <article class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-medium text-slate-500">
                        Pokok Pinjaman
                    </p>

                    <p class="mt-2 text-xl font-bold text-slate-900">
                        Rp{{ number_format($loan->principal_amount, 0, ',', '.') }}
                    </p>

                </article>

                <article class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-medium text-slate-500">
                        Total Bunga
                    </p>

                    <p class="mt-2 text-xl font-bold text-amber-600">
                        Rp{{ number_format($loan->interest_amount, 0, ',', '.') }}
                    </p>

                </article>

                <article class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-medium text-slate-500">
                        Total Tagihan
                    </p>

                    <p class="mt-2 text-xl font-bold text-blue-600">
                        Rp{{ number_format($loan->total_amount, 0, ',', '.') }}
                    </p>

                </article>

                <article class="rounded-2xl border border-slate-200 p-5">

                    <p class="text-xs font-medium text-slate-500">
                        Angsuran Bulanan
                    </p>

                    <p class="mt-2 text-xl font-bold text-emerald-600">
                        Rp{{ number_format($loan->monthly_installment, 0, ',', '.') }}
                    </p>

                </article>

            </div>

            <div class="mt-7 grid gap-6 lg:grid-cols-2">

                <article class="rounded-3xl border border-slate-200 p-6">

                    <h3 class="font-bold text-slate-900">
                        Informasi Pengajuan
                    </h3>

                    <dl class="mt-6 space-y-4">

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Tanggal pengajuan</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $loan->application_date->translatedFormat('d F Y') }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Bunga flat</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ number_format($loan->interest_rate, 2, ',', '.') }}% per bulan
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-4">
                            <dt class="text-sm text-slate-500">Tenor</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $loan->tenor_months }} bulan
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-slate-500">Dibuat oleh</dt>
                            <dd class="text-right text-sm font-semibold text-slate-800">
                                {{ $loan->creator?->name ?? 'Sistem' }}
                            </dd>
                        </div>

                    </dl>

                </article>

                <article class="rounded-3xl border border-slate-200 p-6">

                    <h3 class="font-bold text-slate-900">
                        Tujuan Pinjaman
                    </h3>

                    <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-700">
                        {{ $loan->purpose }}
                    </p>

                    @if ($loan->notes)
                        <div class="mt-5 rounded-2xl bg-slate-50 p-4">

                            <p class="text-xs font-semibold text-slate-500">
                                Catatan
                            </p>

                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                                {{ $loan->notes }}
                            </p>

                        </div>
                    @endif

                </article>

            </div>

            @if ($loan->status === 'pending')
                <section class="mt-7 grid gap-6 lg:grid-cols-2">

                    <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6">

                        <div class="flex items-center gap-3">

                            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                                <i data-lucide="badge-check" class="h-6 w-6"></i>
                            </div>

                            <div>
                                <h3 class="font-bold text-emerald-900">
                                    Setujui Pinjaman
                                </h3>

                                <p class="mt-1 text-xs text-emerald-700">
                                    Jadwal angsuran akan dibuat otomatis.
                                </p>
                            </div>

                        </div>

                        <form action="{{ route('loans.approve', $loan) }}" method="POST" id="approve-loan-form"
                            class="mt-6">

                            @csrf

                            <label for="start_date" class="mb-2 block text-sm font-semibold text-emerald-900">
                                Tanggal pencairan
                            </label>

                            <input type="date" name="start_date" id="start_date"
                                value="{{ old('start_date', now()->format('Y-m-d')) }}" required
                                class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500">

                            <button type="button" onclick="confirmApproveLoan()"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                                <i data-lucide="check" class="h-5 w-5"></i>
                                Setujui Pinjaman
                            </button>

                        </form>

                    </article>

                    <article class="rounded-3xl border border-red-200 bg-red-50 p-6">

                        <div class="flex items-center gap-3">

                            <div class="rounded-2xl bg-red-100 p-3 text-red-600">
                                <i data-lucide="circle-x" class="h-6 w-6"></i>
                            </div>

                            <div>
                                <h3 class="font-bold text-red-900">
                                    Tolak Pinjaman
                                </h3>

                                <p class="mt-1 text-xs text-red-700">
                                    Masukkan alasan penolakan pengajuan.
                                </p>
                            </div>

                        </div>

                        <form action="{{ route('loans.reject', $loan) }}" method="POST" id="reject-loan-form"
                            class="mt-6">

                            @csrf

                            <textarea name="rejection_reason" id="rejection_reason" rows="4" required placeholder="Masukkan alasan penolakan"
                                class="w-full resize-none rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm outline-none focus:border-red-500">{{ old('rejection_reason') }}</textarea>

                            <button type="button" onclick="confirmRejectLoan()"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700">

                                <i data-lucide="x" class="h-5 w-5"></i>
                                Tolak Pinjaman
                            </button>

                        </form>

                    </article>

                </section>
            @endif

            @if ($loan->status === 'rejected' && $loan->rejection_reason)
                <section class="mt-7 rounded-3xl border border-red-200 bg-red-50 p-6">

                    <h3 class="font-bold text-red-800">
                        Alasan Penolakan
                    </h3>

                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-red-700">
                        {{ $loan->rejection_reason }}
                    </p>

                </section>
            @endif

            @if ($loan->installments->isNotEmpty())

                <section class="mt-7 overflow-hidden rounded-3xl border border-slate-200">

                    <div
                        class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

                        <div>

                            <h3 class="font-bold text-slate-900">
                                Jadwal Angsuran
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Jadwal pembayaran selama {{ $loan->tenor_months }} bulan.
                            </p>

                        </div>

                        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-right">

                            <p class="text-xs text-emerald-600">
                                Sisa pinjaman
                            </p>

                            <p class="mt-1 font-bold text-emerald-700">
                                Rp{{ number_format($summary['remaining_amount'], 0, ',', '.') }}
                            </p>

                        </div>

                    </div>

                    <div class="overflow-x-auto">

                        <table class="min-w-full">

                            <thead class="bg-slate-50">

                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-4">Angsuran</th>
                                    <th class="px-6 py-4">Jatuh Tempo</th>
                                    <th class="px-6 py-4 text-right">Pokok</th>
                                    <th class="px-6 py-4 text-right">Bunga</th>
                                    <th class="px-6 py-4 text-right">Total</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                @foreach ($loan->installments as $installment)
                                    @php
                                        $displayStatus = $installment->status;

                                        if (
                                            in_array($installment->status, ['unpaid', 'partial'], true) &&
                                            $installment->due_date->isPast()
                                        ) {
                                            $displayStatus = 'overdue';
                                        }

                                        $installmentClass = match ($displayStatus) {
                                            'paid' => 'bg-emerald-100 text-emerald-700',
                                            'partial' => 'bg-blue-100 text-blue-700',
                                            'overdue' => 'bg-red-100 text-red-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };

                                        $installmentLabel = match ($displayStatus) {
                                            'paid' => 'Lunas',
                                            'partial' => 'Sebagian',
                                            'overdue' => 'Terlambat',
                                            default => 'Belum Dibayar',
                                        };
                                    @endphp

                                    <tr class="hover:bg-slate-50">

                                        <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                            Ke-{{ $installment->installment_number }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">
                                            {{ $installment->due_date->translatedFormat('d M Y') }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                            Rp{{ number_format($installment->principal_amount, 0, ',', '.') }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">
                                            Rp{{ number_format($installment->interest_amount, 0, ',', '.') }}
                                        </td>

                                        <td
                                            class="whitespace-nowrap px-6 py-4 text-right text-sm font-bold text-slate-900">
                                            Rp{{ number_format($installment->total_amount, 0, ',', '.') }}
                                        </td>

                                        <td class="px-6 py-4">

                                            <span
                                                class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $installmentClass }}">
                                                {{ $installmentLabel }}
                                            </span>

                                        </td>
                                        <td class="px-6 py-4">

                                            <div class="flex justify-end">

                                                @if ($loan->status === 'active' && $installment->status !== 'paid')
                                                    <a href="{{ route('installments.pay', $installment) }}"
                                                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-emerald-700">

                                                        <i data-lucide="hand-coins" class="h-4 w-4"></i>
                                                        Bayar
                                                    </a>
                                                @elseif ($installment->payments->isNotEmpty())
                                                    <a href="{{ route('installment-payments.show', $installment->payments->first()) }}"
                                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">

                                                        <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                                        Kuitansi
                                                    </a>
                                                @endif

                                            </div>

                                        </td>

                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>

                </section>

            @endif

        </div>

    </section>

@endsection

@push('scripts')
    <script>
        function confirmApproveLoan() {
            const startDate = document.getElementById('start_date').value;

            if (!startDate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tanggal belum diisi',
                    text: 'Silakan pilih tanggal pencairan pinjaman.',
                    confirmButtonColor: '#059669',
                });

                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Setujui pinjaman?',
                text: 'Jadwal angsuran akan dibuat secara otomatis.',
                showCancelButton: true,
                confirmButtonText: 'Ya, setujui',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('approve-loan-form').submit();
                }
            });
        }

        function confirmRejectLoan() {
            const reason = document
                .getElementById('rejection_reason')
                .value
                .trim();

            if (!reason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Alasan belum diisi',
                    text: 'Silakan masukkan alasan penolakan.',
                    confirmButtonColor: '#dc2626',
                });

                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Tolak pinjaman?',
                text: 'Pengajuan akan ditandai sebagai ditolak.',
                showCancelButton: true,
                confirmButtonText: 'Ya, tolak',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('reject-loan-form').submit();
                }
            });
        }

        function confirmCancelLoan() {
            Swal.fire({
                icon: 'warning',
                title: 'Batalkan pengajuan?',
                text: 'Pengajuan pinjaman akan dibatalkan.',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel-loan-form').submit();
                }
            });
        }
    </script>
@endpush
