<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your CFIP Programme Coordinator Account</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, Helvetica, sans-serif;
        background: #eef2fb;
        color: #1a1f36;
        -webkit-font-smoothing: antialiased;
    }

    .wrapper {
        max-width: 580px;
        margin: 36px auto;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.09);
    }

    /* ── Header (teal/green for PC role) ── */
    .header {
        background: linear-gradient(135deg, #134e35 0%, #1d9e75 100%);
        padding: 36px 40px 30px;
        text-align: center;
    }
    .header-pill {
        display: inline-block;
        background: rgba(255,255,255,0.15);
        color: rgba(255,255,255,0.85);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        padding: 5px 14px;
        border-radius: 20px;
        margin-bottom: 16px;
    }
    .role-badge {
        display: inline-block;
        background: rgba(255,255,255,0.22);
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .header h1 {
        color: #ffffff;
        font-size: 24px;
        font-weight: 700;
        line-height: 1.3;
        margin-bottom: 6px;
    }
    .header p {
        color: rgba(255,255,255,0.68);
        font-size: 13px;
    }

    /* ── Body ── */
    .body {
        background: #ffffff;
        padding: 36px 40px;
    }

    .greeting {
        font-size: 15px;
        line-height: 1.7;
        color: #374151;
        margin-bottom: 20px;
    }
    .greeting strong { color: #1a1f36; }

    /* ── Notice strip (green for PC) ── */
    .notice {
        border-left: 4px solid #1d9e75;
        background: #f0fdf4;
        border-radius: 0 8px 8px 0;
        padding: 13px 16px;
        font-size: 13px;
        color: #166534;
        line-height: 1.6;
        margin-bottom: 28px;
    }

    /* ── Credential box ── */
    .cred-box {
        border: 1px solid #e5e9f5;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 28px;
    }
    .cred-box-header {
        background: #1d9e75;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 11px 20px;
    }
    .cred-row {
        display: flex;
        align-items: center;
        padding: 14px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e9f5;
    }
    .cred-row:last-child { border-bottom: none; }
    .cred-label {
        min-width: 100px;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .cred-value {
        font-size: 16px;
        font-weight: 700;
        color: #1a1f36;
        font-family: 'Courier New', Courier, monospace;
        letter-spacing: 0.05em;
    }

    /* ── CTA button (green) ── */
    .btn-wrap { text-align: center; margin-bottom: 22px; }
    .btn {
        display: inline-block;
        background: linear-gradient(135deg, #1d9e75 0%, #134e35 100%);
        color: #ffffff !important;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        padding: 14px 42px;
        border-radius: 10px;
        letter-spacing: 0.02em;
    }

    .url-note {
        font-size: 12px;
        color: #9ca3af;
        text-align: center;
        margin-bottom: 24px;
        line-height: 1.7;
    }
    .url-note a { color: #1d9e75; text-decoration: none; word-break: break-all; }

    /* ── Responsibilities section ── */
    .section-title {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 12px;
    }
    .steps {
        list-style: none;
        margin-bottom: 24px;
    }
    .steps li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #374151;
        line-height: 1.6;
        margin-bottom: 9px;
    }
    .step-num {
        min-width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #1d9e75;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* ── Access info box ── */
    .access-box {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        padding: 14px 16px;
        font-size: 13px;
        color: #166534;
        line-height: 1.7;
        margin-bottom: 20px;
    }
    .access-box strong { color: #14532d; }

    /* ── Warning ── */
    .warning {
        background: #fef9c3;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 12.5px;
        color: #854d0e;
        line-height: 1.6;
    }

    /* ── Footer ── */
    .footer {
        background: #f8fafc;
        border-top: 1px solid #e5e9f5;
        padding: 22px 40px;
        text-align: center;
    }
    .footer p {
        font-size: 11.5px;
        color: #9ca3af;
        line-height: 1.7;
        margin-bottom: 6px;
    }
    .footer .stamp {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #d1d5db;
    }
</style>
</head>
<body>

<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-pill">Certified Financial Investigator Programme</div>
        <div class="role-badge">Programme Coordinator</div>
        <h1>Welcome, {{ $name }}!</h1>
        <p>Your coordinator account has been activated</p>
    </div>

    {{-- Body --}}
    <div class="body">

        <p class="greeting">
            Dear <strong>{{ $name }}</strong>,<br><br>
            You have been appointed as a <strong>Programme Coordinator</strong> on the
            <strong>CFIP System</strong>. In this role, you will have access to your agency's
            learner data, analytics dashboards, and progress reports for the Certified Financial
            Investigator Programme. Use the credentials below to sign in.
        </p>

        <div class="notice">
            <strong>First login notice:</strong> You will be prompted to set a new password
            on your first sign-in. The temporary password below is for one-time use only.
        </div>

        {{-- Credentials --}}
        <div class="cred-box">
            <div class="cred-box-header">Your Login Credentials</div>
            <div class="cred-row">
                <span class="cred-label">Username</span>
                <span class="cred-value">{{ $username }}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Password</span>
                <span class="cred-value">{{ $password }}</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="btn-wrap">
            <a href="{{ $loginUrl }}" class="btn">Sign In as Programme Coordinator &rarr;</a>
        </div>

        <p class="url-note">
            Button not working? Copy this link into your browser:<br>
            <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
        </p>

        {{-- Access info --}}
        <div class="access-box">
            <strong>As a Programme Coordinator, you have access to:</strong><br>
            Analytics dashboards scoped to your agency &bull;
            Learner progress tracking &bull;
            Domain &amp; module completion rates &bull;
            Report generation for your department
        </div>

        {{-- Steps --}}
        <p class="section-title">Getting started</p>
        <ul class="steps">
            <li>
                <span class="step-num">1</span>
                Sign in with the credentials above and set your permanent password.
            </li>
            <li>
                <span class="step-num">2</span>
                You will land on your analytics dashboard, scoped to your agency's data.
            </li>
            <li>
                <span class="step-num">3</span>
                Use the sidebar to navigate between Overview, Domain Analytics, Module Analytics,
                Student Progress, and Reports.
            </li>
        </ul>

        <div class="warning">
            <strong>Confidential:</strong> Do not share your credentials with others.
            The data accessible through this account is restricted to authorised personnel only.
            Contact your CFIP administrator if you encounter any access issues.
        </div>

    </div>{{-- /body --}}

    {{-- Footer --}}
    <div class="footer">
        <p>
            This is an automated notification from the CFIP System.<br>
            If you were not expecting this email, please contact your programme administrator.
        </p>
        <div class="stamp">CFIP — Confidential &amp; Restricted</div>
    </div>

</div>

</body>
</html>
