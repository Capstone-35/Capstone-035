<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi pengelola.',
                ]);
            }

            $timeoutSeconds = max(60, (int) config('session.lifetime', 30) * 60);
            $lastActivity = (int) $request->session()->get('last_activity_timestamp', 0);
            $now = now()->timestamp;

            if ($lastActivity > 0 && ($now - $lastActivity) > $timeoutSeconds) {
                $wasStaff = $user->isStaff();
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $targetRoute = $wasStaff ? 'staff.login' : 'login';

                return redirect()->route($targetRoute)->with(
                    'toast_warning',
                    'Sesi Anda telah berakhir karena tidak aktif selama 30 menit. Silakan masuk kembali.'
                );
            }

            $request->session()->put('last_activity_timestamp', $now);
        }

        return $next($request);
    }
}
