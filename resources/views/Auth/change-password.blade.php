<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password — CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            overflow: hidden;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(145deg, #e0f0ff 0%, #c2dff7 25%, #9ac8f0 55%, #6aaee8 80%, #4a95d9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Floating bubbles — same as login page */
        .bg-bubbles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .bubble {
            position: absolute;
            bottom: -150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            animation: riseUp linear infinite;
        }
        @keyframes riseUp {
            0%   { transform: translateY(0) scale(1); opacity: 0.6; }
            100% { transform: translateY(-110vh) scale(1.1); opacity: 0; }
        }

        /* Card */
        .card {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(0,0,0,0.08);
            padding: 2.4rem 2.2rem 2rem;
            width: 100%;
            max-width: 400px;
            animation: cardIn 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 1.4rem;
        }
        .logo-wrap img {
            height: 64px;
            object-fit: contain;
        }

        .card-title {
            font-size: 19px;
            font-weight: 700;
            color: #1a1f36;
            text-align: center;
            margin-bottom: 4px;
        }
        .card-sub {
            font-size: 12.5px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 1.6rem;
            line-height: 1.5;
        }

        /* Notice banner */
        .notice-banner {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 9px;
            padding: 10px 12px;
            margin-bottom: 1.4rem;
            font-size: 12px;
            color: #1d4ed8;
            line-height: 1.5;
        }
        .notice-banner svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }

        /* Form fields */
        .field-group { margin-bottom: 1rem; }
        .field-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .field-wrap {
            position: relative;
        }
        .field-wrap input {
            width: 100%;
            padding: 10px 38px 10px 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 9px;
            font-size: 13.5px;
            font-family: inherit;
            color: #1a1f36;
            background: #f9fafb;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .field-wrap input:focus {
            border-color: #1a4fa8;
            background: #fff;
        }
        .toggle-pw {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 2px;
            display: flex;
            align-items: center;
        }
        .toggle-pw:hover { color: #6b7280; }
        .toggle-pw svg { width: 15px; height: 15px; }

        /* Strength bar */
        .strength-wrap {
            display: flex;
            gap: 4px;
            margin-top: 6px;
        }
        .strength-seg {
            flex: 1;
            height: 3px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background 0.25s;
        }
        .strength-label {
            font-size: 10.5px;
            color: #9ca3af;
            margin-top: 3px;
        }

        /* Error alert */
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 12px;
            color: #991b1b;
            margin-bottom: 1rem;
        }

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #3a85d4, #1e5faf);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            transition: opacity 0.15s, transform 0.15s;
        }
        .submit-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .submit-btn:active { transform: translateY(0); }
    </style>
</head>
<body>

<div class="bg-bubbles">
    @foreach([120,80,60,100,45,90,70,55,110,75] as $i => $size)
    <div class="bubble" style="
        width:{{ $size }}px; height:{{ $size }}px;
        left:{{ [5,15,25,38,50,60,72,80,88,95][$i] }}%;
        animation-duration:{{ [12,18,14,20,16,11,19,13,17,15][$i] }}s;
        animation-delay:{{ [0,3,1,5,2,7,4,6,8,1][$i] }}s;
    "></div>
    @endforeach
</div>

<div class="card">
    <div class="logo-wrap">
        <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo">
    </div>

    <div class="card-title">Set Your Password</div>
    <div class="card-sub">
        Welcome, <strong>{{ Auth::user()->name }}</strong>.<br>
        Please create a new password to continue.
    </div>

    <div class="notice-banner">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Your account was set up by an administrator. You must change your password before you can access the system.
    </div>

    @if($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('change.password.submit') }}">
        @csrf

        <div class="field-group">
            <label for="password">New Password</label>
            <div class="field-wrap">
                <input type="password" id="password" name="password"
                       placeholder="Minimum 8 characters"
                       oninput="updateStrength(this.value)"
                       autocomplete="new-password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('password', this)">
                    <svg id="eyeIcon1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <div class="strength-wrap">
                <div class="strength-seg" id="seg1"></div>
                <div class="strength-seg" id="seg2"></div>
                <div class="strength-seg" id="seg3"></div>
                <div class="strength-seg" id="seg4"></div>
            </div>
            <div class="strength-label" id="strengthLabel">Enter a password</div>
        </div>

        <div class="field-group">
            <label for="password_confirmation">Confirm Password</label>
            <div class="field-wrap">
                <input type="password" id="password_confirmation" name="password_confirmation"
                       placeholder="Re-enter your password"
                       autocomplete="new-password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('password_confirmation', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="submit-btn">Set Password &amp; Sign In</button>
    </form>
</div>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const showing = inp.type === 'text';
    inp.type = showing ? 'password' : 'text';
    btn.querySelector('svg').innerHTML = showing
        ? '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'
        : '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
}

function updateStrength(pw) {
    const segs   = [1,2,3,4].map(n => document.getElementById('seg' + n));
    const label  = document.getElementById('strengthLabel');
    let score    = 0;

    if (pw.length >= 8)                          score++;
    if (pw.length >= 12)                         score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw))   score++;
    if (/\d/.test(pw) && /[^a-zA-Z0-9]/.test(pw)) score++;

    const colors = ['#e24b4a', '#f59e0b', '#22c7b8', '#1d9e75'];
    const labels = ['Too weak', 'Fair', 'Good', 'Strong'];

    segs.forEach((s, i) => {
        s.style.background = i < score ? colors[score - 1] : '#e5e7eb';
    });
    label.textContent  = pw.length ? labels[score - 1] || 'Too weak' : 'Enter a password';
    label.style.color  = pw.length ? colors[score - 1] || '#e24b4a'  : '#9ca3af';
}
</script>

</body>
</html>
