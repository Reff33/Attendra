<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            // Block suspended accounts
            if ($user->status === 'suspended') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'Your staff account has been suspended by the administrator. Please contact faculty admin.',
                ]);
            }

            // Enforce max 3 active devices limit (like UIDM)
            if (config('session.driver') === 'database' && \Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                $activeSessionCount = \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', session()->getId())
                    ->count();

                if ($activeSessionCount >= 3) {
                    if ($request->boolean('revoke_others')) {
                        \Illuminate\Support\Facades\DB::table('sessions')
                            ->where('user_id', $user->id)
                            ->where('id', '!=', session()->getId())
                            ->delete();
                    } else {
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return back()->withInput($request->only('email'))
                            ->with('device_limit_reached', true)
                            ->withErrors([
                                'email' => 'Device limit reached: Maximum 3 active concurrent devices allowed. Check the box below to sign out of other devices.',
                            ]);
                    }
                }
            }

            $request->session()->regenerate();

            if (Auth::user()->isAdmin() && $request->input('target_role') === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our registered records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
