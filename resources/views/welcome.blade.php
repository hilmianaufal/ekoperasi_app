<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>e-Koperasi</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100">

    <div class="min-h-screen lg:flex">

        <!-- Sidebar -->
        <aside class="w-full bg-slate-950 px-6 py-6 text-white lg:min-h-screen lg:w-72">

            <div class="flex items-center gap-4">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500 text-lg font-bold shadow-lg shadow-emerald-950/40">
                    EK
                </div>

                <div>
                    <h1 class="text-lg font-bold">
                        e-Koperasi
                    </h1>

                    <p class="text-xs text-slate-400">
                        Sistem Manajemen Koperasi
                    </p>
                </div>
            </div>

            <nav class="mt-10 space-y-2">

                <a href="#"
                    class="flex items-center rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-950/30">
                    Dashboard
                </a>

                <a href="#"
                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                    Data Anggota
                </a>

                <a href="#"
                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                    Simpanan
                </a>

                <a href="#"
                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                    Pinjaman
                </a>

                <a href="#"
                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                    Angsuran
                </a>

                <a href="#"
                    class="flex items-center rounded-xl px-4 py-3 text-sm font-medium text-slate-400 hover:bg-white/10 hover:text-white">
                    Laporan
                </a>

            </nav>

        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-5 md:p-8">

            <header class="flex flex-col justify-between gap-4 md:flex-row md:items-center">

                <div>
                    <p class="text-sm font-medium text-emerald-600">
                        Selamat datang
                    </p>

                    <h2 class="mt-1 text-2xl font-bold text-slate-900 md:text-3xl">
                        Dashboard e-Koperasi
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        Kelola seluruh kegiatan koperasi dalam satu aplikasi.
                    </p>
                </div>

                <button type="button"
                    onclick="showWelcomeAlert()"
                    class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">
                    Tes SweetAlert
                </button>

            </header>

            <!-- Cards -->
            <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Anggota
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        0
                    </h3>

                    <p class="mt-2 text-xs text-emerald-600">
                        Anggota terdaftar
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Simpanan
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        Rp0
                    </h3>

                    <p class="mt-2 text-xs text-emerald-600">
                        Seluruh simpanan anggota
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Pinjaman Aktif
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        0
                    </h3>

                    <p class="mt-2 text-xs text-amber-600">
                        Pinjaman sedang berjalan
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Saldo Kas
                    </p>

                    <h3 class="mt-3 text-3xl font-bold text-slate-900">
                        Rp0
                    </h3>

                    <p class="mt-2 text-xs text-blue-600">
                        Saldo kas koperasi
                    </p>
                </div>

            </section>

            <!-- Welcome Section -->
            <section
                class="relative mt-8 overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-800 p-7 text-white shadow-xl shadow-emerald-200">

                <div class="relative z-10 max-w-2xl">

                    <span
                        class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                        Aplikasi Koperasi Digital
                    </span>

                    <h3 class="mt-4 text-2xl font-bold md:text-3xl">
                        Pengelolaan koperasi menjadi lebih cepat dan terorganisir.
                    </h3>

                    <p class="mt-3 text-sm leading-7 text-emerald-50">
                        Kelola anggota, simpanan, pinjaman, angsuran, kas,
                        dan laporan koperasi melalui satu sistem terintegrasi.
                    </p>

                </div>

                <div
                    class="absolute -bottom-20 -right-20 h-64 w-64 rounded-full bg-white/10">
                </div>

                <div
                    class="absolute -right-5 -top-20 h-48 w-48 rounded-full bg-white/10">
                </div>

            </section>

        </main>

    </div>

</body>

</html>
