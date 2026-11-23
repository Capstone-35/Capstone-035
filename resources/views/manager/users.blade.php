@extends('layouts.staff', ['title' => 'Manajemen Akun Staf'])

@section('heading', 'Manager — Manajemen Akun Staf Operasional')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
    <!-- List -->
    <div class="xl:col-span-8 bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Daftar Staf (Kasir, Kitchen, Manager)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email & Kontak</th>
                        <th class="px-4 py-3 whitespace-nowrap">Peran (Role)</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($staffUsers as $stf)
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17]">{{ $stf->name }}</td>
                            <td class="px-4 py-3.5 text-[#6F6259]">
                                <div class="break-all">{{ $stf->email }}</div>
                                <div class="text-[11px]">{{ $stf->phone ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full bg-[#3B2A20] text-white font-bold uppercase text-[10px]">
                                    {{ $stf->getRoleNames()->first() }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $stf->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $stf->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if(auth()->id() === $stf->id)
                                    <span class="text-[11px] font-semibold text-[#6F6259]">Akun Anda</span>
                                @else
                                    <form method="POST" action="{{ route('manager.users.update', ['user' => $stf->id]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="name" value="{{ $stf->name }}">
                                        <input type="hidden" name="email" value="{{ $stf->email }}">
                                        <input type="hidden" name="phone" value="{{ $stf->phone }}">
                                        <input type="hidden" name="role" value="{{ $stf->getRoleNames()->first() }}">
                                        <input type="hidden" name="is_active" value="{{ $stf->is_active ? '0' : '1' }}">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold {{ $stf->is_active ? 'text-amber-800 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $stf->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[#6F6259]">
                                Belum ada akun staf yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Form -->
    <div class="xl:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Akun Staf</h3>
        <form method="POST" action="{{ route('manager.users.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Lengkap *</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="80" class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Email Staf *</label>
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="120" class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nomor Telepon</label>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="20" class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Peran (Role) *</label>
                <select name="role" required class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
                    <option value="kasir" @selected(old('role', 'kasir') === 'kasir')>Kasir (POS)</option>
                    <option value="kitchen" @selected(old('role') === 'kitchen')>Kitchen / Barista (KDS)</option>
                    <option value="manager" @selected(old('role') === 'manager')>Manager Operasional</option>
                </select>
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Kata Sandi (Min. 8 Karakter) *</label>
                <input type="password" name="password" minlength="8" required class="w-full px-3 py-2.5 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <button type="submit" class="w-full min-h-[44px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Simpan Akun Staf
            </button>
        </form>
    </div>
</div>
@endsection
