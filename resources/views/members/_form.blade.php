@php
    $isEdit = isset($member);
@endphp

<form
    action="{{ $isEdit ? route('members.update', $member) : route('members.store') }}"
    method="POST"
    enctype="multipart/form-data">

    @csrf

    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
            <div class="flex gap-3">
                <div class="mt-0.5 text-red-500">
                    <i data-lucide="circle-alert" class="h-5 w-5"></i>
                </div>

                <div>
                    <p class="text-sm font-semibold text-red-700">
                        Data belum dapat disimpan
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

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="space-y-6 lg:col-span-2">

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h3 class="font-bold text-slate-900">
                        Informasi Pribadi
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Masukkan identitas lengkap anggota koperasi.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div class="md:col-span-2">
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">
                            Nama lengkap
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $member->name ?? '') }}"
                            required
                            placeholder="Masukkan nama lengkap"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div>
                        <label for="gender" class="mb-2 block text-sm font-semibold text-slate-700">
                            Jenis kelamin
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="gender"
                            id="gender"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            <option value="">Pilih jenis kelamin</option>

                            <option
                                value="male"
                                @selected(old('gender', $member->gender ?? '') === 'male')>
                                Laki-laki
                            </option>

                            <option
                                value="female"
                                @selected(old('gender', $member->gender ?? '') === 'female')>
                                Perempuan
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="phone" class="mb-2 block text-sm font-semibold text-slate-700">
                            Nomor telepon
                        </label>

                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone', $member->phone ?? '') }}"
                            placeholder="Contoh: 081234567890"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div>
                        <label for="place_of_birth" class="mb-2 block text-sm font-semibold text-slate-700">
                            Tempat lahir
                        </label>

                        <input
                            type="text"
                            name="place_of_birth"
                            id="place_of_birth"
                            value="{{ old('place_of_birth', $member->place_of_birth ?? '') }}"
                            placeholder="Contoh: Cirebon"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div>
                        <label for="date_of_birth" class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal lahir
                        </label>

                        <input
                            type="date"
                            name="date_of_birth"
                            id="date_of_birth"
                            value="{{ old('date_of_birth', isset($member) && $member->date_of_birth ? $member->date_of_birth->format('Y-m-d') : '') }}"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                            Alamat email
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $member->email ?? '') }}"
                            placeholder="anggota@email.com"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-semibold text-slate-700">
                            Alamat lengkap
                        </label>

                        <textarea
                            name="address"
                            id="address"
                            rows="4"
                            placeholder="Masukkan alamat lengkap anggota"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('address', $member->address ?? '') }}</textarea>
                    </div>

                </div>

            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-6">
                    <h3 class="font-bold text-slate-900">
                        Informasi Keanggotaan
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Tentukan tanggal bergabung dan status anggota.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">

                    <div>
                        <label for="join_date" class="mb-2 block text-sm font-semibold text-slate-700">
                            Tanggal bergabung
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="date"
                            name="join_date"
                            id="join_date"
                            value="{{ old('join_date', isset($member) && $member->join_date ? $member->join_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                    </div>

                    <div>
                        <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">
                            Status anggota
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="status"
                            id="status"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">

                            <option
                                value="active"
                                @selected(old('status', $member->status ?? 'active') === 'active')>
                                Aktif
                            </option>

                            <option
                                value="inactive"
                                @selected(old('status', $member->status ?? 'active') === 'inactive')>
                                Tidak Aktif
                            </option>
                        </select>
                    </div>

                </div>

            </section>

        </div>

        <div>

            <section
                x-data="{ preview: '{{ isset($member) && $member->photo ? asset('storage/' . $member->photo) : '' }}' }"
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

                <div>
                    <h3 class="font-bold text-slate-900">
                        Foto Anggota
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Format JPG, PNG, atau WebP maksimal 2 MB.
                    </p>
                </div>

                <div class="mt-6">

                    <div class="mx-auto flex h-44 w-44 items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50">

                        <img
                            x-show="preview"
                            x-bind:src="preview"
                            alt="Preview foto"
                            class="h-full w-full object-cover">

                        <div
                            x-show="!preview"
                            class="flex flex-col items-center text-slate-400">

                            <i data-lucide="image-up" class="h-10 w-10"></i>

                            <span class="mt-3 text-xs">
                                Belum ada foto
                            </span>
                        </div>

                    </div>

                    <label
                        for="photo"
                        class="mt-5 flex cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">

                        <i data-lucide="upload" class="h-5 w-5"></i>
                        Pilih foto
                    </label>

                    <input
                        type="file"
                        name="photo"
                        id="photo"
                        accept="image/png,image/jpeg,image/webp"
                        class="hidden"
                        x-on:change="
                            const file = $event.target.files[0];

                            if (file) {
                                preview = URL.createObjectURL(file);
                            }
                        ">

                </div>

            </section>

        </div>

    </div>

    <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

        <a
            href="{{ route('members.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

            <i data-lucide="{{ $isEdit ? 'save' : 'user-plus' }}" class="h-5 w-5"></i>

            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Anggota' }}
        </button>

    </div>

</form>
