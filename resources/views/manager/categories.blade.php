@extends('layouts.staff', ['title' => 'Kategori Menu'])

@section('heading', 'Manager — Kategori Menu')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- List -->
    <div class="lg:col-span-8 bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Daftar Kategori Menu</h3>
        </div>
        <table class="w-full text-left text-xs">
            <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                <tr>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Nama Kategori</th>
                    <th class="px-4 py-3 text-center">Jumlah Produk</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#F2ECE1]">
                @foreach($categories as $cat)
                    <tr class="hover:bg-[#FAF6EF]/60">
                        <td class="px-4 py-3 font-bold text-[#6F6259]">{{ $cat->sort_order }}</td>
                        <td class="px-4 py-3 font-bold text-[#1F1A17]">{{ $cat->name }}</td>
                        <td class="px-4 py-3 text-center font-bold">{{ $cat->products_count }} Produk</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $cat->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('manager.categories.destroy', ['category' => $cat->id]) }}"
                                  onsubmit="return confirm('Hapus kategori ini?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline font-bold">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Create Form -->
    <div class="lg:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Kategori</h3>
        <form method="POST" action="{{ route('manager.categories.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Kategori *</label>
                <input type="text" name="name" required placeholder="Contoh: Manual Brew"
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Urutan Tampilan</label>
                <input type="number" name="sort_order" value="1" min="0" required
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <label class="flex items-center gap-2 font-bold cursor-pointer pt-1">
                <input type="checkbox" name="is_active" value="1" checked class="rounded text-[#3B2A20]">
                <span>Aktif di Menu Pelanggan</span>
            </label>
            <button type="submit" class="w-full min-h-[42px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Simpan Kategori
            </button>
        </form>
    </div>
</div>
@endsection
