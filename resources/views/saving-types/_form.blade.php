@php
    $isEdit = isset($savingType);
@endphp

<form
    action="{{ $isEdit
        ? route('saving-types.update', $savingType)
        : route('saving-types.store') }}"
    method="POST">

    @csrf

    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
            <p class="text-sm font-semibold text-red-700">
                Data belum dapat disimpan
            </p>

            <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-red-600">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-6">
            <h3 class="font-bold text-slate-900">
                Informasi Jenis Simpanan
            </h3>

            <p class="mt-1 text-xs text-slate-500">
                Atur nama, kode, nominal, dan ketentuan penarikan.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">

            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">
                    Nama simpanan
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $savingType->name ?? '') }}"
                    required
                    placeholder="Contoh: Simpanan Wajib"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
            </div>

            <div>
                <label for="code" class="mb-2 block text-sm font-semibold text-slate-700">
                    Kode simpanan
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="code"
                    id="code"
                    value="{{ old('code', $savingType->code ?? '') }}"
                    required
                    placeholder="Contoh: WAJIB"
                    class="w-full uppercase rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
            </div>

            <div class="md:col-span-2">
                <label for="default_amount" class="mb-2 block text-sm font-semibold text-slate-700">
                    Nominal default
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                        Rp
                    </span>

                    <input
                        type="number"
                        name="default_amount"
                        id="default_amount"
                        value="{{ old('default_amount', isset($savingType) ? (float) $savingType->default_amount : 0) }}"
                        min="0"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-12 pr-4 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">
                    Keterangan
                </label>

                <textarea
                    name="description"
                    id="description"
                    rows="4"
                    placeholder="Masukkan keterangan jenis simpanan"
                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">{{ old('description', $savingType->description ?? '') }}</textarea>
            </div>

            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                <input
                    type="checkbox"
                    name="is_withdrawable"
                    value="1"
                    @checked(old('is_withdrawable', $savingType->is_withdrawable ?? false))
                    class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">

                <span>
                    <span class="block text-sm font-semibold text-slate-700">
                        Dapat ditarik
                    </span>

                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Anggota dapat melakukan penarikan dari jenis simpanan ini.
                    </span>
                </span>
            </label>

            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $savingType->is_active ?? true))
                    class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">

                <span>
                    <span class="block text-sm font-semibold text-slate-700">
                        Jenis simpanan aktif
                    </span>

                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Jenis simpanan dapat digunakan saat membuat transaksi.
                    </span>
                </span>
            </label>

        </div>

    </section>

    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

        <a
            href="{{ route('saving-types.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50">

            <i data-lucide="arrow-left" class="h-5 w-5"></i>
            Kembali
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

            <i data-lucide="save" class="h-5 w-5"></i>

            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Jenis Simpanan' }}
        </button>

    </div>

</form>
