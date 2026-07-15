<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $reportTitle }}
        |
        {{ $appSetting?->short_name ?? 'e-Koperasi' }}
    </title>

    @if ($appSetting?->logo_url)
        <link rel="icon" href="{{ $appSetting->logo_url }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @page {
            size: landscape;
            margin: 12mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .print-container {
                padding: 0 !important;
                max-width: 100% !important;
            }

            .print-document {
                border: none !important;
                border-radius: 0 !important;
            }

            table {
                font-size: 10px !important;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800">

    <main class="print-container mx-auto max-w-[1500px] p-6">

        <div class="no-print mb-6 flex justify-end gap-3">

            <button
                type="button"
                onclick="window.close()"
                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600">

                Tutup

            </button>

            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white">

                <i data-lucide="printer" class="h-5 w-5"></i>
                Cetak / Simpan PDF

            </button>

        </div>

        <section class="print-document overflow-hidden rounded-3xl border border-slate-200 bg-white">

            <header class="border-b-4 border-emerald-600 p-7">

                <div class="flex items-start justify-between gap-8">

                    <div class="flex max-w-3xl items-start gap-4">

                        @if ($appSetting?->logo_url)

                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white">

                                <img
                                    src="{{ $appSetting->logo_url }}"
                                    alt="{{ $appSetting->cooperative_name }}"
                                    class="h-full w-full object-contain p-2">

                            </div>

                        @else

                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white">

                                <i data-lucide="landmark" class="h-10 w-10"></i>

                            </div>

                        @endif

                        <div>

                            <h1 class="text-2xl font-bold text-slate-900">
                                {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}
                            </h1>

                            <p class="mt-1 text-sm font-medium text-emerald-600">
                                {{ $appSetting?->tagline ?? 'Sistem Manajemen Koperasi' }}
                            </p>

                            @if ($appSetting?->registration_number)

                                <p class="mt-2 text-xs text-slate-500">

                                    Nomor Badan Hukum:
                                    {{ $appSetting->registration_number }}

                                </p>

                            @endif

                            @if ($appSetting?->address)

                                <p class="mt-2 max-w-xl text-xs leading-5 text-slate-500">
                                    {{ $appSetting->address }}
                                </p>

                            @endif

                            @if ($appSetting?->phone || $appSetting?->email)

                                <p class="mt-1 text-xs text-slate-500">

                                    {{ $appSetting?->phone }}

                                    @if ($appSetting?->phone && $appSetting?->email)
                                        ·
                                    @endif

                                    {{ $appSetting?->email }}

                                </p>

                            @endif

                        </div>

                    </div>

                    <div class="shrink-0 text-right">

                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">
                            Dokumen Laporan
                        </p>

                        <h2 class="mt-2 text-xl font-bold text-slate-900">
                            {{ $reportTitle }}
                        </h2>

                        <p class="mt-2 text-sm text-slate-500">

                            {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d F Y') }}
                            –
                            {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d F Y') }}

                        </p>

                    </div>

                </div>

            </header>

            <!-- Ringkasan -->
            <div class="grid gap-4 border-b border-slate-200 p-6 sm:grid-cols-2 lg:grid-cols-4">

                @foreach ($summaryCards as $card)

                    <article class="rounded-2xl bg-slate-50 p-4">

                        <p class="text-xs text-slate-500">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-2 font-bold text-slate-900">

                            @if ($card['format'] === 'currency')

                                Rp{{ number_format($card['value'], 0, ',', '.') }}

                            @else

                                {{ number_format($card['value'], 0, ',', '.') }}

                            @endif

                        </p>

                    </article>

                @endforeach

            </div>

            <!-- Tabel -->
            @include('reports._table')

            <!-- Tanda tangan -->
            <div class="grid grid-cols-2 gap-20 border-t border-slate-200 px-10 py-10 text-center">

                <div>

                    <p class="text-xs text-slate-500">
                        Ketua Koperasi
                    </p>

                    <div class="mt-20 border-t border-slate-300 pt-2">

                        <p class="text-sm font-semibold text-slate-800">

                            {{ $appSetting?->chairman_name ?: 'Ketua Koperasi' }}

                        </p>

                    </div>

                </div>

                <div>

                    <p class="text-xs text-slate-500">
                        Bendahara
                    </p>

                    <div class="mt-20 border-t border-slate-300 pt-2">

                        <p class="text-sm font-semibold text-slate-800">

                            {{ $appSetting?->treasurer_name ?: 'Bendahara' }}

                        </p>

                    </div>

                </div>

            </div>

            <footer class="flex items-center justify-between gap-5 border-t border-slate-200 bg-slate-50 p-6 text-xs text-slate-500">

                <p>
                    Dicetak pada {{ now()->translatedFormat('d F Y H:i') }}
                </p>

                <p>
                    Dicetak oleh {{ auth()->user()->name }}
                </p>

                <p>
                    {{ $appSetting?->short_name ?? 'e-Koperasi' }}
                </p>

            </footer>

        </section>

    </main>

</body>

</html>
