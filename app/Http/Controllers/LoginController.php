<?php
/*
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    // Show login page
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Handle login process
   public function login(Request $request)
{
    $request->validate([
        'user_id' => 'required',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('user_id', $request->user_id)->first();

    if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        // Store user info in session
        session([
            'user_id' => $user->user_id,
            'role' => $user->role,
            'name' => $user->name,
        ]);

        // Redirect based on role
        switch ($user->role) {
            case 'A':
                return redirect()->route('admin.dashboard');
            case 'PC':
                return redirect()->route('pc.dashboard');
            case 'L':
                return redirect()->route('learner.dashboard');
            default:
                return redirect()->route('login.page');
        }
    }

    // If invalid credentials
    return back()->withErrors(['login_error' => 'Invalid User ID or Password.']);
}


    // Logout
    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login.form');
    }
}
