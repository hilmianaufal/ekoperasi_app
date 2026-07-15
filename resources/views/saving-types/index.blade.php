@extends('layouts.app')

@section('title', 'Jenis Simpanan')
@section('page-title', 'Jenis Simpanan')
@section('page-description', 'Kelola kategori dan ketentuan simpanan')

@section('content')

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">

            <div>
                <h3 class="font-bold text-slate-900">
                    Daftar Jenis Simpanan
                </h3>

                <p class="mt-1 text-xs text-slate-500">
                    Atur simpanan pokok, wajib, sukarela, dan jenis lainnya.
                </p>
            </div>

            <a
                href="{{ route('saving-types.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-200 hover:bg-emerald-700">

                <i data-lucide="plus" class="h-5 w-5"></i>
                Tambah Jenis
            </a>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4">Jenis Simpanan</th>
                        <th class="px-6 py-4">Nominal Default</th>
                        <th class="px-6 py-4">Penarikan</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Transaksi</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse ($savingTypes as $savingType)

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $savingType->name }}
                                </p>

                                <p class="mt-1 text-xs font-medium text-emerald-600">
                                    {{ $savingType->code }}
                                </p>

                                @if ($savingType->description)
                                    <p class="mt-2 max-w-sm text-xs leading-5 text-slate-500">
                                        {{ $savingType->description }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                                Rp{{ number_format($savingType->default_amount, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4">
                                @if ($savingType->is_withdrawable)
                                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                        Dapat ditarik
                                    </span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                        Tidak dapat ditarik
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                @if ($savingType->is_active)
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                                {{ number_format($savingType->transactions_count, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4">

                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route('saving-types.edit', $savingType) }}"
                                        class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-600"
                                        title="Edit">

                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>

                                    <form
                                        action="{{ route('saving-types.destroy', $savingType) }}"
                                        method="POST"
                                        id="delete-saving-type-{{ $savingType->id }}">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="button"
                                            onclick="confirmDeleteSavingType(
                                                {{ $savingType->id }},
                                                @js($savingType->name)
                                            )"
                                            class="rounded-xl border border-slate-200 p-2.5 text-slate-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600"
                                            title="Hapus">

                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">

                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="wallet-cards" class="h-9 w-9"></i>
                                </div>

                                <h4 class="mt-5 font-semibold text-slate-700">
                                    Jenis simpanan belum tersedia
                                </h4>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($savingTypes->hasPages())
            <div class="border-t border-slate-200 px-6 py-5">
                {{ $savingTypes->links() }}
            </div>
        @endif

    </section>

@endsection

@push('scripts')
    <script>
        function confirmDeleteSavingType(id, name) {
            Swal.fire({
                icon: 'warning',
                title: 'Hapus jenis simpanan?',
                html: `Jenis simpanan <strong>${name}</strong> akan dihapus.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document
                        .getElementById(`delete-saving-type-${id}`)
                        .submit();
                }
            });
        }
    </script>
@endpush
