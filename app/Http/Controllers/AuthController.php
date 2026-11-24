<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isMaster() 
                ? redirect()->route('master.dashboard') 
                : redirect()->route('judge.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $phone = preg_replace('/[\s-]+/', '', trim($credentials['phone']));
        // Standardize phone format if needed (e.g. replace leading +62 with 0)
        if (str_starts_with($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        }

        $passwordInput = $credentials['password'];
        $authenticated = Auth::attempt(['phone' => $phone, 'password' => $passwordInput], $request->boolean('remember'));

        // Also allow +62 or spaced phone entry when judges use phone as password
        if (!$authenticated) {
            $normalizedPw = preg_replace('/[\s-]+/', '', trim($passwordInput));
            if (str_starts_with($normalizedPw, '+62')) {
                $normalizedPw = '0' . substr($normalizedPw, 3);
            }
            if ($normalizedPw !== $passwordInput && $normalizedPw === $phone) {
                $authenticated = Auth::attempt(['phone' => $phone, 'password' => $normalizedPw], $request->boolean('remember'));
            }
        }

        if ($authenticated) {
            $user = Auth::user();

            if (!$user->isActive()) {
                Auth::logout();
                return back()->withErrors(['phone' => 'Akun Anda sedang dinonaktifkan oleh panitia.']);
            }

            $user->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            if ($user->isMaster()) {
                return redirect()->intended(route('master.dashboard'));
            }

            return redirect()->intended(route('judge.dashboard'));
        }

        return back()->withErrors([
            'phone' => 'Username / Nomor HP atau password yang dimasukkan salah.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * One-click signed auto-login link for assigned judges.
     */
    public function magicJudgeLogin(Request $request, User $judge)
    {
        if (!$request->hasValidSignature() || !$judge->isJudge()) {
            abort(403, 'Link akses juri tidak valid.');
        }

        if (!hash_equals($judge->judgeAccessKey(), (string) $request->query('k', ''))) {
            return redirect()->route('login')->with('error', 'Link akses juri sudah diperbarui oleh panitia. Silakan minta link akses terbaru atau login manual dengan Nomor HP Anda.');
        }

        if (!$judge->isActive()) {
            return redirect()->route('login')->with('error', 'Akun juri Anda sedang dinonaktifkan oleh panitia.');
        }

        Auth::login($judge, true);
        $judge->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->route('judge.dashboard')
            ->with('success', "Selamat datang, {$judge->name}. Anda masuk sebagai Dewan Juri.");
    }
}
