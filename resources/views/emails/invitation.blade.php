<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CFIP Invitation</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Poppins', Arial, sans-serif;
        background: #f0f4fb;
        color: #1a1f36;
    }
    .wrapper {
        max-width: 560px;
        margin: 40px auto;
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    /* Header */
    .header {
        background: linear-gradient(135deg, #1a2e5a 0%, #1a4fa8 100%);
        padding: 32px 36px 28px;
        text-align: center;
    }
    .header-badge {
        display: inline-block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.65);
        background: rgba(255,255,255,0.12);
        border-radius: 20px;
        padding: 4px 12px;
        margin-bottom: 14px;
    }
    .header h1 {
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        line-height: 1.3;
    }
    .header p {
        font-size: 13px;
        color: rgba(255,255,255,0.72);
        margin-top: 6px;
    }

    /* Body */
    .body { padding: 32px 36px; }

    .greeting {
        font-size: 15px;
        color: #1a1f36;
        margin-bottom: 12px;
        line-height: 1.6;
    }

    .notice-box {
        background: #eff6ff;
        border-left: 4px solid #1a4fa8;
        border-radius: 0 8px 8px 0;
        padding: 12px 16px;
        font-size: 13px;
        color: #1d4ed8;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    /* Credential table */
    .cred-table {
        background: #f8fafc;
        border: 1px solid #e5e9f5;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .cred-table-header {
        background: #1a4fa8;
        padding: 10px 16px;
        font-size: 11px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .cred-row {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e9f5;
    }
    .cred-row:last-child { border-bottom: none; }
    .cred-label {
        font-size: 11px;
        color: #6b7280;
        font-weight: 600;
        min-width: 90px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .cred-value {
        font-size: 15px;
        font-weight: 700;
        color: #1a1f36;
        font-family: 'Courier New', monospace;
        letter-spacing: 0.04em;
    }

    /* Login button */
    .btn-wrap { text-align: center; margin-bottom: 24px; }
    .login-btn {
        display: inline-block;
        background: linear-gradient(135deg, #3a85d4, #1e5faf);
        color: #ffffff !important;
        text-decoration: none;
        padding: 13px 36px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .url-fallback {
        font-size: 12px;
        color: #6b7280;
        text-align: center;
        margin-bottom: 24px;
        line-height: 1.6;
    }
    .url-fallback a { color: #1a4fa8; word-break: break-all; }

    /* Warning box */
    .warning-box {
        background: #fef9c3;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 12.5px;
        color: #854d0e;
        line-height: 1.6;
        margin-bottom: 8px;
    }

    /* Footer */
    .footer {
        border-top: 1px solid #e5e9f5;
        padding: 20px 36px;
        text-align: center;
    }
    .footer p {
        font-size: 11.5px;
        color: #9ca3af;
        line-height: 1.7;
    }
    .footer .confidential {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #d1d5db;
        margin-top: 8px;
    }
</style>
</head>
<body>

<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-badge">Certified Financial Investigator Programme</div>
        <h1>You've Been Invited</h1>
        <p>Your account has been set up on the CFIP System</p>
    </div>

    {{-- Body --}}
    <div class="body">

        <p class="greeting">
            Dear <strong>{{ $recipientName }}</strong>,<br><br>
            An administrator has created a login account for you on the
            <strong>CFIP System</strong> — the Certified Financial Investigator Programme
            Learning Analytics Platform. Please use the credentials below to sign in.
        </p>

        <div class="notice-box">
            You will be asked to <strong>set a new password</strong> immediately after your first login.
            The temporary password below is for one-time use only.
        </div>

        {{-- Credential box --}}
        <div class="cred-table">
            <div class="cred-table-header">Your Login Credentials</div>
            <div class="cred-row">
                <span class="cred-label">Username</span>
                <span class="cred-value">{{ $username }}</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Password</span>
                <span class="cred-value">{{ $plainPassword }}</span>
            </div>
        </div>

        {{-- Login button --}}
        <div class="btn-wrap">
            <a href="{{ $loginUrl }}" class="login-btn">Sign In to CFIP System</a>
        </div>

        <p class="url-fallback">
            If the button doesn't work, copy this link into your browser:<br>
            <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
        </p>

        <div class="warning-box">
            <strong>Important:</strong> Keep these credentials private. Do not share your username or password with anyone.
            Once you set your new password, this temporary password will no longer work.
        </div>

    </div>{{-- /body --}}

    {{-- Footer --}}
    <div class="footer">
        <p>
            This is an automated message from the CFIP System.<br>
            If you did not expect this email, please contact your programme administrator.
        </p>
        <div class="confidential">CFIP — Confidential</div>
    </div>

</div>

</body>
</html>
