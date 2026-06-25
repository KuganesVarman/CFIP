<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            width: 100%;
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
            cursor: pointer;
        }

        /* Floating background bubbles */
        .bg-bubbles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .bubble {
            position: absolute;
            bottom: -150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            animation: riseUp linear infinite;
        }

        @keyframes riseUp {
            0%   { transform: translateY(0) scale(1);   opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 0.6; }
            100% { transform: translateY(-110vh) scale(1.2); opacity: 0; }
        }

        /* Landing screen */
        .landing {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .landing.hidden {
            opacity: 0;
            transform: scale(0.94);
            pointer-events: none;
        }

        .logo-wrapper {
            animation: logoPop 1.4s cubic-bezier(0.34, 1.4, 0.64, 1) forwards;
        }

        @keyframes logoPop {
            0%   { opacity: 0; transform: scale(0.6) translateY(30px); }
            70%  { opacity: 1; transform: scale(1.04) translateY(-4px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .logo-wrapper img {
            width: 400px;
            max-width: 78vw;
            height: auto;
            filter: drop-shadow(0 24px 48px rgba(0, 70, 160, 0.3));
        }

        .click-hint {
            margin-top: 2.75rem;
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            text-shadow: 0 2px 12px rgba(0, 60, 160, 0.4);
            opacity: 0;
            animation: hintFade 2.8s ease-in-out infinite;
            animation-delay: 1.8s;
        }

        @keyframes hintFade {
            0%, 100% { opacity: 0.5; transform: translateY(0); }
            50%       { opacity: 1;   transform: translateY(-5px); }
        }

        .hint-arrow {
            display: block;
            margin-top: 0.5rem;
            font-size: 1.2rem;
            opacity: 0;
            animation: hintFade 2.8s ease-in-out infinite;
            animation-delay: 2s;
            color: rgba(255,255,255,0.85);
        }

        /* Modal overlay */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(8, 40, 90, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        /* Login card */
        .login-card {
            background: rgba(255, 255, 255, 0.93);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 22px;
            padding: 2.75rem 3rem;
            width: 100%;
            max-width: 430px;
            margin: 1rem;
            box-shadow:
                0 30px 70px rgba(0, 50, 120, 0.28),
                0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(40px) scale(0.96);
            transition: transform 0.45s cubic-bezier(0.34, 1.3, 0.64, 1);
            cursor: default;
        }

        .modal-overlay.active .login-card {
            transform: translateY(0) scale(1);
        }

        .card-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .card-logo img {
            width: 130px;
            height: auto;
            filter: drop-shadow(0 4px 10px rgba(0,60,140,0.15));
        }

        .card-title {
            font-size: 1.65rem;
            font-weight: 700;
            color: #0f2d52;
            text-align: center;
            margin-bottom: 0.3rem;
        }

        .card-subtitle {
            text-align: center;
            color: #7a9abf;
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.45rem;
            color: #1e3f65;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1.1rem;
            border: 1.5px solid #c5daf0;
            border-radius: 11px;
            font-size: 0.95rem;
            color: #0f2d52;
            background: rgba(240, 248, 255, 0.6);
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3a85d4;
            box-shadow: 0 0 0 4px rgba(58, 133, 212, 0.14);
            background: #fff;
        }

        .form-group input::placeholder {
            color: #a8c3de;
            font-weight: 300;
        }

        .pw-wrap {
            position: relative;
        }

        .pw-wrap input {
            padding-right: 2.8rem;
        }

        .pw-toggle {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #a8c3de;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .pw-toggle:hover { color: #3a85d4; }

        .pw-toggle svg { width: 18px; height: 18px; }

        .error-message {
            background: #fff0f0;
            border: 1px solid #ffd0d0;
            color: #b91c1c;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }

        .login-btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #3a85d4 0%, #1e5faf 100%);
            color: #fff;
            border: none;
            border-radius: 11px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.22s ease;
            font-family: inherit;
            margin-top: 0.25rem;
            letter-spacing: 0.03em;
            box-shadow: 0 5px 18px rgba(30, 95, 175, 0.38);
        }

        .login-btn:hover {
            background: linear-gradient(135deg, #2575c8 0%, #174fa0 100%);
            box-shadow: 0 8px 24px rgba(30, 95, 175, 0.5);
            transform: translateY(-2px);
        }

        .login-btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(30, 95, 175, 0.3);
        }

        .close-hint {
            text-align: center;
            margin-top: 1.25rem;
            color: #a0bcd8;
            font-size: 0.78rem;
            letter-spacing: 0.02em;
        }

        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.75rem; }
            .logo-wrapper img { width: 260px; }
        }
    </style>
</head>
<body>

    <!-- Floating background bubbles -->
    <div class="bg-bubbles" id="bgBubbles"></div>

    <!-- Landing: big logo + hint -->
    <div class="landing" id="landing">
        <div class="logo-wrapper">
            <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo">
        </div>
        <p class="click-hint">Click anywhere to sign in</p>
        <span class="hint-arrow">↓</span>
    </div>

    <!-- Login Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="login-card" id="loginCard">

            <div class="card-logo">
                <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo">
            </div>

            <h2 class="card-title">Welcome Back</h2>
            <p class="card-subtitle">Sign in to continue</p>

            @if ($errors->has('user_id'))
                <div class="error-message">
                    {{ $errors->first('user_id') }}
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
                           placeholder="Enter your User ID"
                           required
                           autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                            {{-- Eye icon (shown when password is hidden) --}}
                            <svg id="iconEye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12S5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            {{-- Eye-off icon (shown when password is visible) --}}
                            <svg id="iconEyeOff" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <p class="close-hint">Press Esc or click outside to dismiss</p>
        </div>
    </div>

    <script>
        // Generate floating bubbles
        const bubbleContainer = document.getElementById('bgBubbles');
        const bubbleConfig = [
            { size: 80,  left: 8,  delay: 0,   dur: 18 },
            { size: 50,  left: 20, delay: 3,   dur: 14 },
            { size: 100, left: 35, delay: 7,   dur: 20 },
            { size: 40,  left: 50, delay: 1,   dur: 12 },
            { size: 70,  left: 62, delay: 5,   dur: 16 },
            { size: 55,  left: 75, delay: 9,   dur: 22 },
            { size: 90,  left: 88, delay: 2,   dur: 17 },
            { size: 35,  left: 15, delay: 11,  dur: 13 },
            { size: 65,  left: 45, delay: 6,   dur: 19 },
            { size: 45,  left: 92, delay: 4,   dur: 15 },
        ];

        bubbleConfig.forEach(({ size, left, delay, dur }) => {
            const el = document.createElement('div');
            el.className = 'bubble';
            el.style.cssText = `width:${size}px;height:${size}px;left:${left}%;animation-duration:${dur}s;animation-delay:${delay}s;`;
            bubbleContainer.appendChild(el);
        });

        const overlay  = document.getElementById('modalOverlay');
        const landing  = document.getElementById('landing');
        const card     = document.getElementById('loginCard');

        function openModal() {
            overlay.classList.add('active');
            landing.classList.add('hidden');
        }

        function closeModal() {
            overlay.classList.remove('active');
            landing.classList.remove('hidden');
        }

        // Open on any body click
        document.body.addEventListener('click', openModal);

        // Prevent clicks inside card from closing
        card.addEventListener('click', e => e.stopPropagation());

        // Close on overlay background click
        overlay.addEventListener('click', closeModal);

        // Close on Escape key
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        // Auto-open if validation errors exist (after failed login)
        @if ($errors->any())
            openModal();
        @endif

        // Password show/hide toggle
        const pwToggle  = document.getElementById('pwToggle');
        const pwInput   = document.getElementById('password');
        const iconEye   = document.getElementById('iconEye');
        const iconEyeOff = document.getElementById('iconEyeOff');

        pwToggle.addEventListener('click', function () {
            const isHidden = pwInput.type === 'password';
            pwInput.type      = isHidden ? 'text' : 'password';
            iconEye.style.display    = isHidden ? 'none'  : '';
            iconEyeOff.style.display = isHidden ? ''      : 'none';
        });
    </script>
</body>
</html>
