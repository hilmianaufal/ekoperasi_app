@if ($allocations->isNotEmpty())

    <section x-data="{
        paymentOpen: false,
        paymentAction: '',
        memberName: '',
        remainingAmount: 0,
        formattedRemaining: '',
        paymentAmount: 0,

        openPayment(detail) {
            this.paymentAction =
                detail.action;

            this.memberName =
                detail.member;

            this.remainingAmount =
                detail.remaining;

            this.paymentAmount =
                detail.remaining;

            this.formattedRemaining =
                new Intl.NumberFormat(
                    'id-ID'
                ).format(
                    detail.remaining
                );

            this.paymentOpen = true;
        }
    }" x-on:open-shu-payment.window="
            openPayment($event.detail)
        "
        class="mt-7">

        <div class="mb-5 grid gap-4 sm:grid-cols-3">

            <article class="rounded-3xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">
                    Total Alokasi
                </p>

                <p class="mt-2 text-xl font-bold">
                    Rp{{ number_format($summary['allocated_total'], 0, ',', '.') }}
                </p>
            </article>

            <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-sm text-emerald-700">
                    Sudah Dibayar
                </p>

                <p class="mt-2 text-xl font-bold text-emerald-700">
                    Rp{{ number_format($summary['paid_total'], 0, ',', '.') }}
                </p>
            </article>

            <article class="rounded-3xl border border-blue-200 bg-blue-50 p-5">
                <p class="text-sm text-blue-700">
                    Sisa Pembayaran
                </p>

                <p class="mt-2 text-xl font-bold text-blue-700">
                    Rp{{ number_format($summary['remaining_total'], 0, ',', '.') }}
                </p>
            </article>

        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-6">

                <h3 class="font-bold text-slate-900">
                    Alokasi dan Pembayaran SHU
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    {{ $summary['unpaid_count'] }}
                    anggota masih memiliki sisa pembayaran.
                </p>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="px-5 py-4">Anggota</th>
                            <th class="px-5 py-4 text-right">JASUS</th>
                            <th class="px-5 py-4 text-right">JASIM</th>
                            <th class="px-5 py-4 text-right">Total SHU</th>
                            <th class="px-5 py-4 text-right">Dibayar</th>
                            <th class="px-5 py-4 text-right">Sisa</th>
                            <th class="px-5 py-4 text-center">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach ($allocations as $allocation)
                            @php
                                $remaining = max((float) $allocation->total_shu - (float) $allocation->paid_amount, 0);

                                $statusClass = match ($allocation->payment_status) {
                                    'paid' => 'bg-emerald-100 text-emerald-700',

                                    'partial' => 'bg-amber-100 text-amber-700',

                                    default => 'bg-slate-100 text-slate-600',
                                };

                                $statusLabel = match ($allocation->payment_status) {
                                    'paid' => 'Lunas',
                                    'partial' => 'Sebagian',
                                    default => 'Belum Dibayar',
                                };
                            @endphp

                            <tr>

                                <td class="px-5 py-4">

                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $allocation->member?->name }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $allocation->member?->member_number }}
                                    </p>

                                </td>

                                <td class="px-5 py-4 text-right text-sm">
                                    Rp{{ number_format($allocation->business_service_amount, 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4 text-right text-sm">
                                    Rp{{ number_format($allocation->saving_service_amount, 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4 text-right text-sm font-bold text-slate-900">
                                    Rp{{ number_format($allocation->total_shu, 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4 text-right text-sm text-emerald-700">
                                    Rp{{ number_format($allocation->paid_amount, 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4 text-right text-sm font-semibold text-blue-700">
                                    Rp{{ number_format($remaining, 0, ',', '.') }}
                                </td>

                                <td class="px-5 py-4 text-center">

                                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>

                                </td>

                                <td class="px-5 py-4 text-right">

                                    @if (in_array($shuPeriod->status, ['approved', 'distributed'], true) && $remaining > 0)
                                        <button type="button"
                                            x-on:click="
                                                $dispatch(
                                                    'open-shu-payment',
                                                    {
                                                        action: @js(route('shu-payments.store', $allocation)),
                                                        member: @js($allocation->member?->name),
                                                        remaining: {{ $remaining }}
                                                    }
                                                )
                                            "
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">

                                            <i data-lucide="banknote" class="h-4 w-4"></i>
                                            Bayar
                                        </button>
                                    @elseif ($allocation->payment_status === 'paid')
                                        <span class="text-xs font-semibold text-emerald-600">
                                            Lunas
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">
                                            Menunggu persetujuan
                                        </span>
                                    @endif
                                    @if ($allocation->payments->isNotEmpty())
                                        <a href="{{ route('shu-payments.show', $allocation->payments->first()) }}"
                                            title="Lihat pembayaran terakhir"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-600">

                                            <i data-lucide="receipt-text" class="h-4 w-4"></i>
                                            Kuitansi
                                        </a>
                                    @endif
                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

            @if ($allocations->hasPages())
                <div class="border-t border-slate-200 p-6">
                    {{ $allocations->links() }}
                </div>
            @endif

        </div>

        <div x-show="paymentOpen" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">

            <div x-on:click.outside="
                    paymentOpen = false
                " x-transition
                class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">

                <div class="flex items-center justify-between">

                    <div>

                        <h3 class="text-lg font-bold text-slate-900">
                            Pembayaran SHU
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Anggota:
                            <strong x-text="memberName"></strong>
                        </p>

                    </div>

                    <button type="button"
                        x-on:click="
                            paymentOpen = false
                        "
                        class="rounded-xl p-2 text-slate-400 hover:bg-slate-100">

                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>

                </div>

                <div class="mt-5 rounded-2xl bg-emerald-50 p-4">

                    <p class="text-xs text-emerald-700">
                        Sisa SHU
                    </p>

                    <p class="mt-1 text-xl font-bold text-emerald-700">
                        Rp<span x-text="formattedRemaining"></span>
                    </p>

                </div>

                <form x-bind:action="paymentAction" method="POST" class="mt-5 space-y-4">

                    @csrf

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal Pembayaran
                        </label>

                        <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nominal Pembayaran
                        </label>

                        <input type="number" name="amount" x-model="paymentAmount" x-bind:max="remainingAmount"
                            min="1" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Metode Pembayaran
                        </label>

                        <select name="payment_method" required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm">

                            <option value="cash">
                                Tunai
                            </option>

                            <option value="transfer">
                                Transfer
                            </option>

                            <option value="other">
                                Lainnya
                            </option>

                        </select>

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Nomor Referensi
                        </label>

                        <input type="text" name="reference_number"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                            placeholder="Opsional">

                    </div>

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Catatan
                        </label>

                        <textarea name="notes" rows="3"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                            placeholder="Catatan pembayaran"></textarea>

                    </div>

                    <div class="flex gap-3 pt-2">

                        <button type="button"
                            x-on:click="
                                paymentOpen = false
                            "
                            class="flex-1 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">

                            Batal
                        </button>

                        <button type="submit"
                            class="flex-1 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">

                            Simpan Pembayaran
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </section>

@endif
