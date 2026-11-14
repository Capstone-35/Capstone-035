@extends('layouts.staff', ['title' => 'Manajemen Produk'])

@section('heading', 'Manager — Katalog Produk & Menu')

@section('content')
<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('manager.products.index') }}"
               class="px-3 py-1.5 rounded-xl font-bold {{ empty($categoryId) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                Semua Kategori
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('manager.products.index', ['category_id' => $cat->id]) }}"
                   class="px-3 py-1.5 rounded-xl font-bold {{ (string)$categoryId === (string)$cat->id ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259]' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('manager.products.index') }}" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama menu..."
                       class="px-3.5 py-1.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-[#3B2A20] text-white font-bold">
                    Cari
                </button>
            </form>
            <a href="{{ route('manager.products.create') }}" class="px-4 py-2 rounded-xl bg-[#A9552F] text-white font-extrabold hover:bg-[#8E4423]">
                + Tambah Menu
            </a>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama Menu</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-center">Ketersediaan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($products as $prod)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-sm text-[#1F1A17]">{{ $prod->name }}</div>
                                <div class="text-[11px] text-[#6F6259] line-clamp-1">{{ $prod->description }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-[#6F6259]">{{ $prod->category?->name }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums">
                                {{ $prod->formattedPrice() }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <form method="POST" action="{{ route('manager.products.toggle', ['product' => $prod->id]) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-extrabold {{ $prod->is_available ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $prod->is_available ? 'Tersedia' : 'Habis' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $prod->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                    {{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('manager.products.edit', ['product' => $prod->id]) }}" class="px-3 py-1 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                        Ubah
                                    </a>
                                    <form method="POST" action="{{ route('manager.products.destroy', ['product' => $prod->id]) }}"
                                          onsubmit="return confirm('Arsipkan produk {{ $prod->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-red-600 hover:bg-red-50 font-bold">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-[#6F6259]">
                                Tidak ada menu yang cocok dengan pencarian ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#E6DED2]">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
