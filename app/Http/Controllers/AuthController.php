<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }
        return view('auth.login');
    }

    // Handle login process
    public function login(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|string',
            'password' => 'required|string',
        ]);

        // Rate-limit key: lowercase user_id + IP address
        $key = Str::transliterate(Str::lower($request->input('user_id')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'user_id' => 'Too many login attempts. Please try again in '
                             .ceil($seconds / 60).' minute(s).',
            ]);
        }

        // Find user by user_id (NOT by id)
        $user = User::where('user_id', $request->input('user_id'))->first();

        // Single generic error for wrong user_id OR wrong password (prevents user enumeration)
        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($key, 900); // 15-minute decay window

            activity()
                ->withProperties(['ip' => $request->ip(), 'user_id_attempted' => $request->input('user_id')])
                ->log('Failed login attempt');

            return back()->withErrors([
                'user_id' => 'Invalid credentials. Please check your User ID and password.',
            ])->onlyInput('user_id');
        }

        // Successful login — clear the rate limit counter
        RateLimiter::clear($key);

        // Login using the user object (Laravel will use 'id' internally)
        Auth::login($user, $request->filled('remember'));

        // Regenerate session to prevent fixation attacks
        $request->session()->regenerate();

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('User logged in');

        // Redirect based on role
        return $this->redirectToDashboard($user);
    }


    // Show first-login change-password page
    public function showChangePassword()
    {
        return view('Auth.change-password');
    }

    // Handle first-login password change
    public function submitChangePassword(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $user = Auth::user();
        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Password changed');

        return $this->redirectToDashboard($user);
    }

    // Handle logout
    public function logout(Request $request)
    {
        activity()
            ->causedBy(Auth::user())
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.page')->with('success', 'You have been logged out successfully.');
    }

    // Helper method to redirect based on role
    private function redirectToDashboard($user)
    {
        switch ($user->role) {
            case 'A':
                return redirect()->route('admin.analytics.levels');
            case 'PC':
                return redirect()->route('pc.analytics.levels');
            case 'L':
                return redirect()->route('learner.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login.page')->withErrors(['login_error' => 'Invalid role assigned.']);
        }
    }
}
