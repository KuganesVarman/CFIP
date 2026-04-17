{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CFIP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            overflow-x: hidden;
        }

        .login-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .left-panel {
            width: 50%;
            background: linear-gradient(135deg, #b8d4f1 0%, #8eb3d9 50%, #7a9ec9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        .logo-container {
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .logo-container img {
            width: 100%;
            max-width: 400px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .right-panel {
            width: 50%;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .login-form-container {
            width: 100%;
            max-width: 450px;
            padding: 0 1rem;
        }

        .login-form-container h1 {
            font-size: 3rem;
            margin-bottom: 3rem;
            color: #000000;
            font-weight: 700;
            line-height: 1;
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.625rem;
            color: #333333;
            font-size: 0.9rem;
            font-weight: 400;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #cccccc;
            border-radius: 3px;
            font-size: 1rem;
            color: #333333;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #999999;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 0.5rem;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .remember-me label {
            color: #333333;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
        }

        .login-button {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: #ff6b35;
            color: #ffffff;
            border: none;
            border-radius: 3px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .login-button:hover {
            background: #ff5722;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .error-message ul {
            margin: 0;
            padding-left: 1.25rem;
            list-style: none;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: #666666;
            text-decoration: none;
            font-size: 0.875rem;
        }

        .forgot-password a:hover {
            color: #ff6b35;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .left-panel, .right-panel {
                width: 100%;
            }

            .left-panel {
                min-height: 200px;
                padding: 2rem;
            }

            .logo-container {
                max-width: 250px;
            }

            .login-form-container h1 {
                font-size: 2.5rem;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel with Logo -->
        <div class="left-panel">
            <div class="logo-container">
                <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo">
            </div>
        </div>

        <!-- Right Panel with Login Form -->
        <div class="right-panel">
            <div class="login-form-container">
                <h1>Login</h1>


                @if ($errors->has('login_error'))
                    <div class="error-message">
                         {{ $errors->first('login_error') }}
                    </div>
                @endif


                <form method="POST" action="{{ route('login') }}">
                     @csrf

                     <div class="form-group">
                        <label for="user_id">User ID</label>
                         <input type="text"
                             id="user_id"
                             name="user_id"
                             value="{{ old('user_id') }}"
                             placeholder="User ID"
                             required >
                     </div>

                <div class="form-group">
                    <label for="password">Password</label>
                        <input type="password"
                            id="password"
                            name="password"
                             placeholder="Password"
                                required >
                </div>

                    <button type="submit" class="login-button">Login</button>
                </form>
                
                    @if (Route::has('password.request'))
                        <div class="forgot-password">
                            <a href="{{ route('password.request') }}">Forgot your password?</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</body>
</html>