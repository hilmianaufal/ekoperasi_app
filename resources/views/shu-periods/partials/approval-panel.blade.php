@if (
    in_array(
        $shuPeriod->status,
        ['draft', 'review'],
        true
    )
    && $summary['member_count'] > 0
)

    <section class="mt-7 rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm">

        <div class="flex items-start gap-4">

            <div class="rounded-2xl bg-amber-100 p-3 text-amber-700">
                <i data-lucide="shield-alert" class="h-6 w-6"></i>
            </div>

            <div class="flex-1">

                <h3 class="font-bold text-amber-900">
                    Persetujuan Periode SHU
                </h3>

                <p class="mt-2 text-sm leading-7 text-amber-700">
                    Periode harus disetujui sebelum pembayaran SHU dapat dilakukan.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">

                    <article class="rounded-2xl bg-white/80 p-4">
                        <p class="text-xs text-slate-500">
                            Ketetapan Anggota
                        </p>

                        <p class="mt-2 font-bold text-slate-900">
                            Rp{{ number_format(
                                $shuPeriod->declared_member_shu,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>
                    </article>

                    <article class="rounded-2xl bg-white/80 p-4">
                        <p class="text-xs text-slate-500">
                            Total Alokasi
                        </p>

                        <p class="mt-2 font-bold text-slate-900">
                            Rp{{ number_format(
                                $summary['allocated_total'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>
                    </article>

                    <article class="rounded-2xl bg-white/80 p-4">
                        <p class="text-xs text-slate-500">
                            Selisih
                        </p>

                        <p class="mt-2 font-bold {{ abs($summary['difference']) >= 0.01 ? 'text-red-600' : 'text-emerald-600' }}">
                            Rp{{ number_format(
                                $summary['difference'],
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>
                    </article>

                </div>

                <form
                    action="{{ route(
                        'shu-periods.approve',
                        $shuPeriod
                    ) }}"
                    method="POST"
                    class="mt-5 space-y-4">

                    @csrf

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-amber-900">
                            Catatan Persetujuan
                        </label>

                        <textarea
                            name="approval_notes"
                            rows="3"
                            class="w-full resize-none rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm"
                            placeholder="Masukkan nomor keputusan RAT atau hasil konfirmasi client">{{ old('approval_notes') }}</textarea>

                    </div>

                    @if (
                        abs(
                            $summary['difference']
                        ) >= 0.01
                    )

                        <label class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">

                            <input
                                type="checkbox"
                                name="acknowledge_difference"
                                value="1"
                                class="mt-1 h-4 w-4 rounded border-red-300 text-red-600">

                            <span class="text-sm leading-6 text-red-700">
                                Saya sudah memeriksa dan menyetujui selisih alokasi sebesar
                                <strong>
                                    Rp{{ number_format(
                                        abs($summary['difference']),
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </strong>.
                            </span>

                        </label>

                    @endif

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-600 px-6 py-3.5 text-sm font-semibold text-white hover:bg-amber-700">

                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                        Setujui Periode SHU
                    </button>

                </form>

            </div>

        </div>

    </section>

@elseif (
    in_array(
        $shuPeriod->status,
        ['approved', 'distributed'],
        true
    )
)

    <section class="mt-7 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">

        <div class="flex items-center gap-4">

            <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                <i data-lucide="badge-check" class="h-6 w-6"></i>
            </div>

            <div>

                <h3 class="font-bold text-emerald-900">
                    Periode SHU Sudah Disetujui
                </h3>

                <p class="mt-1 text-sm text-emerald-700">
                    Disetujui oleh
                    {{ $shuPeriod->approver?->name ?? 'Administrator' }}
                    pada
                    {{ $shuPeriod->approved_at?->translatedFormat('d F Y H:i') }}.
                </p>

            </div>

        </div>

    </section>

@endif
