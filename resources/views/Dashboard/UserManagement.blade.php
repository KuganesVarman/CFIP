<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Management | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <style>
        /* ── Toolbar ────────────────────────────────────────── */
        .um-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .um-search-wrap {
            position: relative;
            flex: 0 0 240px;
        }
        .um-search-wrap svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px; height: 14px;
            color: var(--text-muted);
            pointer-events: none;
        }
        .um-search {
            width: 100%;
            padding: 7px 12px 7px 32px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card);
            outline: none;
            transition: border-color 0.2s;
        }
        .um-search:focus { border-color: var(--cfip-blue); }
        .um-search::placeholder { color: var(--text-muted); }

        .um-add-btn {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .um-add-btn:hover { opacity: 0.88; }
        .um-add-btn svg { width: 14px; height: 14px; }

        /* ── Collapsible section header ──────────────────── */
        .um-collapsible-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            user-select: none;
            margin-bottom: 4px;
            transition: background 0.15s;
            gap: 10px;
        }
        .um-collapsible-header:hover { background: rgba(79,110,247,0.04); }

        /* ── Summary pills ────────────────────────────────── */
        .um-summary {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .um-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .um-pill.total  { background: var(--cfip-blue-light); color: var(--cfip-blue); }
        .um-pill.learner{ background: #e0f2fe; color: #0369a1; }
        .um-pill.pc     { background: #f0fdf4; color: #15803d; }

        .um-count {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 8px;
            padding-left: 2px;
        }

        /* ── Table card ───────────────────────────────────── */
        .um-card {
            background: var(--bg-card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .um-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.855rem;
        }
        .um-table thead tr { border-bottom: 1px solid var(--border); }
        .um-table th {
            padding: 0.85rem 1.2rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
            background: var(--bg-card);
        }
        .um-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }
        .um-table tbody tr:last-child { border-bottom: none; }
        .um-table tbody tr:hover { background: rgba(79,110,247,0.03); }
        .um-table td { padding: 0.85rem 1.2rem; }

        /* ── User cell ────────────────────────────────────── */
        .um-user-cell { display: flex; align-items: center; gap: 10px; }
        .um-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--cfip-blue);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            letter-spacing: 0.03em;
        }
        .um-avatar.pc { background: #15803d; }
        .um-name  { font-weight: 600; font-size: 13px; color: var(--text-primary); }
        .um-email { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        /* ── Role badge / toggle ──────────────────────────── */
        .um-role-select {
            border: 1.5px solid var(--border);
            border-radius: 6px;
            padding: 4px 24px 4px 8px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 600;
            color: var(--text-primary);
            background: var(--bg-card)
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
                no-repeat right 5px center / 12px 12px;
            appearance: none;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        .um-role-select:focus { border-color: var(--cfip-blue); }
        .um-role-select.learner { color: #0369a1; border-color: #bae6fd; background-color: #e0f2fe; }
        .um-role-select.pc      { color: #15803d; border-color: #bbf7d0; background-color: #f0fdf4; }

        /* ── Status badge ─────────────────────────────────── */
        .um-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 5px;
        }
        .um-status-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .um-status.invited        { background: #fef9c3; color: #854d0e; }
        .um-status.invited .um-status-dot { background: #ca8a04; }
        .um-status.active         { background: #dcfce7; color: #15803d; }
        .um-status.active .um-status-dot  { background: #16a34a; }
        .um-status.pending        { background: #f3f4f6; color: #6b7280; }
        .um-status.pending .um-status-dot { background: #9ca3af; }

        /* ── Action buttons ───────────────────────────────── */
        .um-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            transition: opacity 0.15s;
        }
        .um-action-btn:hover { opacity: 0.82; }
        .um-action-btn svg { width: 13px; height: 13px; }

        .um-action-btn.invite {
            background: var(--cfip-blue-light);
            color: var(--cfip-blue);
        }
        .um-action-btn.reinvite {
            background: #fef9c3;
            color: #854d0e;
        }
        .um-action-btn.delete {
            background: #fef2f2;
            color: #991b1b;
        }
        .um-actions-cell { display: flex; align-items: center; gap: 6px; }

        /* ── Empty state ──────────────────────────────────── */
        .um-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* ── MODAL BACKDROP ──────────────────────────────── */
        .cfip-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(3px);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .cfip-modal-backdrop.open { display: flex; }

        .cfip-modal {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.18);
            width: 100%;
            max-width: 480px;
            padding: 0;
            overflow: hidden;
            animation: modalIn 0.22s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(16px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .cfip-modal-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cfip-modal-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .cfip-modal-close {
            width: 28px; height: 28px;
            border-radius: 7px;
            border: none;
            background: var(--bg-main);
            color: var(--text-muted);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .cfip-modal-close:hover { background: #e5e9f5; color: var(--text-primary); }
        .cfip-modal-close svg { width: 14px; height: 14px; }

        .cfip-modal-body { padding: 1.5rem; }
        .cfip-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        /* ── Add-user form ────────────────────────────────── */
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card);
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: var(--cfip-blue); }
        .form-group .form-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .btn-primary {
            padding: 8px 20px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .btn-primary:hover { opacity: 0.88; }

        .btn-ghost {
            padding: 8px 16px;
            background: transparent;
            color: var(--text-secondary);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-ghost:hover { background: var(--bg-main); }

        /* ── Credential card (shown in credential modal) ─── */
        .cred-card {
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
        }
        .cred-card-header {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 0.75rem;
        }
        .cred-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid var(--border);
        }
        .cred-row:last-child { border-bottom: none; }
        .cred-label {
            font-size: 11px;
            color: var(--text-muted);
            min-width: 80px;
        }
        .cred-value {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Courier New', monospace;
            flex: 1;
            padding: 0 10px;
        }
        .cred-copy-btn {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: var(--cfip-blue-light);
            color: var(--cfip-blue);
            border: none;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: opacity 0.15s;
        }
        .cred-copy-btn:hover { opacity: 0.8; }
        .cred-copy-btn svg { width: 11px; height: 11px; }

        .cred-notice {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            background: #fef9c3;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            color: #854d0e;
            line-height: 1.5;
        }
        .cred-notice svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }

        .cred-login-link {
            margin-top: 10px;
            font-size: 12px;
            color: var(--text-muted);
            word-break: break-all;
        }
        .cred-login-link a {
            color: var(--cfip-blue);
            font-weight: 600;
            text-decoration: none;
        }
        .cred-login-link a:hover { text-decoration: underline; }

        /* ── Populate emails safety button ───────────────── */
        .sync-emails-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #fffbeb;
            color: #92400e;
            border: 1.5px solid #fcd34d;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            white-space: nowrap;
        }
        .sync-emails-btn:hover { background: #fef3c7; border-color: #f59e0b; }
        .sync-emails-btn.done  { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; cursor: default; }
        .sync-emails-btn svg   { width: 14px; height: 14px; flex-shrink: 0; }

        /* ── Bulk cohort section ──────────────────────────── */
        .bulk-card {
            background: var(--bg-card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-top: 24px;
        }
        .bulk-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
            gap: 10px;
        }
        .bulk-card-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bulk-card-title svg { width: 16px; height: 16px; color: var(--cfip-blue); }
        .bulk-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bulk-cohort-select {
            padding: 6px 28px 6px 10px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card)
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
                no-repeat right 5px center / 12px 12px;
            appearance: none;
            outline: none;
            min-width: 200px;
        }
        .bulk-cohort-select:focus { border-color: var(--cfip-blue); }
        .bulk-load-btn {
            padding: 6px 14px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .bulk-load-btn:hover { opacity: 0.88; }

        .bulk-summary-bar {
            display: flex;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-main);
            font-size: 12px;
            flex-wrap: wrap;
        }
        .bulk-stat { display: flex; align-items: center; gap: 5px; }
        .bulk-stat-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .bulk-stat.total  .bulk-stat-dot { background: var(--cfip-blue); }
        .bulk-stat.new    .bulk-stat-dot { background: #16a34a; }
        .bulk-stat.exists .bulk-stat-dot { background: #9ca3af; }
        .bulk-stat strong { font-weight: 700; color: var(--text-primary); }
        .bulk-stat span   { color: var(--text-muted); }

        .bulk-table-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 20px;
            border-bottom: 1px solid var(--border);
        }
        .bulk-select-links { display: flex; gap: 10px; }
        .bulk-select-link {
            font-size: 11px;
            font-weight: 600;
            color: var(--cfip-blue);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            font-family: inherit;
        }
        .bulk-select-link:hover { text-decoration: underline; }
        .bulk-selected-label {
            font-size: 11px;
            color: var(--text-muted);
        }
        .bulk-selected-label strong { color: var(--text-primary); }

        .bulk-table-wrap {
            max-height: 400px;
            overflow-y: auto;
        }
        .bulk-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .bulk-table th {
            padding: 8px 14px;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .bulk-table td {
            padding: 8px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .bulk-table tbody tr:last-child td { border-bottom: none; }
        .bulk-table tbody tr.has-account { opacity: 0.5; }
        .bulk-table tbody tr:hover { background: rgba(79,110,247,0.03); }

        .bulk-cb { width: 15px; height: 15px; cursor: pointer; accent-color: var(--cfip-blue); }

        .bulk-user-cell { display: flex; align-items: center; gap: 8px; }
        .bulk-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--cfip-blue);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .bulk-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 4px;
        }
        .bulk-badge.new     { background: #dcfce7; color: #15803d; }
        .bulk-badge.exists  { background: #f3f4f6; color: #6b7280; }

        .bulk-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            border-top: 1px solid var(--border);
        }
        .bulk-send-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .bulk-send-btn:hover  { opacity: 0.88; }
        .bulk-send-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .bulk-send-btn svg { width: 13px; height: 13px; }

        .bulk-loading-state,
        .bulk-empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* ── Bulk results card ────────────────────────────── */
        .bulk-results {
            margin: 0 20px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }
        .bulk-results-header {
            background: #f0fdf4;
            border-bottom: 1px solid #bbf7d0;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 700;
            color: #15803d;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .bulk-results-header svg { width: 14px; height: 14px; }
        .bulk-results-body { padding: 12px 16px; }
        .bulk-results-stats { display: flex; gap: 20px; margin-bottom: 12px; }
        .bulk-result-stat { font-size: 12px; color: var(--text-secondary); }
        .bulk-result-stat strong { font-size: 18px; font-weight: 700; color: var(--text-primary); display: block; }
        .bulk-csv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: var(--cfip-blue-light);
            color: var(--cfip-blue);
            border: 1px solid #bfdbfe;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .bulk-csv-btn:hover { opacity: 0.82; }
        .bulk-csv-btn svg { width: 13px; height: 13px; }

        /* ── Email toggle (topbar) ───────────────────────── */
        .email-toggle-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--bg-card);
            cursor: pointer;
            user-select: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .email-toggle-wrap:hover { border-color: #cbd5e1; }
        .email-toggle-wrap.active {
            border-color: #16a34a;
            background: #f0fdf4;
        }

        .email-toggle-icon svg { width: 15px; height: 15px; color: var(--text-muted); }
        .email-toggle-wrap.active .email-toggle-icon svg { color: #16a34a; }

        .email-toggle-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            white-space: nowrap;
        }
        .email-toggle-wrap.active .email-toggle-label { color: #15803d; }

        /* iOS-style pill switch */
        .toggle-switch {
            position: relative;
            width: 34px;
            height: 18px;
            flex-shrink: 0;
        }
        .toggle-switch input { display: none; }
        .toggle-track {
            position: absolute;
            inset: 0;
            border-radius: 18px;
            background: #d1d5db;
            transition: background 0.2s;
        }
        .toggle-switch input:checked + .toggle-track { background: #16a34a; }
        .toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: transform 0.2s;
        }
        .toggle-switch input:checked ~ .toggle-thumb { transform: translateX(16px); }

        /* ── Toast notification ───────────────────────────── */
        .cfip-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1100;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,0.14);
            animation: toastIn 0.25s ease;
            pointer-events: none;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .cfip-toast.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .cfip-toast.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .cfip-toast.info    { background: var(--cfip-blue-light); color: var(--cfip-blue); border: 1px solid #bfdbfe; }
        .cfip-toast svg { width: 15px; height: 15px; flex-shrink: 0; }

        /* ── Spinner ──────────────────────────────────────── */
        .btn-spinner { display: none; width: 13px; height: 13px; }
        .btn-spinner.loading { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-spinner circle {
            animation: spin 0.7s linear infinite;
            transform-origin: center;
        }
    </style>
</head>
<body>

@include('partials.sync-loading')
@include('partials.sidebar')

<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">User Management</span>
        </div>
        <div class="topbar-right">
            @include('partials.api-dot')

            {{-- Populate Emails safety button --}}
            <button class="sync-emails-btn" id="syncEmailsBtn" onclick="openSyncEmailsModal()" title="Fetch and store learner email addresses from iSpring">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Populate Emails
            </button>

            {{-- Email sending toggle --}}
            <label class="email-toggle-wrap" id="emailToggleWrap" title="Toggle email sending">
                <span class="email-toggle-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </span>
                <span class="email-toggle-label" id="emailToggleLabel">Email off</span>
                <span class="toggle-switch">
                    <input type="checkbox" id="emailToggleInput" onchange="onEmailToggle(this)">
                    <span class="toggle-track"></span>
                    <span class="toggle-thumb"></span>
                </span>
            </label>

            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($authUser->name, 0, 1)) }}</div>
                <span>{{ $authUser->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        @php
            $learnerCount = $users->where('role', 'L')->count();
            $pcCount      = $users->where('role', 'PC')->count();
        @endphp

        {{-- Collapsible user table section --}}
        <div class="um-collapsible-header" id="umCollapseHeader" onclick="toggleUserTable()" title="Click to expand/collapse">
            <div style="display:flex;align-items:center;gap:10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;color:var(--cfip-blue)">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
                <span style="font-size:13px;font-weight:700;color:var(--text-primary)">System Users</span>
                <div class="um-summary" style="margin:0">
                    <span class="um-pill total">{{ $users->count() }} total</span>
                    @if($learnerCount)<span class="um-pill learner">{{ $learnerCount }} Learner{{ $learnerCount !== 1 ? 's' : '' }}</span>@endif
                    @if($pcCount)<span class="um-pill pc">{{ $pcCount }} PC</span>@endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <button class="um-add-btn" style="margin-left:0" onclick="event.stopPropagation();openAddModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add User
                </button>
                <svg id="umCollapseChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     style="width:16px;height:16px;color:var(--text-muted);transition:transform 0.25s;transform:rotate(-90deg)">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </div>
        </div>

        {{-- Collapsible body (closed by default) --}}
        <div id="umCollapseBody" style="display:none">
            {{-- Toolbar --}}
            <div class="um-toolbar" style="margin-top:10px">
                <div class="um-search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" class="um-search" id="umSearch" placeholder="Search by name or username…" oninput="filterUsers()">
                </div>
            </div>

            {{-- Row count --}}
            <div class="um-count" id="umCount">{{ $users->count() }} {{ $users->count() === 1 ? 'user' : 'users' }}</div>

        {{-- Table --}}
        <div class="um-card">
            <table class="um-table">
                <thead>
                    <tr>
                        <th style="width:35%">User</th>
                        <th style="width:20%">Username</th>
                        <th style="width:14%">Role</th>
                        <th style="width:14%">Status</th>
                        <th style="width:17%">Actions</th>
                    </tr>
                </thead>
                <tbody id="umTableBody">
                    @forelse($users as $u)
                    @php
                        $words    = array_slice(explode(' ', trim($u->name)), 0, 2);
                        $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words));
                        $isPC     = $u->role === 'PC';
                        $statusClass = $u->must_change_password ? 'invited' : 'active';
                        $statusLabel = $u->must_change_password ? 'Invited' : 'Active';
                    @endphp
                    <tr data-name="{{ strtolower($u->name) }}" data-uid="{{ strtolower($u->user_id) }}">
                        <td>
                            <div class="um-user-cell">
                                <div class="um-avatar {{ $isPC ? 'pc' : '' }}">{{ $initials }}</div>
                                <div>
                                    <div class="um-name">{{ $u->name }}</div>
                                    <div class="um-email">{{ $u->email ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-family:'Courier New',monospace;font-size:12px;color:var(--text-secondary)">
                            {{ $u->user_id }}
                        </td>
                        <td>
                            <select class="um-role-select {{ $isPC ? 'pc' : 'learner' }}"
                                    onchange="changeRole({{ $u->id }}, this)"
                                    data-user-id="{{ $u->id }}">
                                <option value="L" {{ $u->role === 'L' ? 'selected' : '' }}>Learner</option>
                                <option value="PC" {{ $u->role === 'PC' ? 'selected' : '' }}>Program Coordinator</option>
                            </select>
                        </td>
                        <td>
                            <span class="um-status {{ $statusClass }}">
                                <span class="um-status-dot"></span>
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>
                            <div class="um-actions-cell">
                                <button class="um-action-btn {{ $u->must_change_password ? 'reinvite' : 'invite' }}"
                                        onclick="sendInvitation({{ $u->id }}, '{{ addslashes($u->name) }}', this)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 01.08 1.16 2 2 0 012.03 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/>
                                    </svg>
                                    {{ $u->must_change_password ? 'Re-invite' : 'Send Invitation' }}
                                </button>
                                <button class="um-action-btn delete"
                                        onclick="confirmDeleteUser({{ $u->id }}, '{{ addslashes($u->name) }}', this)"
                                        title="Remove user">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="um-empty">No users yet. Click <strong>Add User</strong> to get started.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>{{-- /umCollapseBody --}}

        {{-- ─── BULK COHORT INVITATION ─────────────────────────── --}}
        <div class="bulk-card">
            <div class="bulk-card-header">
                <div class="bulk-card-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    Bulk Cohort Invitation
                </div>
                <div class="bulk-controls">
                    <select class="bulk-cohort-select" id="bulkCohortSelect">
                        <option value="">— Select a cohort —</option>
                        @foreach($cohorts as $cohort)
                            <option value="{{ $cohort->group_id }}">{{ $cohort->name }}</option>
                        @endforeach
                    </select>
                    <button class="bulk-load-btn" onclick="loadCohortLearners()">Load Learners</button>
                </div>
            </div>

            {{-- Loading state --}}
            <div id="bulkLoadingState" class="bulk-loading-state" style="display:none">
                Loading learners…
            </div>

            {{-- Empty cohort --}}
            <div id="bulkEmptyState" class="bulk-empty-state" style="display:none">
                No learners found in this cohort.
            </div>

            {{-- Learner panel --}}
            <div id="bulkPanel" style="display:none">

                {{-- Results (shown after sending) --}}
                <div id="bulkResults" style="display:none" class="bulk-results" style="margin-top:0">
                    <div class="bulk-results-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Bulk invitation complete
                    </div>
                    <div class="bulk-results-body">
                        <div class="bulk-results-stats">
                            <div class="bulk-result-stat">
                                <strong id="bulkResultCreated">0</strong>
                                Accounts created
                            </div>
                            <div class="bulk-result-stat">
                                <strong id="bulkResultSkipped">0</strong>
                                Already existed (skipped)
                            </div>
                        </div>
                        <button class="bulk-csv-btn" id="bulkCsvBtn" onclick="downloadCredentialsCSV()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download Credentials CSV
                        </button>
                        <span style="font-size:11px;color:var(--text-muted);margin-left:10px">
                            Contains: Name, Username, Password for each new account
                        </span>
                    </div>
                </div>

                {{-- Summary bar --}}
                <div class="bulk-summary-bar">
                    <div class="bulk-stat total">
                        <span class="bulk-stat-dot"></span>
                        <strong id="bulkStatTotal">0</strong>
                        <span>found in cohort</span>
                    </div>
                    <div class="bulk-stat new">
                        <span class="bulk-stat-dot"></span>
                        <strong id="bulkStatNew">0</strong>
                        <span>will get new accounts</span>
                    </div>
                    <div class="bulk-stat exists">
                        <span class="bulk-stat-dot"></span>
                        <strong id="bulkStatExists">0</strong>
                        <span>already have accounts</span>
                    </div>
                </div>

                {{-- Select all / none --}}
                <div class="bulk-table-actions">
                    <div class="bulk-select-links">
                        <button class="bulk-select-link" onclick="bulkSelectAll(true)">Select all new</button>
                        <button class="bulk-select-link" onclick="bulkSelectAll(false)">Deselect all</button>
                    </div>
                    <span class="bulk-selected-label"><strong id="bulkSelCount">0</strong> selected</span>
                </div>

                {{-- Learner table --}}
                <div class="bulk-table-wrap">
                    <table class="bulk-table">
                        <thead>
                            <tr>
                                <th style="width:40px"></th>
                                <th>Name</th>
                                <th>Department</th>
                                <th style="width:110px">Status</th>
                            </tr>
                        </thead>
                        <tbody id="bulkTableBody"></tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="bulk-footer">
                    <span style="font-size:12px;color:var(--text-muted)">
                        Accounts created without email. Use <strong>Populate Emails</strong> afterwards to fetch emails from iSpring.
                    </span>
                    <button class="bulk-send-btn" id="bulkSendBtn" onclick="confirmBulkInvite()" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"/>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        <span id="bulkSendLabel">Send Invitations</span>
                    </button>
                </div>

            </div>{{-- /bulkPanel --}}
        </div>{{-- /bulk-card --}}

    </div>{{-- /content --}}
</div>{{-- /main --}}

{{-- ═══════════════════════════════════════════
     ADD USER MODAL
═══════════════════════════════════════════ --}}
<div class="cfip-modal-backdrop" id="addModal" onclick="backdropClose(event,'addModal')">
    <div class="cfip-modal">
        <div class="cfip-modal-header">
            <span class="cfip-modal-title">Add New User</span>
            <button class="cfip-modal-close" onclick="closeModal('addModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="cfip-modal-body">
            <div class="form-group">
                <label>Full Name <span style="color:var(--cfip-red)">*</span></label>
                <input type="text" id="addName" placeholder="e.g. Kuganes Varman" maxlength="255">
                <div class="form-hint">Username will be auto-generated: e.g. <strong id="previewUsername">KuganesVarman</strong></div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" id="addEmail" placeholder="e.g. user@agency.gov.my">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Department <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                    <select id="addDepartment">
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="addRole">
                        <option value="L" selected>Learner</option>
                        <option value="PC">Program Coordinator</option>
                    </select>
                </div>
            </div>
            <div id="addFormError" style="display:none;color:var(--cfip-red);font-size:12px;margin-top:-6px;margin-bottom:6px"></div>
        </div>
        <div class="cfip-modal-footer">
            <button class="btn-ghost" onclick="closeModal('addModal')">Cancel</button>
            <button class="btn-primary" id="addSubmitBtn" onclick="submitAddUser()">
                Create &amp; Send Invitation
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     CREDENTIAL MODAL
═══════════════════════════════════════════ --}}
<div class="cfip-modal-backdrop" id="credModal" onclick="backdropClose(event,'credModal')">
    <div class="cfip-modal">
        <div class="cfip-modal-header">
            <span class="cfip-modal-title">Invitation Credentials</span>
            <button class="cfip-modal-close" onclick="closeModal('credModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="cfip-modal-body">
            <p style="font-size:13px;color:var(--text-secondary);margin-bottom:1rem">
                Share these credentials with <strong id="credUserName"></strong>. They will be asked to change their password on first login.
            </p>

            <div class="cred-card">
                <div class="cred-card-header">Login credentials</div>
                <div class="cred-row">
                    <span class="cred-label">Username</span>
                    <span class="cred-value" id="credUsername"></span>
                    <button class="cred-copy-btn" onclick="copyField('credUsername', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Password</span>
                    <span class="cred-value" id="credPassword"></span>
                    <button class="cred-copy-btn" onclick="copyField('credPassword', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <div class="cred-row">
                    <span class="cred-label">Login URL</span>
                    <span class="cred-value" id="credUrl" style="font-size:11px"></span>
                    <button class="cred-copy-btn" onclick="copyField('credUrl', this)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                        Copy
                    </button>
                </div>
            </div>

            <div class="cred-notice">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Email sending is not active yet. Copy the credentials above and share them manually with the user.
            </div>
        </div>
        <div class="cfip-modal-footer">
            <button class="btn-primary" onclick="closeModal('credModal')">Done</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     DELETE USER CONFIRMATION MODAL
═══════════════════════════════════════════ --}}
<div class="cfip-modal-backdrop" id="deleteModal" onclick="backdropClose(event,'deleteModal')">
    <div class="cfip-modal">
        <div class="cfip-modal-header">
            <span class="cfip-modal-title">Remove User</span>
            <button class="cfip-modal-close" onclick="closeModal('deleteModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="cfip-modal-body">
            <p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:14px">
                Are you sure you want to remove <strong id="deleteUserName"></strong>?
                This will permanently delete their account and cannot be undone.
            </p>
            <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;line-height:1.6">
                Their login access will be revoked immediately. If they were created from iSpring,
                they can always be re-invited through Bulk Cohort Invitation.
            </div>
        </div>
        <div class="cfip-modal-footer">
            <button class="btn-ghost" onclick="closeModal('deleteModal')">Cancel</button>
            <button id="deleteConfirmBtn" onclick="runDeleteUser()"
                    style="padding:8px 20px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer">
                Remove User
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     SYNC EMAILS CONFIRMATION MODAL
═══════════════════════════════════════════ --}}
<div class="cfip-modal-backdrop" id="syncEmailsModal" onclick="backdropClose(event,'syncEmailsModal')">
    <div class="cfip-modal">
        <div class="cfip-modal-header">
            <span class="cfip-modal-title">Populate Emails from iSpring</span>
            <button class="cfip-modal-close" onclick="closeModal('syncEmailsModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="cfip-modal-body">
            <p style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:14px">
                This will scan all learner accounts that have an iSpring link but <strong>no email address</strong>,
                then fetch their email from the iSpring database and save it to their account.
            </p>
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;line-height:1.6">
                <strong>One-time action.</strong> Once emails are populated, the Email toggle will
                automatically include them in future invitations. You can run this again after a new
                bulk invite to pick up any newly created accounts.
            </div>
        </div>
        <div class="cfip-modal-footer">
            <button class="btn-ghost" onclick="closeModal('syncEmailsModal')">Cancel</button>
            <button class="btn-primary" id="syncEmailsConfirmBtn" onclick="runSyncEmails()" style="background:#d97706;border-color:#d97706">
                Populate Emails
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════
     BULK INVITE CONFIRMATION MODAL
═══════════════════════════════════════════ --}}
<div class="cfip-modal-backdrop" id="bulkConfirmModal" onclick="backdropClose(event,'bulkConfirmModal')">
    <div class="cfip-modal">
        <div class="cfip-modal-header">
            <span class="cfip-modal-title">Confirm Bulk Invitation</span>
            <button class="cfip-modal-close" onclick="closeModal('bulkConfirmModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="cfip-modal-body">
            <p id="bulkConfirmText" style="font-size:13px;color:var(--text-secondary);line-height:1.6;margin-bottom:14px"></p>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1d4ed8;line-height:1.6">
                Accounts will be created with no email address. Use <strong>Populate Emails</strong> afterwards to fetch emails from iSpring, then enable the Email toggle to send invitation emails.
            </div>
        </div>
        <div class="cfip-modal-footer">
            <button class="btn-ghost" onclick="closeModal('bulkConfirmModal')">Cancel</button>
            <button class="btn-primary" id="bulkConfirmBtn" onclick="runBulkInvite()">
                Create Accounts
            </button>
        </div>
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const LOGIN_URL = '{{ url("/login") }}';
const EMAIL_TOGGLE_KEY = 'cfip_email_enabled';

/* ── Email toggle ─────────────────────────────── */
function emailEnabled() {
    return localStorage.getItem(EMAIL_TOGGLE_KEY) === '1';
}

function onEmailToggle(checkbox) {
    const on = checkbox.checked;
    localStorage.setItem(EMAIL_TOGGLE_KEY, on ? '1' : '0');
    applyEmailToggleUI(on);
    if (on) {
        showToast('info', 'Email sending enabled — invitations will be sent to the user\'s email address.');
    }
}

function applyEmailToggleUI(on) {
    const wrap  = document.getElementById('emailToggleWrap');
    const label = document.getElementById('emailToggleLabel');
    const input = document.getElementById('emailToggleInput');
    wrap.classList.toggle('active', on);
    label.textContent = on ? 'Email on' : 'Email off';
    input.checked = on;
}

/* ── Toast ────────────────────────────────────── */
let toastTimer = null;
function showToast(type, message) {
    const existing = document.querySelector('.cfip-toast');
    if (existing) existing.remove();
    if (toastTimer) clearTimeout(toastTimer);

    const icons = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>',
        error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };

    const el = document.createElement('div');
    el.className = `cfip-toast ${type}`;
    el.innerHTML = (icons[type] || '') + message;
    document.body.appendChild(el);

    toastTimer = setTimeout(() => el.remove(), 4000);
}

/* ── Collapsible user table ───────────────────── */
function toggleUserTable() {
    const body    = document.getElementById('umCollapseBody');
    const chevron = document.getElementById('umCollapseChevron');
    const open    = body.style.display !== 'none';
    body.style.display    = open ? 'none' : 'block';
    chevron.style.transform = open ? 'rotate(-90deg)' : 'rotate(0deg)';
}

/* ── Search ───────────────────────────────────── */
function filterUsers() {
    const q = document.getElementById('umSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#umTableBody tr[data-name]');
    let visible = 0;
    rows.forEach(r => {
        const match = !q || r.dataset.name.includes(q) || r.dataset.uid.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('umCount').textContent =
        visible + (visible === 1 ? ' user' : ' users');
}

/* ── Modal helpers ────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function backdropClose(e, id) { if (e.target === e.currentTarget) closeModal(id); }

function openAddModal() {
    document.getElementById('addName').value       = '';
    document.getElementById('addEmail').value      = '';
    document.getElementById('addDepartment').value = '';
    document.getElementById('addRole').value       = 'L';
    document.getElementById('previewUsername').textContent = '';
    document.getElementById('addFormError').style.display = 'none';
    openModal('addModal');
    document.getElementById('addName').focus();
}

/* ── Username preview — handled in DOMContentLoaded below ── */

/* ── Submit add-user form ─────────────────────── */
function submitAddUser() {
    const name  = document.getElementById('addName').value.trim();
    const email = document.getElementById('addEmail').value.trim();
    const dept  = document.getElementById('addDepartment').value;
    const role  = document.getElementById('addRole').value;
    const errEl = document.getElementById('addFormError');

    errEl.style.display = 'none';
    if (!name) { errEl.textContent = 'Full name is required.'; errEl.style.display = 'block'; return; }

    const btn = document.getElementById('addSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Creating…';

    fetch('{{ route("admin.users.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, email: email || null, department_id: dept || null, role, send_email: emailEnabled() }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Failed to create user.');
        closeModal('addModal');
        handleEmailResult(data, email);
        showCredentials(data.name, data.username, data.password);
        setTimeout(() => location.reload(), 4500);
    })
    .catch(err => {
        errEl.textContent = err.message;
        errEl.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Create & Send Invitation';
    });
}

/* ── Change role ──────────────────────────────── */
function changeRole(userId, select) {
    const role = select.value;
    fetch(`/admin/users/${userId}/role`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ role }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { alert('Failed to update role.'); select.value = role === 'L' ? 'PC' : 'L'; return; }
        select.className = 'um-role-select ' + (role === 'PC' ? 'pc' : 'learner');
        const avatar = select.closest('tr').querySelector('.um-avatar');
        if (role === 'PC') avatar.classList.add('pc'); else avatar.classList.remove('pc');
    })
    .catch(() => { alert('Network error.'); select.value = role === 'L' ? 'PC' : 'L'; });
}

/* ── Send / Re-send invitation ────────────────── */
function sendInvitation(userId, name, btn) {
    btn.disabled = true;
    const orig = btn.innerHTML;
    btn.innerHTML = '…';

    const formData = new FormData();
    formData.append('send_email', emailEnabled() ? '1' : '0');

    fetch(`/admin/users/${userId}/invite`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error('Failed.');
        handleEmailResult(data, null);
        showCredentials(data.name, data.username, data.password);
        // Update status badge
        const row    = btn.closest('tr');
        const badge  = row.querySelector('.um-status');
        badge.className = 'um-status invited';
        badge.innerHTML = '<span class="um-status-dot"></span> Invited';
        btn.className = 'um-action-btn reinvite';
        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 01.08 1.16 2 2 0 012.03 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg> Re-invite`;
    })
    .catch(err => { alert(err.message || 'Error.'); btn.innerHTML = orig; })
    .finally(() => { btn.disabled = false; });
}

/* ── Show credential modal ────────────────────── */
function showCredentials(name, username, password) {
    document.getElementById('credUserName').textContent = name;
    document.getElementById('credUsername').textContent = username;
    document.getElementById('credPassword').textContent = password;
    document.getElementById('credUrl').textContent      = LOGIN_URL;
    openModal('credModal');
}

/* ── Copy field to clipboard ──────────────────── */
function copyField(elementId, btn) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✓ Copied';
        setTimeout(() => { btn.innerHTML = orig; }, 1800);
    });
}

/* ── Handle email send result (toast) ────────── */
function handleEmailResult(data, fallbackEmail) {
    if (!emailEnabled()) return;
    const emailAddr = fallbackEmail || '(user email)';
    if (data.email_sent) {
        showToast('success', `Invitation email sent to ${emailAddr}`);
    } else if (data.email_error) {
        showToast('error', `Email failed: ${data.email_error}`);
    }
}

/* ── Restore toggle state on page load ───────── */
document.addEventListener('DOMContentLoaded', function () {
    applyEmailToggleUI(emailEnabled());

    document.getElementById('addName').addEventListener('input', function () {
        const words = this.value.trim().split(/\s+/);
        const preview = words.map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('').replace(/[^a-zA-Z0-9]/g, '');
        document.getElementById('previewUsername').textContent = preview || '—';
    });
});

/* ── Close modals on Escape ───────────────────── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['addModal','credModal','deleteModal','syncEmailsModal','bulkConfirmModal'].forEach(closeModal);
    }
});

/* ═══════════════════════════════════════════════
   DELETE USER
═══════════════════════════════════════════════ */
let deleteUserId  = null;
let deleteUserRow = null;

function confirmDeleteUser(userId, name, btn) {
    deleteUserId  = userId;
    deleteUserRow = btn.closest('tr');
    document.getElementById('deleteUserName').textContent = name;
    openModal('deleteModal');
}

function runDeleteUser() {
    if (!deleteUserId) return;

    const btn = document.getElementById('deleteConfirmBtn');
    btn.disabled    = true;
    btn.textContent = 'Removing…';

    fetch(`/admin/users/${deleteUserId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message || 'Failed to remove user.');
        closeModal('deleteModal');
        if (deleteUserRow) {
            deleteUserRow.style.transition = 'opacity 0.3s';
            deleteUserRow.style.opacity = '0';
            setTimeout(() => {
                deleteUserRow.remove();
                const remaining = document.querySelectorAll('#umTableBody tr[data-name]').length;
                document.getElementById('umCount').textContent = remaining + (remaining === 1 ? ' user' : ' users');
            }, 300);
        }
        showToast('success', 'User removed successfully.');
    })
    .catch(err => showToast('error', err.message || 'Error removing user.'))
    .finally(() => {
        btn.disabled    = false;
        btn.textContent = 'Remove User';
        deleteUserId    = null;
        deleteUserRow   = null;
    });
}

/* ═══════════════════════════════════════════════
   POPULATE EMAILS (safety button)
═══════════════════════════════════════════════ */
function openSyncEmailsModal() {
    openModal('syncEmailsModal');
}

function runSyncEmails() {
    const btn = document.getElementById('syncEmailsConfirmBtn');
    btn.disabled = true;
    btn.textContent = 'Syncing…';

    fetch('{{ route("admin.users.sync-emails") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        closeModal('syncEmailsModal');
        showToast('success', data.message || 'Emails populated.');
        const syncBtn = document.getElementById('syncEmailsBtn');
        syncBtn.classList.add('done');
        syncBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg> Emails Populated`;
        syncBtn.onclick = null;
    })
    .catch(() => {
        closeModal('syncEmailsModal');
        showToast('error', 'Failed to sync emails. Check the server log.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Populate Emails';
    });
}

/* ═══════════════════════════════════════════════
   BULK COHORT INVITE
═══════════════════════════════════════════════ */
let bulkLearners     = [];   // full list loaded from server
let bulkCredentials  = [];   // credentials returned after creation

function loadCohortLearners() {
    const groupId = document.getElementById('bulkCohortSelect').value;
    if (!groupId) { showToast('info', 'Please select a cohort first.'); return; }

    document.getElementById('bulkPanel').style.display       = 'none';
    document.getElementById('bulkEmptyState').style.display  = 'none';
    document.getElementById('bulkLoadingState').style.display = 'block';
    document.getElementById('bulkResults').style.display     = 'none';
    bulkCredentials = [];

    fetch(`{{ route("admin.cohort.learners") }}?group_id=${encodeURIComponent(groupId)}`, {
        headers: { 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('bulkLoadingState').style.display = 'none';
        bulkLearners = data.learners || [];

        if (!bulkLearners.length) {
            document.getElementById('bulkEmptyState').style.display = 'block';
            return;
        }

        renderBulkTable(bulkLearners);
        document.getElementById('bulkPanel').style.display = 'block';
    })
    .catch(() => {
        document.getElementById('bulkLoadingState').style.display = 'none';
        showToast('error', 'Failed to load cohort learners.');
    });
}

function renderBulkTable(learners) {
    const tbody  = document.getElementById('bulkTableBody');
    const total  = learners.length;
    const exists = learners.filter(l => l.has_account).length;
    const newC   = total - exists;

    document.getElementById('bulkStatTotal').textContent  = total;
    document.getElementById('bulkStatNew').textContent    = newC;
    document.getElementById('bulkStatExists').textContent = exists;

    tbody.innerHTML = learners.map(l => {
        const initials = l.name.split(' ').slice(0,2).map(w => w[0]?.toUpperCase() || '').join('');
        return `
        <tr class="${l.has_account ? 'has-account' : ''}" data-ispring-id="${l.ispring_user_id}">
            <td>
                <input type="checkbox" class="bulk-cb"
                    ${l.has_account ? 'disabled' : 'checked'}
                    onchange="updateBulkSelection()"
                    data-ispring-id="${l.ispring_user_id}">
            </td>
            <td>
                <div class="bulk-user-cell">
                    <div class="bulk-avatar">${initials}</div>
                    <span style="font-size:13px;font-weight:600;color:var(--text-primary)">${escHtml(l.name)}</span>
                </div>
            </td>
            <td style="font-size:12px;color:var(--text-muted)">${escHtml(l.department)}</td>
            <td>
                <span class="bulk-badge ${l.has_account ? 'exists' : 'new'}">
                    ${l.has_account ? 'Has Account' : 'New'}
                </span>
            </td>
        </tr>`;
    }).join('');

    updateBulkSelection();
}

function updateBulkSelection() {
    const checked = document.querySelectorAll('#bulkTableBody .bulk-cb:not([disabled]):checked').length;
    document.getElementById('bulkSelCount').textContent = checked;
    const btn = document.getElementById('bulkSendBtn');
    btn.disabled = checked === 0;
    document.getElementById('bulkSendLabel').textContent = `Send Invitations to ${checked} learner${checked !== 1 ? 's' : ''}`;
}

function bulkSelectAll(selectNew) {
    document.querySelectorAll('#bulkTableBody .bulk-cb:not([disabled])').forEach(cb => {
        cb.checked = selectNew;
    });
    updateBulkSelection();
}

function confirmBulkInvite() {
    const checked = document.querySelectorAll('#bulkTableBody .bulk-cb:not([disabled]):checked').length;
    document.getElementById('bulkConfirmText').innerHTML =
        `You are about to create <strong>${checked}</strong> learner account${checked !== 1 ? 's' : ''} from iSpring. ` +
        `Accounts will be created with a temporary password and <strong>must_change_password</strong> set.`;
    openModal('bulkConfirmModal');
}

function runBulkInvite() {
    const selectedIds = Array.from(
        document.querySelectorAll('#bulkTableBody .bulk-cb:not([disabled]):checked')
    ).map(cb => cb.dataset.ispringId);

    if (!selectedIds.length) return;

    const confirmBtn = document.getElementById('bulkConfirmBtn');
    confirmBtn.disabled    = true;
    confirmBtn.textContent = 'Creating…';

    const bulkSendBtn = document.getElementById('bulkSendBtn');
    bulkSendBtn.disabled = true;

    fetch('{{ route("admin.users.bulk-invite") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ ispring_user_ids: selectedIds, send_email: false }),
    })
    .then(r => r.json())
    .then(data => {
        closeModal('bulkConfirmModal');

        if (!data.success) throw new Error(data.message || 'Bulk invite failed.');

        bulkCredentials = data.credentials || [];

        document.getElementById('bulkResultCreated').textContent = data.created_count;
        document.getElementById('bulkResultSkipped').textContent = data.skipped_count;
        document.getElementById('bulkResults').style.display     = 'block';
        document.getElementById('bulkCsvBtn').style.display      = bulkCredentials.length ? '' : 'none';

        showToast('success', `${data.created_count} account${data.created_count !== 1 ? 's' : ''} created successfully.`);
        loadCohortLearners(); // reload table to reflect new statuses
    })
    .catch(err => {
        closeModal('bulkConfirmModal');
        showToast('error', err.message || 'Error during bulk invite.');
    })
    .finally(() => {
        confirmBtn.disabled    = false;
        confirmBtn.textContent = 'Create Accounts';
        bulkSendBtn.disabled   = false;
    });
}

function downloadCredentialsCSV() {
    if (!bulkCredentials.length) return;

    const header = ['Name', 'Username', 'Password'];
    const rows   = bulkCredentials.map(c => [c.name, c.username, c.password]);
    const csv    = [header, ...rows]
        .map(row => row.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','))
        .join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'cfip_bulk_credentials.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

@include('partials.api-status')
</body>
</html>
