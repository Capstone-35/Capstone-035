@extends('layouts.staff', ['title' => 'Manajemen Produk'])

@section('heading', 'Manager — Katalog Produk & Menu')

@section('content')
<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <a href="{{ route('manager.products.index', array_filter(['q' => $search])) }}"
               class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap {{ empty($categoryId) ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259] hover:bg-[#F2ECE1]' }}">
                Semua Kategori
            </a>
            @foreach($categories as $cat)
                <a href="{{ route('manager.products.index', array_filter(['category_id' => $cat->id, 'q' => $search])) }}"
                   class="px-3 py-1.5 rounded-xl font-bold whitespace-nowrap {{ (string)$categoryId === (string)$cat->id ? 'bg-[#3B2A20] text-[#FAF6EF]' : 'bg-[#FAF6EF] text-[#6F6259] hover:bg-[#F2ECE1]' }}">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            <form method="GET" action="{{ route('manager.products.index') }}" class="flex items-center gap-2 flex-1 sm:flex-initial min-w-0">
                @if(!empty($categoryId))
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama menu..."
                       class="w-full sm:w-48 min-w-0 px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-[#3B2A20] text-white font-bold shrink-0">
                    Cari
                </button>
            </form>
            <a href="{{ route('manager.products.create') }}" class="px-4 py-2 rounded-xl bg-[#A9552F] text-white font-extrabold hover:bg-[#8E4423] whitespace-nowrap shrink-0">
                + Tambah Menu
            </a>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[740px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama Menu</th>
                        <th class="px-4 py-3 whitespace-nowrap">Kategori</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Harga</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Ketersediaan</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($products as $prod)
                        <tr class="hover:bg-[#FAF6EF]/60 transition">
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-sm text-[#1F1A17]">{{ $prod->name }}</div>
                                <div class="text-[11px] text-[#6F6259] line-clamp-1">{{ $prod->description }}</div>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-[#6F6259] whitespace-nowrap">{{ $prod->category?->name }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-[#3B2A20] tabular-nums whitespace-nowrap">
                                {{ $prod->formattedPrice() }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('manager.products.toggle', ['product' => $prod->id]) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-extrabold {{ $prod->is_available ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $prod->is_available ? 'Tersedia' : 'Habis' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $prod->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                    {{ $prod->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('manager.products.edit', ['product' => $prod->id]) }}" class="px-3 py-1.5 rounded-lg border border-[#D6CBBC] bg-white font-bold hover:bg-[#F2ECE1]">
                                        Ubah
                                    </a>
                                    <form method="POST" action="{{ route('manager.products.destroy', ['product' => $prod->id]) }}"
                                          data-confirm="Arsipkan produk {{ $prod->name }}?"
                                          onsubmit="return confirm(this.dataset.confirm);">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 font-bold">
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
