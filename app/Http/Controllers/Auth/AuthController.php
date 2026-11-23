<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showMemberLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Auth::user()->homeRoute());
        }

        return view('auth.member-login');
    }

    public function memberLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'contact' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ], [
            'contact.required' => 'Email atau nomor kontak wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $contact = trim($credentials['contact']);
        $contactLower = strtolower($contact);
        $user = User::query()
            ->where('email', $contactLower)
            ->orWhere('phone', $contact)
            ->first();

        if (! $user || ! $user->is_active || ! $user->hasRole('member') || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('contact'))->withErrors([
                'contact' => 'Email/kontak atau kata sandi tidak sesuai.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_timestamp', now()->timestamp);
        $request->session()->put('ordering_mode', 'member');

        $user->update(['last_login_at' => now()]);

        if ($request->session()->has('qr_table_id')) {
            return redirect()->route('menu.index')->with('toast_success', "Selamat datang kembali, {$user->name}!");
        }

        return redirect()->intended(route('member.dashboard'))->with('toast_success', "Selamat datang, {$user->name}!");
    }

    public function showMemberRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Auth::user()->homeRoute());
        }

        return view('auth.member-register');
    }

    public function memberRegister(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone' => trim((string) $request->input('phone', '')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:9', 'max:25', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'consent' => ['accepted'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi (2–60 karakter).',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar sebagai akun.',
            'phone.required' => 'Nomor telepon/WhatsApp wajib diisi.',
            'phone.unique' => 'Nomor telepon ini sudah terdaftar.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'consent.accepted' => 'Anda wajib menyetujui pengolahan data pribadi sesuai UU PDP No. 27 Tahun 2022.',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::query()->create([
                'name' => trim(strip_tags($validated['name'])),
                'email' => strtolower(trim($validated['email'])),
                'phone' => trim($validated['phone']),
                'password' => $validated['password'],
                'is_active' => true,
                'is_demo' => false,
                'last_login_at' => now(),
            ]);

            $user->assignRole('member');

            $memberCode = 'MBR-774-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
            if (Member::query()->where('member_code', $memberCode)->exists()) {
                $memberCode = 'MBR-774-'.strtoupper(substr(md5($user->id.'-'.microtime(true)), 0, 6));
            }
            Member::query()->create([
                'user_id' => $user->id,
                'member_code' => $memberCode,
                'points_balance' => 0,
                'consent_at' => now(),
                'joined_at' => now(),
                'is_demo' => false,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_timestamp', now()->timestamp);
        $request->session()->put('ordering_mode', 'member');

        if ($request->session()->has('qr_table_id')) {
            return redirect()->route('menu.index')->with('toast_success', 'Pendaftaran Member berhasil! Promo khusus Member siap digunakan.');
        }

        return redirect()->route('member.dashboard')->with('toast_success', 'Akun Member Anda berhasil dibuat.');
    }

    public function showStaffLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect(Auth::user()->homeRoute());
        }

        return view('auth.staff-login');
    }

    public function staffLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email staf wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::query()->where('email', strtolower(trim($credentials['email'])))->first();

        if (! $user || ! $user->is_active || ! $user->isStaff() || ! Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Kredensial staf tidak valid atau tidak memiliki hak akses operasional.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_timestamp', now()->timestamp);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended($user->homeRoute())->with('toast_success', "Selamat bertugas, {$user->name}!");
    }

    public function logout(Request $request): RedirectResponse
    {
        $wasStaff = Auth::check() && Auth::user()->isStaff();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($wasStaff) {
            return redirect()->route('staff.login')->with('toast_info', 'Anda telah keluar dari sesi staf.');
        }

        return redirect()->route('menu.index')->with('toast_info', 'Anda telah keluar dari akun Member.');
    }
}
