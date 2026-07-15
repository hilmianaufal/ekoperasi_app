<div class="overflow-x-auto">

    @if ($reportType === 'members')

        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-4">Nomor Anggota</th>
                    <th class="px-5 py-4">Nama</th>
                    <th class="px-5 py-4">Jenis Kelamin</th>
                    <th class="px-5 py-4">Kontak</th>
                    <th class="px-5 py-4">Bergabung</th>
                    <th class="px-5 py-4">Status</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $member)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-emerald-600">
                            {{ $member->member_number }}
                        </td>

                        <td class="px-5 py-4 text-sm font-semibold text-slate-800">
                            {{ $member->name }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                            {{ $member->gender_label }}
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm text-slate-700">
                                {{ $member->phone ?: '-' }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $member->email ?: '-' }}
                            </p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                            {{ $member->join_date->translatedFormat('d M Y') }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                                {{ $member->status === 'active'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-600' }}">

                                {{ $member->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">
                            Data laporan tidak tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($reportType === 'savings')

        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-4">Transaksi</th>
                    <th class="px-5 py-4">Anggota</th>
                    <th class="px-5 py-4">Jenis Simpanan</th>
                    <th class="px-5 py-4">Tipe</th>
                    <th class="px-5 py-4 text-right">Nominal</th>
                    <th class="px-5 py-4 text-right">Saldo Akhir</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $transaction)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $transaction->transaction_code }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $transaction->member->name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $transaction->member->member_number }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm text-slate-700">
                                {{ $transaction->savingType->name }}
                            </p>

                            <p class="mt-1 text-xs font-semibold text-emerald-600">
                                {{ $transaction->savingType->code }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                                {{ $transaction->transaction_type === 'deposit'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-700' }}">

                                {{ $transaction->transaction_type_label }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold
                            {{ $transaction->transaction_type === 'deposit'
                                ? 'text-emerald-600'
                                : 'text-red-600' }}">

                            {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}
                            Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800">
                            Rp{{ number_format($transaction->balance_after, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">
                            Data laporan tidak tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($reportType === 'loans')

        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-4">Pinjaman</th>
                    <th class="px-5 py-4">Anggota</th>
                    <th class="px-5 py-4 text-right">Pokok</th>
                    <th class="px-5 py-4">Bunga</th>
                    <th class="px-5 py-4">Tenor</th>
                    <th class="px-5 py-4 text-right">Total</th>
                    <th class="px-5 py-4">Status</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $loan)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $loan->loan_number }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $loan->application_date->translatedFormat('d M Y') }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $loan->member->name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $loan->member->member_number }}
                            </p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-slate-800">
                            Rp{{ number_format($loan->principal_amount, 0, ',', '.') }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                            {{ number_format($loan->interest_rate, 2, ',', '.') }}%
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                            {{ $loan->tenor_months }} bulan
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-blue-600">
                            Rp{{ number_format($loan->total_amount, 0, ',', '.') }}
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $loanStatusClass = match ($loan->status) {
                                    'active' => 'bg-blue-100 text-blue-700',
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                            @endphp

                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $loanStatusClass }}">
                                {{ $loan->status_label }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center text-sm text-slate-500">
                            Data laporan tidak tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($reportType === 'installments')

        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-4">Pembayaran</th>
                    <th class="px-5 py-4">Anggota</th>
                    <th class="px-5 py-4">Pinjaman</th>
                    <th class="px-5 py-4">Metode</th>
                    <th class="px-5 py-4 text-right">Nominal</th>
                    <th class="px-5 py-4 text-right">Sisa</th>
                    <th class="px-5 py-4">Petugas</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $payment)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4">
                            <p class="text-sm font-semibold text-emerald-600">
                                {{ $payment->payment_code }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $payment->payment_date->translatedFormat('d M Y') }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $payment->installment->loan->member->name }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $payment->installment->loan->member->member_number }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-sm text-slate-700">
                                {{ $payment->installment->loan->loan_number }}
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                Angsuran ke-{{ $payment->installment->installment_number }}
                            </p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                            {{ $payment->payment_method_label }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-emerald-600">
                            Rp{{ number_format($payment->amount, 0, ',', '.') }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold text-amber-600">
                            Rp{{ number_format($payment->remaining_after, 0, ',', '.') }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                            {{ $payment->user?->name ?? 'Sistem' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center text-sm text-slate-500">
                            Data laporan tidak tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @else

        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-4">Transaksi</th>
                    <th class="px-5 py-4">Kategori</th>
                    <th class="px-5 py-4">Keterangan</th>
                    <th class="px-5 py-4">Metode</th>
                    <th class="px-5 py-4">Jenis</th>
                    <th class="px-5 py-4 text-right">Nominal</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($rows as $transaction)
                    <tr class="hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-4">
                            <p class="text-sm font-semibold text-slate-800">
                                {{ $transaction->transaction_code }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $transaction->transaction_date->translatedFormat('d M Y') }}
                            </p>
                        </td>

                        <td class="px-5 py-4 text-sm font-semibold text-slate-700">
                            {{ $transaction->category }}
                        </td>

                        <td class="px-5 py-4">
                            <p class="max-w-md text-sm leading-6 text-slate-600">
                                {{ $transaction->description ?: '-' }}
                            </p>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                            {{ $transaction->payment_method_label }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                                {{ $transaction->direction === 'income'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-700' }}">

                                {{ $transaction->direction_label }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold
                            {{ $transaction->direction === 'income'
                                ? 'text-emerald-600'
                                : 'text-red-600' }}">

                            {{ $transaction->direction === 'income' ? '+' : '-' }}
                            Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center text-sm text-slate-500">
                            Data laporan tidak tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @endif

</div>
