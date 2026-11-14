@extends('layouts.staff', ['title' => ($isEdit ? 'Ubah' : 'Tambah') . ' Menu'])

@section('heading', 'Manager — ' . ($isEdit ? 'Ubah Menu Produk' : 'Tambah Menu Baru'))

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-[#E6DED2] p-6 shadow-sm space-y-5">
    <div class="flex items-center justify-between border-b border-[#F2ECE1] pb-3">
        <a href="{{ route('manager.products.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Daftar Produk
        </a>
        <h2 class="text-base font-extrabold text-[#1F1A17]">{{ $isEdit ? 'Ubah Menu' : 'Tambah Menu' }}</h2>
    </div>

    <form method="POST" action="{{ $isEdit ? route('manager.products.update', ['product' => $product->id]) : route('manager.products.store') }}" class="space-y-4 text-xs">
        @csrf
        @if($isEdit)
            @method('PATCH')
        @endif

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Kategori Menu *</label>
            <select name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm">
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ old('category_id', $product->category_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Menu *</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="80"
                   placeholder="Contoh: Kopi Susu Gula Aren 774"
                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm">
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Deskripsi Singkat Menu</label>
            <textarea name="description" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]"
                      placeholder="Espresso ganda dengan susu segar dan sirup gula aren pilihan...">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Harga Satuan (Rupiah) *</label>
                <input type="number" name="price" min="1000" step="500" value="{{ old('price', $product->price) }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm tabular-nums">
            </div>

            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Path Gambar (Opsional)</label>
                <input type="text" name="image_path" value="{{ old('image_path', $product->image_path) }}"
                       placeholder="/images/products/kopi-susu.svg"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <label class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer font-bold">
                <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} class="rounded text-[#1F7A4C]">
                <span>Tersedia untuk Dipesan (Stok Ada)</span>
            </label>

            <label class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer font-bold">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }} class="rounded text-[#3B2A20]">
                <span>Aktif di Katalog Menu</span>
            </label>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] flex justify-end gap-2">
            <a href="{{ route('manager.products.index') }}" class="px-4 py-2.5 rounded-xl border border-[#D6CBBC] font-bold text-[#6F6259]">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17]">
                {{ $isEdit ? 'Simpan Perubahan' : 'Tambahkan Menu' }}
            </button>
        </div>
    </form>
</div>
@endsection
