<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Dashboard')
        |
        {{ $appSetting?->short_name ?? config('app.name', 'e-Koperasi') }}
    </title>

    @if ($appSetting?->logo_url)
        <link rel="icon" href="{{ $appSetting->logo_url }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body x-data="{ sidebarOpen: false }" class="min-h-screen bg-slate-100 text-slate-800">

    <!-- Overlay mobile -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden">
    </div>

    <!-- Sidebar -->
    <aside x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-white transition-transform duration-300 lg:translate-x-0">

        <!-- Brand -->
        <div class="border-b border-white/10 px-6 py-6">

            <div class="flex items-center gap-3">

                @if ($appSetting?->logo_url)
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-lg shadow-emerald-950/40">

                        <img src="{{ $appSetting->logo_url }}" alt="{{ $appSetting->cooperative_name }}"
                            class="h-full w-full object-contain p-1.5">

                    </div>
                @else
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 shadow-lg shadow-emerald-950/40">

                        <i data-lucide="landmark" class="h-7 w-7"></i>

                    </div>
                @endif

                <div class="min-w-0 flex-1">

                    <h1 class="truncate text-base font-bold">
                        {{ $appSetting?->short_name ?? 'e-Koperasi' }}
                    </h1>

                    <p class="truncate text-xs text-slate-400">
                        {{ $appSetting?->tagline ?? 'Sistem Manajemen Koperasi' }}
                    </p>

                </div>

                <button type="button" x-on:click="sidebarOpen = false"
                    class="rounded-xl p-2 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden">

                    <i data-lucide="x" class="h-5 w-5"></i>

                </button>

            </div>

        </div>

        <!-- Navigasi -->
        <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-6">

            <p class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Menu utama
            </p>

            <a href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                <span>Dashboard</span>

            </a>

            <a href="{{ route('members.index') }}"
                class="{{ request()->routeIs('members.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="users" class="h-5 w-5"></i>
                <span>Data Anggota</span>

            </a>

            <a href="{{ route('savings.index') }}"
                class="{{ request()->routeIs('savings.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="wallet-cards" class="h-5 w-5"></i>
                <span>Simpanan</span>

            </a>

            <a href="{{ route('saving-types.index') }}"
                class="{{ request()->routeIs('saving-types.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="list-settings" class="h-5 w-5"></i>
                <span>Jenis Simpanan</span>

            </a>

            <a href="{{ route('loans.index') }}"
                class="{{ request()->routeIs('loans.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="hand-coins" class="h-5 w-5"></i>
                <span>Pinjaman</span>

            </a>

            <a href="{{ route('installments.index') }}"
                class="{{ request()->routeIs(['installments.*', 'installment-payments.*'])
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="calendar-check-2" class="h-5 w-5"></i>
                <span>Angsuran</span>

            </a>

            <a href="{{ route('cash-transactions.index') }}"
                class="{{ request()->routeIs('cash-transactions.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="arrow-left-right" class="h-5 w-5"></i>
                <span>Kas Koperasi</span>

            </a>

            <p class="mb-3 mt-7 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                Laporan dan sistem
            </p>

            <a href="{{ route('reports.index') }}"
                class="{{ request()->routeIs('reports.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="file-chart-column" class="h-5 w-5"></i>
                <span>Laporan</span>

            </a>
            <a href="{{ route('data-imports.index') }}"
                class="{{ request()->routeIs('data-imports.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
        flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="file-up" class="h-5 w-5"></i>
                <span>Import Data Awal</span>
            </a>
            <a href="{{ route('settings.edit') }}"
                class="{{ request()->routeIs('settings.*')
                    ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-950/30'
                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}
                    flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium">

                <i data-lucide="settings" class="h-5 w-5"></i>
                <span>Pengaturan</span>

            </a>

        </nav>

        <!-- Profil pengguna -->
        <div class="border-t border-white/10 p-4">

            <div class="rounded-2xl bg-white/5 p-3">

                <div class="flex items-center gap-3">

                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 font-bold">

                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}

                    </div>

                    <div class="min-w-0 flex-1">

                        <p class="truncate text-sm font-semibold">
                            {{ auth()->user()->name }}
                        </p>

                        <p class="truncate text-xs capitalize text-slate-400">
                            {{ auth()->user()->role }}
                        </p>

                    </div>

                    <form action="{{ route('logout') }}" method="POST" id="logout-form">

                        @csrf

                        <button type="button" onclick="confirmLogout()"
                            class="rounded-xl p-2 text-slate-400 hover:bg-red-500/15 hover:text-red-400" title="Keluar">

                            <i data-lucide="log-out" class="h-5 w-5"></i>

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </aside>

    <!-- Konten utama -->
    <div class="min-h-screen lg:pl-72">

        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">

            <div class="flex h-20 items-center justify-between gap-4 px-5 md:px-8">

                <div class="flex min-w-0 items-center gap-3">

                    <button type="button" x-on:click="sidebarOpen = true"
                        class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 hover:bg-slate-50 lg:hidden">

                        <i data-lucide="menu" class="h-5 w-5"></i>

                    </button>

                    <div class="min-w-0">

                        <h2 class="truncate text-lg font-bold text-slate-900">
                            @yield('page-title', 'Dashboard')
                        </h2>

                        <p class="hidden truncate text-xs text-slate-500 sm:block">
                            @yield('page-description', $appSetting?->tagline ?? 'Sistem Manajemen Koperasi')
                        </p>

                    </div>

                </div>

                <div class="flex items-center gap-2">

                    <button type="button"
                        class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-500 hover:bg-slate-50 hover:text-emerald-600">

                        <i data-lucide="bell" class="h-5 w-5"></i>

                        <span
                            class="absolute right-2 top-2 h-2 w-2 rounded-full border-2 border-white bg-red-500"></span>

                    </button>

                    <div
                        class="hidden items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 sm:flex">

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-bold text-emerald-700">

                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}

                        </div>

                        <div class="max-w-36">

                            <p class="truncate text-xs font-semibold text-slate-800">
                                {{ auth()->user()->name }}
                            </p>

                            <p class="truncate text-[10px] capitalize text-slate-500">
                                {{ auth()->user()->role }}
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </header>

        <main class="p-5 md:p-8">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white px-5 py-5 text-center text-xs text-slate-400 md:px-8">

            &copy; {{ date('Y') }}
            {{ $appSetting?->cooperative_name ?? 'e-Koperasi' }}.
            Semua hak dilindungi.

        </footer>

    </div>

    @if (session('success'))
        <script>
            window.addEventListener('load', function() {
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

    @if (session('error'))
        <script>
            window.addEventListener('load', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: @json(session('error')),
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#dc2626',
                });
            });
        </script>
    @endif

    <script>
        function confirmLogout() {
            Swal.fire({
                icon: 'question',
                title: 'Keluar dari aplikasi?',
                text: 'Sesi login Anda akan diakhiri.',
                showCancelButton: true,
                confirmButtonText: 'Ya, keluar',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        }
    </script>

    @stack('scripts')

</body>

</html>
