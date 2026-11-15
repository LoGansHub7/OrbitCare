<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Deactivated accounts cannot sign in.
        if (Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            AuditEvent::record('auth.login');

            return redirect()->intended(route('home'));
        }

        throw ValidationException::withMessages([
            'email' => 'These credentials do not match an active account.',
        ]);
    }

    /**
     * Presentation-only one-click access. It is intentionally unavailable
     * unless the server owner explicitly enables ORBITCARE_JUDGE_DEMO.
     */
    public function judgeDemoLogin(Request $request, string $account)
    {
        abort_unless(app()->environment('local') || config('orbitcare.judge_demo'), 404);

        $email = match ($account) {
            'astronaut' => 'astro2@orbitcare.test',
            'medical-officer' => 'medic@orbitcare.test',
            'admin' => 'admin@orbitcare.test',
            default => abort(404),
        };

        $user = User::query()->where('email', $email)->where('is_active', true)->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();
        AuditEvent::record('auth.judge_demo_login', $user, ['account' => $account]);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        AuditEvent::record('auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
