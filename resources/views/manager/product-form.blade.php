@extends('layouts.staff', ['title' => ($isEdit ? 'Ubah' : 'Tambah') . ' Menu'])

@section('heading', 'Manager — ' . ($isEdit ? 'Ubah Menu Produk' : 'Tambah Menu Baru'))

@section('content')
<div class="max-w-2xl bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-6 shadow-sm space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#F2ECE1] pb-3">
        <a href="{{ route('manager.products.index') }}" class="text-xs font-bold text-[#6F6259] hover:text-[#3B2A20]">
            ← Kembali ke Daftar Produk
        </a>
        <h2 class="text-base font-extrabold text-[#1F1A17]">{{ $isEdit ? 'Ubah Menu' : 'Tambah Menu' }}</h2>
    </div>

    <form method="POST"
          action="{{ $isEdit ? route('manager.products.update', ['product' => $product->id]) : route('manager.products.store') }}"
          enctype="multipart/form-data"
          class="space-y-4 text-xs">
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
                   placeholder="Contoh: Butterscotch (Best Seller)"
                   class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm">
        </div>

        <div>
            <label class="block font-bold uppercase text-[#3B2A20] mb-1">Deskripsi Singkat Menu</label>
            <textarea name="description" rows="2" maxlength="300" class="w-full px-3.5 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]"
                      placeholder="Deskripsi singkat cita rasa atau komposisi menu...">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start">
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Harga Satuan (Rupiah) *</label>
                <input type="number" name="price" min="1000" max="5000000" value="{{ old('price', $product->price) }}" required
                       class="w-full px-3.5 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold text-sm tabular-nums">
            </div>

            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Foto Menu (JPG / PNG / WEBP, Maks. 3MB)</label>
                <input type="file" name="image" id="product-image-input" accept="image/jpeg,image/png,image/webp"
                       class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-[#3B2A20] file:text-[#FAF6EF] file:font-bold file:cursor-pointer hover:file:bg-[#2A1E17]">
                @php($existingImageUrl = $product->imageUrl())
                <div id="product-image-preview-wrap" class="mt-2.5 {{ $existingImageUrl ? 'flex' : 'hidden' }} items-center gap-3 p-2.5 rounded-xl bg-[#FAF6EF] border border-[#E6DED2]">
                    <img id="product-image-preview" src="{{ $existingImageUrl ?? '' }}" alt="Preview Foto Menu"
                         class="w-16 h-16 rounded-lg object-cover border border-[#D6CBBC] shrink-0">
                    <div class="min-w-0 space-y-1">
                        <div class="text-[11px] font-bold text-[#1F1A17]">Preview Foto Menu</div>
                        @if($isEdit && $existingImageUrl)
                            <label class="inline-flex items-center gap-1.5 text-[11px] text-[#B91C1C] font-semibold cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-red-400 text-red-600">
                                <span>Hapus foto saat ini</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <div>
                <input type="hidden" name="is_available" value="0">
                <label class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer font-bold">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} class="rounded text-[#1F7A4C]">
                    <span>Tersedia (Stok Ada)</span>
                </label>
            </div>

            <div>
                <input type="hidden" name="is_featured" value="0">
                <label class="p-3.5 rounded-xl border border-[#FCD34D] bg-[#FEF3C7]/40 flex items-center gap-2 cursor-pointer font-bold">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="rounded text-[#A9552F]">
                    <span>★ Menu Andalan</span>
                </label>
            </div>

            <div>
                <input type="hidden" name="is_active" value="0">
                <label class="p-3.5 rounded-xl border border-[#E6DED2] bg-[#FAF6EF] flex items-center gap-2 cursor-pointer font-bold">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }} class="rounded text-[#3B2A20]">
                    <span>Aktif di Katalog</span>
                </label>
            </div>
        </div>

        <div class="pt-3 border-t border-[#F2ECE1] flex flex-wrap justify-end gap-2">
            <a href="{{ route('manager.products.index') }}" class="px-4 py-2.5 rounded-xl border border-[#D6CBBC] font-bold text-[#6F6259] hover:bg-[#FAF6EF]">
                Batal
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17]">
                {{ $isEdit ? 'Simpan Perubahan' : 'Tambahkan Menu' }}
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('product-image-input')?.addEventListener('change', function (e) {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const wrap = document.getElementById('product-image-preview-wrap');
        const img = document.getElementById('product-image-preview');
        if (img && wrap) {
            img.src = URL.createObjectURL(file);
            wrap.classList.remove('hidden');
            wrap.classList.add('flex');
        }
    });
</script>
@endsection
