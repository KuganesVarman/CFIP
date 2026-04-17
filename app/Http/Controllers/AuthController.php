<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
    $credentials = $request->validate([
        'user_id' => 'required|string',
        'password' => 'required|string',
    ]);

    // Find user by user_id (NOT by id)
    $user = User::where('user_id', $credentials['user_id'])->first();

    // Check if user exists
    if (!$user) {
        return back()->withErrors(['login_error' => 'User ID not found.'])->withInput();
    }

    // Check password
    if (!Hash::check($credentials['password'], $user->password)) {
        return back()->withErrors(['login_error' => 'Invalid password.'])->withInput();
    }

    // Login using the user object (Laravel will use 'id' internally)
    Auth::login($user, $request->filled('remember'));
    
    // Regenerate session to prevent fixation attacks
    $request->session()->regenerate();

    // Redirect based on role
    return $this->redirectToDashboard($user);
}


    // Handle logout
    public function logout(Request $request)
    {
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
                return redirect()->route('admin.dashboard');
            case 'P':
                return redirect()->route('pc.dashboard');
            case 'L':
                return redirect()->route('learner.dashboard');
            default:
                Auth::logout();
                return redirect()->route('login.page')->withErrors(['login_error' => 'Invalid role assigned.']);
        }
    }
}
