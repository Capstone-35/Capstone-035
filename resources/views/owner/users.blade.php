@extends('layouts.staff', ['title' => 'Manajemen Pengguna Owner'])

@section('heading', 'Owner — Manajemen Seluruh Pengguna & Hak Akses')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
    <!-- List -->
    <div class="xl:col-span-8 bg-white rounded-2xl border border-[#E6DED2] shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#E6DED2]">
            <h3 class="text-sm font-extrabold text-[#1F1A17]">Daftar Akun Pengguna Staf & Owner</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-xs">
                <thead class="bg-[#F2ECE1] border-b border-[#E6DED2] text-[#3B2A20] font-extrabold uppercase">
                    <tr>
                        <th class="px-4 py-3 whitespace-nowrap">Nama</th>
                        <th class="px-4 py-3">Email & Kontak</th>
                        <th class="px-4 py-3 whitespace-nowrap">Peran (Role)</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F2ECE1]">
                    @forelse($staffUsers as $usr)
                        @php
                            $staffRole = $usr->getRoleNames()->first(fn ($r) => in_array($r, ['kasir', 'kitchen', 'manager', 'owner'], true)) ?? 'kasir';
                        @endphp
                        <tr class="hover:bg-[#FAF6EF]/60">
                            <td class="px-4 py-3.5 font-bold text-[#1F1A17] whitespace-nowrap">{{ $usr->name }}</td>
                            <td class="px-4 py-3.5 text-[#6F6259]">
                                <div class="break-all">{{ $usr->email }}</div>
                                <div class="text-[11px]">{{ $usr->phone ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full bg-[#3B2A20] text-white font-bold uppercase text-[10px]">
                                    {{ $staffRole }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $usr->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $usr->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if(auth()->id() !== $usr->id)
                                    <form method="POST" action="{{ route('owner.users.update', ['user' => $usr->id]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="name" value="{{ $usr->name }}">
                                        <input type="hidden" name="email" value="{{ $usr->email }}">
                                        <input type="hidden" name="role" value="{{ $staffRole }}">
                                        <input type="hidden" name="is_active" value="{{ $usr->is_active ? '0' : '1' }}">
                                        <button type="submit" class="min-h-[34px] px-2.5 py-1 rounded-lg text-xs font-bold {{ $usr->is_active ? 'text-amber-800 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $usr->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 italic">Sesi Anda</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-[#6F6259]">Belum ada akun staf terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Form -->
    <div class="xl:col-span-4 bg-white rounded-2xl border border-[#E6DED2] p-4 sm:p-5 shadow-sm space-y-4">
        <h3 class="text-sm font-extrabold text-[#1F1A17] border-b border-[#F2ECE1] pb-2">Tambah Akun Pengguna</h3>
        <form method="POST" action="{{ route('owner.users.store') }}" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nama Pengguna *</label>
                <input type="text" name="name" maxlength="80" value="{{ old('name') }}" required class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Email *</label>
                <input type="email" name="email" maxlength="120" value="{{ old('email') }}" required class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Nomor Telepon (Opsional)</label>
                <input type="tel" name="phone" maxlength="30" value="{{ old('phone') }}" placeholder="0812xxxxxxxx" class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Peran (Role) *</label>
                <select name="role" required class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF] font-bold">
                    <option value="kasir" {{ old('role') === 'kasir' ? 'selected' : '' }}>Kasir</option>
                    <option value="kitchen" {{ old('role') === 'kitchen' ? 'selected' : '' }}>Kitchen / Barista</option>
                    <option value="manager" {{ old('role') === 'manager' ? 'selected' : '' }}>Manager Operasional</option>
                    <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner (Hak Akses Penuh)</option>
                </select>
            </div>
            <div>
                <label class="block font-bold uppercase text-[#3B2A20] mb-1">Kata Sandi (Min. 8 Karakter) *</label>
                <input type="password" name="password" minlength="8" required class="w-full min-h-[42px] px-3 py-2 rounded-xl border border-[#D6CBBC] bg-[#FAF6EF]">
            </div>
            <button type="submit" class="w-full min-h-[44px] rounded-xl bg-[#3B2A20] text-white font-extrabold hover:bg-[#2A1E17] transition mt-2">
                Simpan Akun
            </button>
        </form>
    </div>
</div>
@endsection
