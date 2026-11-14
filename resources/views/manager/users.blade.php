@extends('layouts.staff', ['title' => 'Manajemen Akun Staf'])

@section('heading', 'Manager — Manajemen Akun Staf Operasional')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- List -->
    <div class="lg:col-span-8 bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Daftar Staf (Kasir, Kitchen, Manager)</h3>
        </div>
        <table class="w-full text-left text-xs">
            <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email & Kontak</th>
                    <th class="px-4 py-3">Peran (Role)</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#F2ECE1]">
                @foreach($staffUsers as $stf)
                    <tr class="hover:bg-[#FAF6EF]/60">
                        <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $stf->name }}</td>
                        <td class="px-4 py-3.5 text-[#6F6259]">
                            <div>{{ $stf->email }}</div>
                            <div class="text-[11px]">{{ $stf->phone ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-[#3B2A20] text-white font-bold uppercase text-[10px]">
                                {{ $stf->getRoleNames()->first() }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $stf->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $stf->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            <form method="POST" action="{{ route('manager.users.update', ['user' => $stf->id]) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="name" value="{{ $stf->name }}">
                                <input type="hidden" name="email" value="{{ $stf->email }}">
                                <input type="hidden" name="role" value="{{ $stf->getRoleNames()->first() }}">
                                <input type="hidden" name="is_active" value="{{ $stf->is_active ? '0' : '1' }}">
                                <button type="submit" class="text-xs font-bold {{ $stf->is_active ? 'text-amber-800 hover:underline' : 'text-emerald-700 hover:underline' }}">
                                    {{ $stf->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Create Form -->
    <div class="lg:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Akun Staf</h3>
        <form method="POST" action="{{ route('manager.users.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Lengkap *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Email Staf *</label>
                <input type="email" name="email" required class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nomor Telepon</label>
                <input type="text" name="phone" class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Peran (Role) *</label>
                <select name="role" required class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
                    <option value="kasir">Kasir (POS)</option>
                    <option value="kitchen">Kitchen / Barista (KDS)</option>
                    <option value="manager">Manager Operasional</option>
                </select>
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Kata Sandi (Min. 8 Karakter) *</label>
                <input type="password" name="password" minlength="8" required class="w-full px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <button type="submit" class="w-full min-h-[42px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Simpan Akun Staf
            </button>
        </form>
    </div>
</div>
@endsection
