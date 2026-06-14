<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return auth()->user()?->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('home');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();
            $target = $user?->isAdmin() ? route('admin.dashboard') : route('home');

            return redirect()->intended($target)->with('success', 'Selamat datang, ' . $user->name . '!');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return auth()->user()?->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('home');
        }
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'home')->with('success', 'Akun berhasil dibuat. Selamat datang, ' . $user->name . '!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }
}
