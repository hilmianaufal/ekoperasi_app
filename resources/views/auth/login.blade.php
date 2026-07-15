<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Login |
        {{ $appSetting?->short_name ?? config('app.name', 'e-Koperasi') }}
    </title>

    @if ($appSetting?->logo_url)
        <link rel="icon" href="{{ $appSetting->logo_url }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-950">

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">

        <div class="absolute inset-0">

            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>

            <div class="absolute -bottom-40 -right-32 h-[500px] w-[500px] rounded-full bg-cyan-500/15 blur-3xl"></div>

        </div>

        <div class="relative grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-2xl lg:grid-cols-2">

            <!-- Informasi aplikasi -->
            <section class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">

                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>

                <div class="absolute -bottom-28 -left-20 h-80 w-80 rounded-full bg-white/10"></div>

                <div class="relative z-10">

                    <div class="flex items-center gap-4">

                        @if ($appSetting?->logo_url)

                            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg">

                                <img
                                    src="{{ $appSetting->logo_url }}"
                                    alt="{{ $appSetting->cooperative_name }}"
                                    class="h-full w-full object-contain p-2">

                            </div>

                        @else

                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">

                                <i data-lucide="landmark" class="h-9 w-9"></i>

                            </div>

                        @endif

                        <div>

                            <h1 class="text-2xl font-bold">
                                {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}
                            </h1>

                            <p class="mt-1 text-sm text-emerald-100">
                                {{ $appSetting?->tagline ?? 'Sistem Manajemen Koperasi' }}
                            </p>

                        </div>

                    </div>

                </div>

                <div class="relative z-10 max-w-md">

                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold backdrop-blur">
                        Koperasi Digital Terintegrasi
                    </span>

                    <h2 class="mt-6 text-4xl font-bold leading-tight">
                        Kelola koperasi lebih mudah, cepat, dan transparan.
                    </h2>

                    <p class="mt-5 text-sm leading-7 text-emerald-100">

                        Kelola data anggota, simpanan, pinjaman, angsuran,
                        kas, dan laporan melalui satu aplikasi modern.

                    </p>

                    @if ($appSetting?->address)

                        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">

                            <i data-lucide="map-pin" class="mt-0.5 h-5 w-5 shrink-0"></i>

                            <p class="text-xs leading-6 text-emerald-100">
                                {{ $appSetting->address }}
                            </p>

                        </div>

                    @endif

                </div>

                <div class="relative z-10 grid grid-cols-3 gap-4">

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">

                        <i data-lucide="users" class="h-5 w-5"></i>

                        <p class="mt-3 text-xs text-emerald-100">
                            Data anggota
                        </p>

                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">

                        <i data-lucide="wallet-cards" class="h-5 w-5"></i>

                        <p class="mt-3 text-xs text-emerald-100">
                            Simpan pinjam
                        </p>

                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">

                        <i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i>

                        <p class="mt-3 text-xs text-emerald-100">
                            Laporan
                        </p>

                    </div>

                </div>

            </section>

            <!-- Form login -->
            <section class="flex items-center bg-white px-6 py-10 sm:px-12 lg:px-16">

                <div class="mx-auto w-full max-w-md">

                    <div class="mb-8 flex items-center gap-3 lg:hidden">

                        @if ($appSetting?->logo_url)

                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white">

                                <img
                                    src="{{ $appSetting->logo_url }}"
                                    alt="{{ $appSetting->cooperative_name }}"
                                    class="h-full w-full object-contain p-1.5">

                            </div>

                        @else

                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white">

                                <i data-lucide="landmark" class="h-7 w-7"></i>

                            </div>

                        @endif

                        <div>

                            <h1 class="font-bold text-slate-900">
                                {{ $appSetting?->short_name ?? 'e-Koperasi' }}
                            </h1>

                            <p class="text-xs text-slate-500">
                                {{ $appSetting?->tagline ?? 'Sistem Manajemen Koperasi' }}
                            </p>

                        </div>

                    </div>

                    <div>

                        <p class="text-sm font-semibold text-emerald-600">
                            Selamat datang kembali
                        </p>

                        <h2 class="mt-2 text-3xl font-bold text-slate-900">
                            Masuk ke akun Anda
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Masukkan email dan password untuk mengakses aplikasi.
                        </p>

                    </div>

                    @if ($errors->any())

                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4">

                            <div class="flex gap-3">

                                <div class="mt-0.5 text-red-500">
                                    <i data-lucide="circle-alert" class="h-5 w-5"></i>
                                </div>

                                <div>

                                    <p class="text-sm font-semibold text-red-700">
                                        Login gagal
                                    </p>

                                    <ul class="mt-1 space-y-1 text-xs text-red-600">

                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach

                                    </ul>

                                </div>

                            </div>

                        </div>

                    @endif

                    <form
                        action="{{ route('login.store') }}"
                        method="POST"
                        class="mt-8 space-y-5">

                        @csrf

                        <div>

                            <label
                                for="email"
                                class="mb-2 block text-sm font-semibold text-slate-700">

                                Alamat email

                            </label>

                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">

                                    <i data-lucide="mail" class="h-5 w-5"></i>

                                </div>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    placeholder="Masukkan alamat email"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm outline-none placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            </div>

                        </div>

                        <div x-data="{ showPassword: false }">

                            <label
                                for="password"
                                class="mb-2 block text-sm font-semibold text-slate-700">

                                Password

                            </label>

                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">

                                    <i data-lucide="lock-keyhole" class="h-5 w-5"></i>

                                </div>

                                <input
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan password"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-12 text-sm outline-none placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                <button
                                    type="button"
                                    x-on:click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-emerald-600">

                                    <i
                                        x-show="!showPassword"
                                        data-lucide="eye"
                                        class="h-5 w-5">
                                    </i>

                                    <i
                                        x-show="showPassword"
                                        x-cloak
                                        data-lucide="eye-off"
                                        class="h-5 w-5">
                                    </i>

                                </button>

                            </div>

                        </div>

                        <label class="flex cursor-pointer items-center gap-3">

                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">

                            <span class="text-sm text-slate-600">
                                Ingat saya
                            </span>

                        </label>

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                            <span>Masuk ke aplikasi</span>

                            <i data-lucide="arrow-right" class="h-5 w-5"></i>

                        </button>

                    </form>

                    <div class="mt-8 text-center">

                        <p class="text-xs text-slate-400">

                            &copy; {{ date('Y') }}
                            {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}.

                        </p>

                        @if ($appSetting?->phone || $appSetting?->email)

                            <p class="mt-2 text-[10px] text-slate-400">

                                {{ $appSetting?->phone }}

                                @if ($appSetting?->phone && $appSetting?->email)
                                    ·
                                @endif

                                {{ $appSetting?->email }}

                            </p>

                        @endif

                    </div>

                </div>

            </section>

        </div>

    </main>

    @if (session('success'))

        <script>
            window.addEventListener('load', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: @json(session('success')),
                    confirmButtonText: 'Baik',
                    confirmButtonColor: '#059669',
                });
            });
        </script>

    @endif

</body>

</html>
