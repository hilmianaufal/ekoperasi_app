@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Aplikasi')
@section('page-description', 'Kelola identitas dan konfigurasi e-Koperasi')

@section('content')

    <div
        x-data="{
            activeTab: 'identity',
            logoPreview: @js($setting->logo_url),
        }"
        class="mx-auto max-w-6xl">

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">

                <div class="flex gap-3">

                    <div class="mt-0.5 text-red-500">
                        <i data-lucide="circle-alert" class="h-5 w-5"></i>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-red-700">
                            Pengaturan belum dapat disimpan
                        </p>

                        <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>

                </div>

            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[260px_1fr]">

            <!-- Navigasi pengaturan -->
            <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">

                <div class="border-b border-slate-100 px-3 pb-4">

                    <h3 class="font-bold text-slate-900">
                        Menu Pengaturan
                    </h3>

                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        Pilih bagian yang ingin diperbarui.
                    </p>

                </div>

                <nav class="mt-4 space-y-2">

                    <button
                        type="button"
                        x-on:click="activeTab = 'identity'"
                        x-bind:class="activeTab === 'identity'
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold">

                        <i data-lucide="landmark" class="h-5 w-5"></i>
                        Identitas Koperasi
                    </button>

                    <button
                        type="button"
                        x-on:click="activeTab = 'management'"
                        x-bind:class="activeTab === 'management'
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold">

                        <i data-lucide="users-round" class="h-5 w-5"></i>
                        Pengurus
                    </button>

                    <button
                        type="button"
                        x-on:click="activeTab = 'loan'"
                        x-bind:class="activeTab === 'loan'
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold">

                        <i data-lucide="hand-coins" class="h-5 w-5"></i>
                        Pengaturan Pinjaman
                    </button>

                    <button
                        type="button"
                        x-on:click="activeTab = 'system'"
                        x-bind:class="activeTab === 'system'
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold">

                        <i data-lucide="settings-2" class="h-5 w-5"></i>
                        Sistem dan Kuitansi
                    </button>

                </nav>

            </aside>

            <form
                action="{{ route('settings.update') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <!-- Identitas koperasi -->
                <section
                    x-show="activeTab === 'identity'"
                    x-cloak
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-7">

                    <div class="mb-7 flex items-center gap-3">

                        <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-600">
                            <i data-lucide="landmark" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Identitas Koperasi
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Informasi ini akan tampil pada aplikasi, laporan, dan kuitansi.
                            </p>
                        </div>

                    </div>

                    <div class="grid gap-6 lg:grid-cols-[1fr_240px]">

                        <div class="grid gap-5 md:grid-cols-2">

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nama koperasi
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="cooperative_name"
                                    value="{{ old('cooperative_name', $setting->cooperative_name) }}"
                                    required
                                    placeholder="Masukkan nama koperasi"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nama singkat
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="short_name"
                                    value="{{ old('short_name', $setting->short_name) }}"
                                    required
                                    placeholder="Contoh: e-Koperasi"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nomor badan hukum
                                </label>

                                <input
                                    type="text"
                                    name="registration_number"
                                    value="{{ old('registration_number', $setting->registration_number) }}"
                                    placeholder="Nomor badan hukum koperasi"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Tagline
                                </label>

                                <input
                                    type="text"
                                    name="tagline"
                                    value="{{ old('tagline', $setting->tagline) }}"
                                    placeholder="Contoh: Bersama Membangun Kesejahteraan"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Nomor telepon
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $setting->phone) }}"
                                    placeholder="Contoh: 081234567890"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Alamat email
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $setting->email) }}"
                                    placeholder="koperasi@email.com"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-semibold text-slate-700">
                                    Alamat koperasi
                                </label>

                                <textarea
                                    name="address"
                                    rows="4"
                                    placeholder="Masukkan alamat lengkap koperasi"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('address', $setting->address) }}</textarea>
                            </div>

                        </div>

                        <!-- Logo -->
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Logo koperasi
                            </label>

                            <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 p-5">

                                <div class="flex h-44 items-center justify-center overflow-hidden rounded-2xl bg-white">

                                    <img
                                        x-show="logoPreview"
                                        x-bind:src="logoPreview"
                                        alt="Preview logo"
                                        class="h-full w-full object-contain p-4">

                                    <div
                                        x-show="!logoPreview"
                                        class="flex flex-col items-center text-slate-400">

                                        <i data-lucide="image-up" class="h-10 w-10"></i>

                                        <p class="mt-3 text-xs">
                                            Belum ada logo
                                        </p>

                                    </div>

                                </div>

                                <label class="mt-4 flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm hover:bg-emerald-50 hover:text-emerald-700">

                                    <i data-lucide="upload" class="h-5 w-5"></i>
                                    Pilih Logo

                                    <input
                                        type="file"
                                        name="logo"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="hidden"
                                        x-on:change="
                                            const file = $event.target.files[0];

                                            if (file) {
                                                logoPreview = URL.createObjectURL(file);
                                            }
                                        ">

                                </label>

                                <p class="mt-3 text-center text-[10px] leading-5 text-slate-400">
                                    Format JPG, PNG, atau WebP. Maksimal 2 MB.
                                </p>

                            </div>

                        </div>

                    </div>

                </section>

                <!-- Pengurus -->
                <section
                    x-show="activeTab === 'management'"
                    x-cloak
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-7">

                    <div class="mb-7 flex items-center gap-3">

                        <div class="rounded-2xl bg-blue-100 p-3 text-blue-600">
                            <i data-lucide="users-round" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Data Pengurus
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Nama pengurus dapat ditampilkan pada laporan dan dokumen.
                            </p>
                        </div>

                    </div>

                    <div class="grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Nama ketua koperasi
                            </label>

                            <input
                                type="text"
                                name="chairman_name"
                                value="{{ old('chairman_name', $setting->chairman_name) }}"
                                placeholder="Masukkan nama ketua"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Nama bendahara
                            </label>

                            <input
                                type="text"
                                name="treasurer_name"
                                value="{{ old('treasurer_name', $setting->treasurer_name) }}"
                                placeholder="Masukkan nama bendahara"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        </div>

                    </div>

                </section>

                <!-- Pengaturan pinjaman -->
                <section
                    x-show="activeTab === 'loan'"
                    x-cloak
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-7">

                    <div class="mb-7 flex items-center gap-3">

                        <div class="rounded-2xl bg-amber-100 p-3 text-amber-600">
                            <i data-lucide="hand-coins" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Ketentuan Pinjaman
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Nilai berikut akan digunakan sebagai nilai awal pada form pinjaman.
                            </p>
                        </div>

                    </div>

                    <div class="grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Bunga default per bulan
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    name="default_interest_rate"
                                    value="{{ old('default_interest_rate', (float) $setting->default_interest_rate) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-12 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 font-semibold text-slate-500">
                                    %
                                </span>

                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Tenor default
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">

                                <input
                                    type="number"
                                    name="default_tenor_months"
                                    value="{{ old('default_tenor_months', $setting->default_tenor_months) }}"
                                    min="1"
                                    max="120"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-20 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-500">
                                    Bulan
                                </span>

                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Minimal pinjaman
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">

                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="minimum_loan_amount"
                                    value="{{ old('minimum_loan_amount', (float) $setting->minimum_loan_amount) }}"
                                    min="0"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Maksimal pinjaman
                            </label>

                            <div class="relative">

                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    name="maximum_loan_amount"
                                    value="{{ old('maximum_loan_amount', $setting->maximum_loan_amount ? (float) $setting->maximum_loan_amount : '') }}"
                                    min="0"
                                    placeholder="Kosongkan jika tidak dibatasi"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            </div>
                        </div>

                    </div>

                </section>

                <!-- Sistem -->
                <section
                    x-show="activeTab === 'system'"
                    x-cloak
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-7">

                    <div class="mb-7 flex items-center gap-3">

                        <div class="rounded-2xl bg-violet-100 p-3 text-violet-600">
                            <i data-lucide="settings-2" class="h-6 w-6"></i>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900">
                                Sistem dan Kuitansi
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Atur zona waktu dan catatan dokumen pembayaran.
                            </p>
                        </div>

                    </div>

                    <div class="grid gap-5">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Zona waktu
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="timezone"
                                required
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                                <option
                                    value="Asia/Jakarta"
                                    @selected(old('timezone', $setting->timezone) === 'Asia/Jakarta')>

                                    Waktu Indonesia Barat
                                </option>

                                <option
                                    value="Asia/Makassar"
                                    @selected(old('timezone', $setting->timezone) === 'Asia/Makassar')>

                                    Waktu Indonesia Tengah
                                </option>

                                <option
                                    value="Asia/Jayapura"
                                    @selected(old('timezone', $setting->timezone) === 'Asia/Jayapura')>

                                    Waktu Indonesia Timur
                                </option>

                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Catatan bawah kuitansi
                            </label>

                            <textarea
                                name="receipt_footer"
                                rows="5"
                                placeholder="Contoh: Terima kasih telah melakukan pembayaran tepat waktu."
                                class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('receipt_footer', $setting->receipt_footer) }}</textarea>
                        </div>

                    </div>

                </section>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

                        <i data-lucide="arrow-left" class="h-5 w-5"></i>
                        Kembali
                    </a>

                    <button
                        type="button"
                        onclick="confirmSaveSettings()"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                        <i data-lucide="save" class="h-5 w-5"></i>
                        Simpan Pengaturan
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        function confirmSaveSettings() {
            const form = document.querySelector(
                'form[action="{{ route('settings.update') }}"]'
            );

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Simpan pengaturan?',
                text: 'Perubahan akan langsung diterapkan pada aplikasi.',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Periksa kembali',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    </script>
@endpush
