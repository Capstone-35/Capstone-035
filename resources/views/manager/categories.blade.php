@extends('layouts.staff', ['title' => 'Kategori Menu'])

@section('heading', 'Manager — Kategori Menu')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
    <!-- List -->
    <div class="xl:col-span-8 bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Daftar Kategori Menu</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[540px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Urutan</th>
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Jumlah Produk</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3 font-bold text-[#6F6259] tabular-nums whitespace-nowrap">{{ $cat->sort_order }}</td>
                            <td class="px-4 py-3 font-bold text-[#1F1A17]">{{ $cat->name }}</td>
                            <td class="px-4 py-3 text-center font-bold tabular-nums whitespace-nowrap">{{ $cat->products_count }} Produk</td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $cat->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('manager.categories.destroy', ['category' => $cat->id]) }}"
                                      data-confirm="Hapus kategori {{ $cat->name }}?"
                                      onsubmit="return confirm(this.dataset.confirm);" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-red-600 hover:bg-red-50 font-bold">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[#6F6259]">
                                Belum ada kategori menu yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Form -->
    <div class="xl:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Kategori</h3>
        <form method="POST" action="{{ route('manager.categories.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Kategori *</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Contoh: Manual Brew"
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Urutan Tampilan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 1) }}" min="0" max="999" required
                       class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] tabular-nums">
            </div>
            <div class="pt-1">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 font-bold cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded text-[#3B2A20]">
                    <span>Aktif di Menu Pelanggan</span>
                </label>
            </div>
            <button type="submit" class="w-full min-h-[44px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Simpan Kategori
            </button>
        </form>
    </div>
</div>
@endsection
