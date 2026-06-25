<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <style>
        /* ── Page meta ───────────────────────────────────────── */
        .settings-meta { margin-bottom: 22px; }
        .settings-page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }
        .settings-page-sub {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* ── Accordion section ───────────────────────────────── */
        .settings-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 14px;
            overflow: hidden;
        }

        .settings-section-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            cursor: pointer;
            background: #f9fafb;
            user-select: none;
            transition: background 0.15s;
        }

        .settings-section-hdr:hover { background: #f3f4f6; }

        .settings-section-hdr.is-open {
            border-bottom: 1px solid var(--border);
            background: #f9fafb;
        }

        .settings-section-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .settings-section-label svg { width: 16px; height: 16px; color: var(--text-secondary); }

        .settings-chevron {
            width: 18px;
            height: 18px;
            color: var(--text-secondary);
            transition: transform 0.25s ease;
            flex-shrink: 0;
        }
        .settings-chevron.open { transform: rotate(180deg); }

        .settings-section-body { padding: 24px 24px 28px; display: none; }
        .settings-section-body.open { display: block; }

        /* ── Profile layout ──────────────────────────────────── */
        .profile-layout {
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }

        /* Avatar */
        .avatar-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            width: 110px;
        }

        .avatar-circle {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cfip-blue), var(--cfip-green));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: #fff;
            font-size: 34px;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .avatar-pencil {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: default;
            transition: all 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        .avatar-pencil svg { width: 12px; height: 12px; }

        .role-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-secondary);
            text-align: center;
            line-height: 1.3;
        }

        /* Form */
        .profile-form { flex: 1; min-width: 0; }

        .settings-field { margin-bottom: 14px; }

        .settings-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 5px;
            letter-spacing: 0.02em;
        }

        .settings-input {
            width: 100%;
            padding: 9px 13px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
            color: var(--text-secondary);
            background: #f3f4f6;
            outline: none;
            transition: border-color 0.2s, background 0.2s, color 0.2s;
        }

        .settings-input.editable {
            background: var(--bg-card);
            color: var(--text-primary);
            border-color: var(--cfip-blue);
        }

        .settings-input.editable:focus { box-shadow: 0 0 0 3px rgba(26,79,168,0.1); }

        .settings-input[disabled] {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .field-hint {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Buttons */
        .form-buttons { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }

        .btn-primary {
            padding: 9px 18px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 700;
            font-family: inherit;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #163d84; }

        .btn-secondary {
            padding: 9px 18px;
            background: transparent;
            color: var(--text-primary);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 700;
            font-family: inherit;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-secondary:hover { background: var(--bg-main); }

        .btn-danger {
            padding: 9px 18px;
            background: transparent;
            color: #ef4444;
            border: 1.5px solid #fca5a5;
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 700;
            font-family: inherit;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-danger:hover { background: #fee2e2; }

        /* Password sub-section */
        .pw-section {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: 20px;
            display: none;
        }
        .pw-section.open { display: block; }
        .pw-section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
        }

        /* Alert messages */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .alert svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* ── System Preferences ──────────────────────────────── */
        .pref-group { margin-bottom: 22px; }
        .pref-group:last-child { margin-bottom: 0; }

        .pref-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .pref-hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; }

        .pref-select {
            width: 220px;
            padding: 9px 12px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card);
            outline: none;
            cursor: pointer;
        }

        .pref-sep { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

        .pref-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        /* Toggle switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #d1d5db;
            border-radius: 24px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .toggle-switch input:checked + .toggle-slider { background: var(--cfip-blue); }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

        /* ── Section open indicator ──────────────────────────── */
        .settings-section-hdr.is-open {
            border-left: 3px solid var(--cfip-blue);
            padding-left: 19px;
        }

        /* ── Dark mode overrides (settings-specific) ─────────── */
        html.dark-mode .settings-section-hdr { background: #162032; }
        html.dark-mode .settings-section-hdr:hover { background: #1c2b3e; }
        html.dark-mode .settings-section-hdr.is-open { border-color: var(--cfip-blue); }
        html.dark-mode .settings-input { background: #162032; color: var(--text-secondary); }
        html.dark-mode .settings-input.editable { background: var(--bg-card); color: var(--text-primary); }
        html.dark-mode .settings-input[disabled] { background: #0f1923; }
        html.dark-mode .btn-secondary { background: transparent; border-color: var(--border); color: var(--text-primary); }
        html.dark-mode .btn-secondary:hover { background: var(--bg-main); }
        html.dark-mode .btn-danger { border-color: #7f1d1d; color: #f87171; }
        html.dark-mode .btn-danger:hover { background: #1f0a0a; }
        html.dark-mode .pref-select { background: var(--bg-card); color: var(--text-primary); }
        html.dark-mode .toggle-slider { background: #334155; }
        html.dark-mode .alert-success { background: #064e3b; color: #6ee7b7; border-color: #065f46; }
        html.dark-mode .alert-error   { background: #450a0a; color: #f87171; border-color: #7f1d1d; }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

{{-- Sidebar: learner gets its own, admin/PC share the same --}}
@if(Auth::user()->role === 'L')
    @include('partials.learner-sidebar')
@else
    @include('partials.sidebar')
@endif

{{-- MAIN --}}
<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">Settings</span>
        </div>
        <div class="topbar-right">
            @include('partials.api-dot')
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- Flash alerts --}}
        @if(session('profile_success'))
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('profile_success') }}
            </div>
        @endif
        @if(session('password_success'))
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('password_success') }}
            </div>
        @endif
        @if($errors->has('current_password'))
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $errors->first('current_password') }}
            </div>
        @endif
        @if($errors->has('user_id'))
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $errors->first('user_id') }}
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════
             SECTION 1 — Profile Settings
        ══════════════════════════════════════════════════ --}}
        <div class="settings-section">
            <div class="settings-section-hdr is-open" id="profile-hdr" onclick="toggleSection('profile')">
                <div class="settings-section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    Profile Settings
                </div>
                <svg class="settings-chevron open" id="profile-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>

            <div class="settings-section-body open" id="profile-body">

                <div class="profile-layout">

                    {{-- Avatar --}}
                    <div class="avatar-area">
                        <div class="avatar-circle">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                            <div class="avatar-pencil" title="Photo upload coming soon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </div>
                        </div>
                        <span class="role-label">
                            @php
                                echo match(Auth::user()->role) {
                                    'A'  => 'Administrator',
                                    'PC' => "Program\nCoordinator",
                                    'L'  => 'Student',
                                    default => Auth::user()->role,
                                };
                            @endphp
                        </span>
                    </div>

                    {{-- Profile form --}}
                    <form class="profile-form" id="profileForm"
                          method="POST" action="{{ route('settings.profile') }}">
                        @csrf

                        <div class="settings-field">
                            <label class="settings-label">Full Name</label>
                            <input type="text" name="name" id="nameInput"
                                   value="{{ old('name', $user->name) }}"
                                   class="settings-input" readonly>
                        </div>

                        <div class="settings-field">
                            <label class="settings-label">Username <span style="font-size:10px;font-weight:400;color:var(--text-muted)">(login ID)</span></label>
                            <input type="text" name="user_id" id="usernameInput"
                                   value="{{ old('user_id', $user->user_id) }}"
                                   class="settings-input" readonly
                                   oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,'')">
                            <span class="field-hint" id="usernameHint">This is used to log in to the system.</span>
                            <span class="field-hint" id="usernameWarning" style="display:none;color:#d97706;font-weight:600">
                                ⚠ Changing your username will change your login ID. You must use the new username to sign in next time.
                            </span>
                        </div>

                        <div class="settings-field">
                            <label class="settings-label">Email</label>
                            <input type="email" name="email" id="emailInput"
                                   value="{{ old('email', $user->email ?? '') }}"
                                   class="settings-input" readonly>
                        </div>

                        <div class="form-buttons" id="viewButtons">
                            <button type="button" class="btn-primary" onclick="startEdit()">Edit Profile</button>
                            <button type="button" class="btn-secondary" onclick="openPasswordSection()">Change Password</button>
                        </div>

                        <div class="form-buttons" id="editButtons" style="display:none">
                            <button type="submit" class="btn-primary">Save Changes</button>
                            <button type="button" class="btn-secondary" onclick="cancelEdit()">Cancel</button>
                        </div>
                    </form>
                </div>

                {{-- ── Change Password sub-section ── --}}
                <div class="pw-section" id="pwSection">
                    <div class="pw-section-title">Change Password</div>
                    <form method="POST" action="{{ route('settings.password') }}" style="max-width:420px">
                        @csrf
                        <div class="settings-field">
                            <label class="settings-label">Current Password</label>
                            <input type="password" name="current_password"
                                   class="settings-input editable"
                                   placeholder="Enter current password" autocomplete="current-password">
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">New Password</label>
                            <input type="password" name="new_password"
                                   class="settings-input editable"
                                   placeholder="Min. 8 characters" autocomplete="new-password">
                        </div>
                        <div class="settings-field">
                            <label class="settings-label">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation"
                                   class="settings-input editable"
                                   placeholder="Repeat new password" autocomplete="new-password">
                        </div>
                        <div class="form-buttons">
                            <button type="submit" class="btn-primary">Update Password</button>
                            <button type="button" class="btn-secondary" onclick="closePasswordSection()">Cancel</button>
                        </div>
                    </form>
                </div>

            </div>{{-- /profile-body --}}
        </div>{{-- /settings-section --}}


        {{-- ══════════════════════════════════════════════════
             SECTION 2 — System Preferences
        ══════════════════════════════════════════════════ --}}
        <div class="settings-section">
            <div class="settings-section-hdr" id="prefs-hdr" onclick="toggleSection('prefs')">
                <div class="settings-section-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    System Preferences
                </div>
                <svg class="settings-chevron" id="prefs-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>

            <div class="settings-section-body" id="prefs-body">

                {{-- Data Refresh Interval --}}
                <div class="pref-group">
                    <label class="pref-label">Data Refresh Interval</label>
                    <select class="pref-select" id="refreshInterval" onchange="saveRefreshPref(this.value)">
                        <option value="15">Every 15 minutes</option>
                        <option value="30" selected>Every 30 minutes</option>
                        <option value="60">Every hour</option>
                        <option value="0">Never</option>
                    </select>
                    <p class="pref-hint">How often the dashboard data should automatically refresh</p>
                </div>

                <hr class="pref-sep">

                {{-- Dark mode --}}
                <div class="pref-group">
                    <div class="pref-row">
                        <div>
                            <label class="pref-label" style="margin-bottom:3px">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     style="width:14px;height:14px;vertical-align:middle;margin-right:4px;margin-top:-2px">
                                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                                </svg>
                                Dark Mode
                            </label>
                            <p class="pref-hint">Toggle between light and dark theme</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="darkModeToggle" onchange="applyDarkMode(this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                @if(Auth::user()->role === 'A')
                <hr class="pref-sep">

                {{-- Auto Sync (admin only) --}}
                <div class="pref-group">
                    <div class="pref-row">
                        <div>
                            <label class="pref-label" style="margin-bottom:3px">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     style="width:14px;height:14px;vertical-align:middle;margin-right:4px;margin-top:-2px">
                                    <polyline points="23 4 23 10 17 10"/>
                                    <polyline points="1 20 1 14 7 14"/>
                                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                                </svg>
                                Auto Sync on Page Load
                                <span style="margin-left:6px;font-size:9px;font-weight:700;letter-spacing:.08em;background:#dbeafe;color:#1d4ed8;padding:2px 7px;border-radius:20px;vertical-align:middle;text-transform:uppercase">Admin</span>
                            </label>
                            <p class="pref-hint">When enabled, iSpring data syncs from the API each time a page loads. A crime-scene loading screen will appear during the sync.</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="autoSyncToggle"
                                   onchange="saveAutoSyncPref(this.checked)"
                                   {{ $autoSyncEnabled ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div id="autoSyncStatus" style="display:none;margin-top:8px;font-size:11px;color:#6b7280"></div>
                </div>
                @endif

            </div>{{-- /prefs-body --}}
        </div>{{-- /settings-section --}}

    </div>{{-- /content --}}
</div>{{-- /main --}}


<script>
// ── Accordion toggle ──────────────────────────────────────
function toggleSection(id) {
    const body    = document.getElementById(id + '-body');
    const chevron = document.getElementById(id + '-chevron');
    const hdr     = document.getElementById(id + '-hdr');
    const isOpen  = body.classList.contains('open');

    body.classList.toggle('open', !isOpen);
    chevron.classList.toggle('open', !isOpen);
    hdr.classList.toggle('is-open', !isOpen);
}

// ── Profile edit mode ─────────────────────────────────────
const editableInputs = ['nameInput', 'usernameInput', 'emailInput'];

function startEdit() {
    editableInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.removeAttribute('readonly'); el.classList.add('editable'); }
    });
    document.getElementById('viewButtons').style.display  = 'none';
    document.getElementById('editButtons').style.display  = 'flex';
    document.getElementById('usernameWarning').style.display = 'inline';
    document.getElementById('usernameHint').style.display    = 'none';
    document.getElementById('nameInput')?.focus();
}

function cancelEdit() {
    editableInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.setAttribute('readonly', ''); el.classList.remove('editable'); }
    });
    // Reset username to original value in case it was changed
    const usernameEl = document.getElementById('usernameInput');
    if (usernameEl) usernameEl.value = '{{ $user->user_id }}';
    document.getElementById('viewButtons').style.display    = 'flex';
    document.getElementById('editButtons').style.display    = 'none';
    document.getElementById('usernameWarning').style.display = 'none';
    document.getElementById('usernameHint').style.display    = 'inline';
}

// ── Change password sub-section ───────────────────────────
function openPasswordSection() {
    const sec = document.getElementById('pwSection');
    sec.classList.add('open');
    sec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function closePasswordSection() {
    document.getElementById('pwSection').classList.remove('open');
}

// ── Dark mode ─────────────────────────────────────────────
function applyDarkMode(on) {
    document.documentElement.classList.toggle('dark-mode', on);
    localStorage.darkMode = on ? 'on' : 'off';
}

// ── Refresh interval (localStorage only for now) ──────────
function saveRefreshPref(val) {
    localStorage.refreshInterval = val;
}

// ── Auto Sync (admin only — persisted server-side) ────────
async function saveAutoSyncPref(enabled) {
    const statusEl = document.getElementById('autoSyncStatus');
    if (statusEl) { statusEl.style.display = 'block'; statusEl.textContent = 'Saving…'; }

    try {
        const res = await fetch('{{ route("api.settings.sync") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ auto_sync: enabled }),
        });
        const data = await res.json();
        if (data.success && statusEl) {
            statusEl.textContent = enabled ? 'Auto sync enabled — syncs will run on every page load.' : 'Auto sync disabled.';
            setTimeout(() => { if (statusEl) statusEl.style.display = 'none'; }, 3000);
        }
    } catch (e) {
        if (statusEl) { statusEl.textContent = 'Failed to save. Please try again.'; statusEl.style.color = '#dc2626'; }
    }
}

// ── Init on page load ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Restore dark mode toggle state
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) toggle.checked = (localStorage.darkMode === 'on');

    // Restore refresh interval
    const saved = localStorage.refreshInterval;
    if (saved) {
        const sel = document.getElementById('refreshInterval');
        if (sel) {
            const opt = [...sel.options].find(o => o.value === saved);
            if (opt) opt.selected = true;
        }
    }

    // Open password section if there were validation errors from the password form
    @if($errors->hasAny(['current_password', 'new_password']))
        openPasswordSection();
    @endif

    // If profile validation failed, re-enter edit mode so the user sees the error
    @if($errors->hasAny(['name', 'email', 'user_id']))
        startEdit();
    @endif
});
</script>

@include('partials.api-status')
</body>
</html>
