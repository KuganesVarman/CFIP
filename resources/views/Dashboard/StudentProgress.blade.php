<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Progress | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <style>
        /* ── Toolbar ─────────────────────────────────────── */
        .sp-toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .sp-search-wrap {
            position: relative;
            flex: 0 0 220px;
        }
        .sp-search-wrap svg {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: var(--text-muted);
            pointer-events: none;
        }
        .sp-search {
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
        .sp-search:focus { border-color: var(--cfip-blue); }
        .sp-search::placeholder { color: var(--text-muted); }

        .sp-filter-select {
            height: 34px;
            padding: 0 30px 0 10px;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-card)
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E")
                no-repeat right 8px center / 14px 14px;
            appearance: none;
            cursor: pointer;
            outline: none;
        }
        .sp-filter-select:focus { border-color: var(--cfip-blue); }

        .sp-export-btn {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s;
        }
        .sp-export-btn:hover { background: #163d84; }
        .sp-export-btn svg { width: 13px; height: 13px; }

        .sp-row-count {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        /* ── Table card ─────────────────────────────────── */
        .sp-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .sp-table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 240px);
        }

        .sp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .sp-table thead { position: sticky; top: 0; z-index: 5; }

        .sp-table th {
            background: #f9fafb;
            padding: 10px 14px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            letter-spacing: 0.04em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .sp-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .sp-table tbody tr:last-child td { border-bottom: none; }
        .sp-table tbody tr:hover { background: rgba(26,79,168,0.03); cursor: pointer; }

        /* ── Learner avatar ─────────────────────────────── */
        .learner-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
        }
        .learner-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .learner-name { font-weight: 600; color: var(--text-primary); }
        .learner-dept { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        /* ── Progress cell ──────────────────────────────── */
        .progress-cell { min-width: 140px; }
        .progress-cell-bar {
            height: 5px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 3px;
        }
        .progress-cell-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.6s ease;
        }
        .progress-pct {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ── Status badge ───────────────────────────────── */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 10px;
            white-space: nowrap;
        }
        .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .status-badge.completed    { background: #dcfce7; color: #15803d; }
        .status-badge.completed::before    { background: #15803d; }
        .status-badge.on-track     { background: var(--cfip-blue-light); color: var(--cfip-blue); }
        .status-badge.on-track::before     { background: var(--cfip-blue); }
        .status-badge.at-risk      { background: #fef3c7; color: #b45309; }
        .status-badge.at-risk::before      { background: #b45309; }
        .status-badge.not-started  { background: #f3f4f6; color: #6b7280; }
        .status-badge.not-started::before  { background: #9ca3af; }

        /* ── View button ────────────────────────────────── */
        .view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--cfip-blue-light);
            border: none;
            cursor: pointer;
            color: var(--cfip-blue);
            transition: background 0.15s;
        }
        .view-btn:hover { background: #c7d9f7; }
        .view-btn svg { width: 14px; height: 14px; }

        /* ── Scope filter bar ────────────────────────────────── */
        .sp-scope-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding: 10px 14px;
            background: var(--bg-main);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .sp-scope-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sp-scope-hint {
            font-size: 11px;
            color: var(--text-muted);
            font-style: italic;
            margin-left: auto;
        }

        .sp-scope-clear {
            font-size: 11px;
            color: #ef4444;
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sp-scope-clear:hover { text-decoration: underline; }

        /* ── Empty state ─────────────────────────────────── */
        .sp-empty {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* ── Slide-in Detail Drawer ─────────────────────── */
        .drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 400;
            backdrop-filter: blur(1px);
        }
        .drawer-overlay.open { display: block; }

        .detail-drawer {
            position: fixed;
            top: 0;
            right: -440px;
            width: 420px;
            height: 100vh;
            background: var(--bg-card);
            box-shadow: -4px 0 32px rgba(0,0,0,0.18);
            z-index: 401;
            transition: right 0.28s cubic-bezier(0.4,0,0.2,1);
            display: flex;
            flex-direction: column;
        }
        .detail-drawer.open { right: 0; }

        .drawer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .drawer-header-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        .drawer-close {
            width: 28px; height: 28px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted);
            transition: background 0.15s;
        }
        .drawer-close:hover { background: var(--bg-main); }
        .drawer-close svg { width: 14px; height: 14px; }

        .drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* ── Avatar + identity ─────────────────────────── */
        .drawer-avatar {
            width: 64px; height: 64px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 700; color: #fff;
            margin: 0 auto 14px;
        }
        .drawer-name {
            font-size: 16px; font-weight: 700;
            color: var(--text-primary);
            text-align: center; margin-bottom: 4px;
        }
        .drawer-cohort-line {
            font-size: 12px; color: var(--text-muted);
            text-align: center; margin-bottom: 4px;
        }
        .drawer-inactive-line {
            font-size: 12px; font-weight: 500;
            text-align: center; margin-bottom: 16px;
        }

        /* ── Alert box ─────────────────────────────────── */
        .drawer-alert-box {
            border-radius: 8px;
            padding: 11px 13px;
            font-size: 12px; font-weight: 500; line-height: 1.5;
            margin-bottom: 14px;
            display: flex; gap: 9px; align-items: flex-start;
        }
        .drawer-alert-box svg { flex-shrink: 0; margin-top: 1px; }
        .drawer-alert-box.warning { background: rgba(180,83,9,0.1); color: #b45309; }
        .drawer-alert-box.info    { background: rgba(100,116,139,0.1); color: var(--text-secondary); }

        /* ── Generic section block ─────────────────────── */
        .drawer-section {
            background: var(--bg-main);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 10px;
        }
        .drawer-section-title {
            font-size: 10px; font-weight: 700; letter-spacing: 0.07em;
            text-transform: uppercase; color: var(--text-muted);
            margin-bottom: 12px;
        }

        /* ── Overall progress ──────────────────────────── */
        .drawer-progress-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 10px;
        }
        .drawer-progress-pct {
            font-size: 30px; font-weight: 700; color: var(--text-primary);
        }
        .drawer-progress-bar-wrap {
            height: 8px;
            background: var(--border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .drawer-progress-fill {
            height: 100%; border-radius: 4px;
            transition: width 0.5s ease;
        }

        /* ── Meta grid ─────────────────────────────────── */
        .drawer-meta-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 8px; margin-bottom: 10px;
        }
        .drawer-meta-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; color: var(--text-muted); margin-bottom: 3px;
        }
        .drawer-meta-value {
            font-size: 12px; font-weight: 600;
            color: var(--text-primary); line-height: 1.4;
        }
        .drawer-quiz-note {
            font-size: 11px; color: var(--text-muted); margin-top: 4px;
        }
        .drawer-quiz-note strong { color: var(--cfip-blue); }

        /* ── Domain progress bars ──────────────────────── */
        .ddom-item {
            display: grid;
            grid-template-columns: 110px 1fr 38px;
            align-items: center;
            gap: 8px; margin-bottom: 6px;
            padding: 5px 8px;
            border-radius: 7px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .ddom-item:last-child { margin-bottom: 0; }
        .ddom-item:hover { background: var(--bg-card); }
        .ddom-item.active { background: rgba(79,110,247,0.1); }
        .ddom-item.active .ddom-name { color: var(--cfip-blue); font-weight: 600; }
        .ddom-item.active .ddom-pct  { color: var(--cfip-blue); }
        .ddom-name {
            font-size: 12px; color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .ddom-bar-bg { height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; }
        .ddom-bar-fill { height: 100%; border-radius: 3px; background: #4f6ef7; transition: width 0.4s ease; }
        .ddom-pct { font-size: 11px; font-weight: 600; color: var(--text-muted); text-align: right; }

        /* ── FD tabs ───────────────────────────────────── */
        .drawer-fd-tabs { display: flex; gap: 4px; margin-bottom: 12px; flex-wrap: wrap; }
        .drawer-fd-tab {
            padding: 4px 12px;
            border-radius: 20px; font-size: 11px; font-weight: 600;
            border: 1.5px solid var(--border);
            background: transparent; color: var(--text-muted);
            cursor: pointer; transition: all 0.15s; font-family: inherit;
        }
        .drawer-fd-tab.active { background: var(--cfip-blue); border-color: var(--cfip-blue); color: #fff; }
        .drawer-fd-tab:hover:not(.active) { border-color: var(--cfip-blue); color: var(--cfip-blue); }

        /* ── Module result rows ────────────────────────── */
        .dmod-item {
            display: flex; align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            gap: 8px;
        }
        .dmod-item:last-child { border-bottom: none; }
        .dmod-code {
            font-size: 10px; font-weight: 700; color: var(--text-muted);
            background: var(--bg-card);
            padding: 2px 6px; border-radius: 4px; flex-shrink: 0;
        }
        .dmod-title { flex: 1; font-size: 12px; color: var(--text-primary); }
        .dmod-score { font-size: 12px; font-weight: 700; color: var(--text-primary); min-width: 32px; text-align: right; }
        .dmod-badge {
            display: inline-flex; align-items: center;
            font-size: 10px; font-weight: 600;
            padding: 2px 8px; border-radius: 8px;
            white-space: nowrap; flex-shrink: 0;
        }
        .dmod-badge.pass        { background: #dcfce7; color: #15803d; }
        .dmod-badge.in_progress { background: #fef3c7; color: #b45309; }
        .dmod-badge.failed      { background: #fee2e2; color: #dc2626; }
        .dmod-badge.not_started { background: #f3f4f6; color: #6b7280; }
        html.dark-mode .dmod-badge.not_started { background: #334155; color: #94a3b8; }
        .dmod-empty { font-size: 12px; color: var(--text-muted); text-align: center; padding: 20px 0; }

        /* ── Loading state ─────────────────────────────── */
        .drawer-loading {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 60px 20px; gap: 12px;
        }
        .drawer-loading-spinner {
            width: 28px; height: 28px;
            border: 3px solid var(--border);
            border-top-color: var(--cfip-blue);
            border-radius: 50%;
            animation: drw-spin 0.8s linear infinite;
        }
        @keyframes drw-spin { to { transform: rotate(360deg); } }
        .drawer-loading-text { font-size: 12px; color: var(--text-muted); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 1024px) {
            .sp-toolbar     { gap: 6px; }
            .sp-search-wrap { flex: 0 0 180px; }
        }

        @media (max-width: 768px) {
            .sp-toolbar         { flex-wrap: wrap; gap: 6px; }
            .sp-search-wrap     { flex: 1 1 100%; }
            .sp-export-btn      { margin-left: 0; }
            .sp-scope-bar       { gap: 6px; }
            .sp-scope-hint      { display: none; }
            .sp-table-wrap      { max-height: calc(100vh - 280px); }
            .detail-drawer      { width: 100%; right: -100%; }
            .learner-cell       { min-width: 150px; }
        }

        @media (max-width: 480px) {
            .sp-table th, .sp-table td { padding: 8px 10px; font-size: 12px; }
            .learner-name   { font-size: 12px; }
            .learner-dept   { font-size: 10px; }
            .progress-cell  { min-width: 100px; }
            .sp-scope-bar   { padding: 8px 10px; }
        }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

@include('partials.sidebar')

<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            @if($isPc)
                <span class="page-title">{{ $agencyName ?? 'Agency' }}</span>
                <svg class="page-title-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            @endif
            <span class="page-title" style="{{ $isPc ? 'color:#6b7280;font-weight:500;font-size:16px' : '' }}">
                Student Progress
            </span>
        </div>
        <div class="topbar-right">
            <div class="sync-btn-wrap">
                <button class="icon-btn" id="syncRefreshBtn" onclick="cfipRequestSync()" title="Refresh data from iSpring">
                    <svg class="sync-refresh-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                    </svg>
                </button>
                <span class="sync-cd-pip" id="syncCdPip"></span>
            </div>
            @include('partials.api-dot')
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- TOOLBAR --}}
        <div class="sp-toolbar">
            {{-- Search --}}
            <div class="sp-search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" class="sp-search" id="searchInput" placeholder="Search name…" oninput="filterTable()">
            </div>

            {{-- Status filter --}}
            <select class="sp-filter-select" id="statusFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="on-track">On Track</option>
                <option value="at-risk">At Risk</option>
                <option value="not-started">Not Started</option>
            </select>

            {{-- Cohort (server-side) --}}
            @if($cohorts->isNotEmpty())
            <select class="sp-filter-select" onchange="applyServerFilter('cohort', this.value)">
                <option value="">All Cohorts</option>
                @foreach($cohorts as $cohort)
                    <option value="{{ $cohort->group_id }}"
                        {{ $selectedCohort === $cohort->group_id ? 'selected' : '' }}>
                        {{ $cohort->name }}
                    </option>
                @endforeach
            </select>
            @endif

            {{-- Agency (admin only, server-side) --}}
            @if(!$isPc && $agencies->isNotEmpty())
            <select class="sp-filter-select" onchange="applyServerFilter('agency', this.value)">
                <option value="">All Agencies</option>
                @foreach($agencies as $agency)
                    <option value="{{ $agency->department_id }}"
                        {{ $selectedAgency === $agency->department_id ? 'selected' : '' }}>
                        {{ $agency->name }}
                    </option>
                @endforeach
            </select>
            @endif

            <button class="sp-export-btn" onclick="exportCsv()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export CSV
            </button>
        </div>

        {{-- SCOPE FILTER BAR (Domain + Module) --}}
        @if(isset($domains) && $domains->isNotEmpty())
        <div class="sp-scope-bar">
            <span class="sp-scope-label">Scope by:</span>

            <select class="sp-filter-select" id="domainScopeFilter" onchange="applyDomainScope(this.value)">
                <option value="">All Domains</option>
                @php $lastLevel = ''; @endphp
                @foreach($domains as $domain)
                    @if($domain->level_name !== $lastLevel)
                        @if($lastLevel !== '') </optgroup> @endif
                        <optgroup label="{{ $domain->level_name }} Level">
                        @php $lastLevel = $domain->level_name; @endphp
                    @endif
                    <option value="{{ $domain->id }}" {{ ($selectedDomain ?? null) == $domain->id ? 'selected' : '' }}>
                        {{ $domain->name }}
                    </option>
                @endforeach
                @if($lastLevel !== '') </optgroup> @endif
            </select>

            @if(isset($domainCourses) && $domainCourses->isNotEmpty())
            <select class="sp-filter-select" id="courseScopeFilter" onchange="applyServerFilter('course', this.value)">
                <option value="">All Modules</option>
                @foreach($domainCourses as $dc)
                    <option value="{{ $dc->course_id }}" {{ ($selectedCourse ?? null) === $dc->course_id ? 'selected' : '' }}>
                        {{ $dc->course_code }}
                    </option>
                @endforeach
            </select>
            @endif

            @if(($selectedDomain ?? null) || ($selectedCourse ?? null))
            @php
                $clearParams = array_filter([
                    'agency'  => $selectedAgency ?? null,
                    'cohort'  => $selectedCohort ?? null,
                ]);
            @endphp
            <a href="{{ request()->url() }}{{ $clearParams ? '?' . http_build_query($clearParams) : '' }}" class="sp-scope-clear">
                ✕ Clear scope
            </a>
            @endif

            <span class="sp-scope-hint">
                @if($selectedCourse ?? null)
                    Showing progress for selected module
                @elseif($selectedDomain ?? null)
                    Showing progress for selected domain
                @else
                    Showing overall progress across all domains
                @endif
            </span>
        </div>
        @endif

        <p class="sp-row-count" id="rowCount"></p>

        {{-- TABLE --}}
        <div class="sp-card">
            <div class="sp-table-wrap">
                <table class="sp-table" id="progressTable">
                    <thead>
                        <tr>
                            <th>Learner</th>
                            <th style="min-width:140px">Progress</th>
                            <th>Status</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        @forelse($learners as $learner)
                            @php
                                $pct      = $learner->avg_progress;
                                $hasData  = $learner->has_results;
                                if (!$hasData || $pct === 0) {
                                    $status   = 'not-started';
                                    $label    = 'Not Started';
                                    $color    = '#9ca3af';
                                    $barColor = '#d1d5db';
                                } elseif ($pct >= 80) {
                                    $status   = 'completed';
                                    $label    = 'Completed';
                                    $color    = '#15803d';
                                    $barColor = '#1d9e75';
                                } elseif ($pct >= 30) {
                                    $status   = 'on-track';
                                    $label    = 'On Track';
                                    $color    = '#1a4fa8';
                                    $barColor = '#1a4fa8';
                                } else {
                                    $status   = 'at-risk';
                                    $label    = 'At Risk';
                                    $color    = '#b45309';
                                    $barColor = '#f59e0b';
                                }
                                $words    = array_slice(explode(' ', $learner->full_name), 0, 2);
                                $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words));
                                @endphp
                            <tr
                                data-name="{{ strtolower($learner->full_name) }}"
                                data-status="{{ $status }}"
                                onclick="openDrawer(this)"
                                data-drawer-user-id="{{ $learner->user_id }}"
                                data-drawer-name="{{ $learner->full_name }}"
                                data-drawer-dept="{{ $learner->department_name ?? '—' }}"
                                data-drawer-pct="{{ $pct }}"
                                data-drawer-status="{{ $label }}"
                                data-drawer-status-class="{{ $status }}"
                                data-drawer-initials="{{ $initials }}"
                                data-drawer-color="{{ $color }}"
                                data-drawer-bar="{{ $barColor }}"
                            >
                                <td>
                                    <div class="learner-cell">
                                        <div class="learner-avatar" style="background:{{ $color }}">{{ $initials }}</div>
                                        <div>
                                            <div class="learner-name">{{ $learner->full_name }}</div>
                                            <div class="learner-dept">{{ $learner->department_name ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="progress-cell">
                                    <span class="progress-pct">{{ $pct }}%</span>
                                    <div class="progress-cell-bar">
                                        <div class="progress-cell-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
                                    </div>
                                </td>
                                <td><span class="status-badge {{ $status }}">{{ $label }}</span></td>
                                <td>
                                    <button class="view-btn" title="View details" onclick="event.stopPropagation();openDrawer(this.closest('tr'))">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="sp-empty">No learners found for the selected filters.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main --}}


{{-- DRAWER OVERLAY --}}
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

{{-- DETAIL DRAWER --}}
<div class="detail-drawer" id="detailDrawer">
    <div class="drawer-header">
        <span class="drawer-header-title">Learner Detail</span>
        <button class="drawer-close" onclick="closeDrawer()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    <div class="drawer-body">

        {{-- Loading --}}
        <div class="drawer-loading" id="drawerLoading">
            <div class="drawer-loading-spinner"></div>
            <span class="drawer-loading-text">Loading learner data…</span>
        </div>

        {{-- Rich content --}}
        <div id="drawerContent" style="display:none">

            <div class="drawer-avatar" id="dAvatar"></div>
            <div class="drawer-name" id="dName"></div>
            <div class="drawer-cohort-line" id="dCohort"></div>
            <div class="drawer-inactive-line" id="dInactiveLine"></div>

            <div class="drawer-alert-box warning" id="dAlertBox" style="display:none">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span id="dAlertText"></span>
            </div>

            {{-- Overall Progress --}}
            <div class="drawer-section">
                <div class="drawer-section-title">Overall Progress</div>
                <div class="drawer-progress-row">
                    <div class="drawer-progress-pct" id="dPct"></div>
                    <span class="status-badge" id="dStatusBadge"></span>
                </div>
                <div class="drawer-progress-bar-wrap">
                    <div class="drawer-progress-fill" id="dProgressFill"></div>
                </div>
                <div class="drawer-meta-grid">
                    <div>
                        <div class="drawer-meta-label">Department</div>
                        <div class="drawer-meta-value" id="dMetaDept"></div>
                    </div>
                    <div>
                        <div class="drawer-meta-label">Last Active</div>
                        <div class="drawer-meta-value" id="dMetaLast"></div>
                    </div>
                </div>
                <div class="drawer-quiz-note" id="dQuizNote"></div>
            </div>

            {{-- Domain Progress --}}
            <div class="drawer-section">
                <div class="drawer-section-title">Domain Progress</div>
                <div id="dDomains"></div>
            </div>

            {{-- Module Results --}}
            <div class="drawer-section">
                <div class="drawer-section-title" id="dModuleSectionTitle">Module Results</div>
                <div class="drawer-fd-tabs" id="dFdTabs"></div>
                <div id="dModules"></div>
            </div>

        </div>{{-- /drawerContent --}}
    </div>{{-- /drawer-body --}}
</div>{{-- /detail-drawer --}}


<script>
// ── Drawer ────────────────────────────────────────────────
const _detailUrl = '{{ route("api.learner.detail") }}';
let _activeDomain  = null;
let _activeCourse  = null;
let _domainModules = {};

const _statusMeta = {
    'completed':   { label: 'Completed',   bar: '#1d9e75', avatar: '#1d9e75' },
    'on-track':    { label: 'On Track',    bar: '#1a4fa8', avatar: '#1a4fa8' },
    'at-risk':     { label: 'At Risk',     bar: '#f59e0b', avatar: '#f59e0b' },
    'not-started': { label: 'Not Started', bar: '#d1d5db', avatar: '#9ca3af' },
};

function _deriveStatus(pct, hasResults) {
    if (!hasResults || pct === 0) return 'not-started';
    if (pct >= 80) return 'completed';
    if (pct >= 30) return 'on-track';
    return 'at-risk';
}

function _slugify(s) {
    return String(s).toLowerCase().replace(/[^a-z0-9]/g, '-');
}

function openDrawer(row) {
    const userId = row.dataset.drawerUserId;

    document.getElementById('drawerLoading').style.display  = 'flex';
    document.getElementById('drawerContent').style.display  = 'none';
    document.getElementById('detailDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');

    const _detailParams = new URLSearchParams({ user_id: userId });
    if (new URL(window.location.href).searchParams.has('include_lessons')) _detailParams.set('include_lessons', '1');
    fetch(_detailUrl + '?' + _detailParams.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        renderDrawer(data);
        document.getElementById('drawerLoading').style.display = 'none';
        document.getElementById('drawerContent').style.display = 'block';
    })
    .catch(() => {
        document.getElementById('drawerLoading').innerHTML =
            '<span style="color:#ef4444;font-size:13px">Failed to load learner data.</span>';
    });
}

function renderDrawer(d) {
    const pct        = d.overall_progress ?? 0;
    const hasResults = (d.domains || []).some(dom => dom.progress > 0) || pct > 0;
    const statusKey  = _deriveStatus(pct, hasResults);
    const meta       = _statusMeta[statusKey];

    // Avatar
    const initials = (d.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
    const av = document.getElementById('dAvatar');
    av.textContent      = initials;
    av.style.background = meta.avatar;

    document.getElementById('dName').textContent   = d.name   || '—';
    document.getElementById('dCohort').textContent = d.cohort || '—';

    // Last active line
    const inact = document.getElementById('dInactiveLine');
    if (d.days_inactive !== null && d.days_inactive !== undefined) {
        inact.textContent = 'Last active ' + d.days_inactive + ' day' + (d.days_inactive !== 1 ? 's' : '') + ' ago';
        inact.style.color = d.days_inactive > 14 ? '#ef4444' : 'var(--text-muted)';
    } else {
        inact.textContent = 'Never active';
        inact.style.color = 'var(--text-muted)';
    }

    // Alert box
    const alertBox  = document.getElementById('dAlertBox');
    const alertText = document.getElementById('dAlertText');
    if (statusKey === 'at-risk') {
        const dayStr = (d.days_inactive !== null && d.days_inactive !== undefined)
            ? ' with ' + d.days_inactive + ' days of inactivity.' : '.';
        alertText.textContent  = 'This learner is at risk. Progress below 30%' + dayStr;
        alertBox.style.display = 'flex';
    } else if (statusKey === 'not-started') {
        alertText.textContent  = 'This learner has not started any modules yet.';
        alertBox.style.display = 'flex';
    } else {
        alertBox.style.display = 'none';
    }

    // Overall progress
    document.getElementById('dPct').textContent = pct + '%';
    const badge = document.getElementById('dStatusBadge');
    badge.textContent = meta.label;
    badge.className   = 'status-badge ' + statusKey;
    const fill = document.getElementById('dProgressFill');
    fill.style.width      = pct + '%';
    fill.style.background = meta.bar;

    // Meta
    document.getElementById('dMetaDept').textContent = d.department || '—';
    document.getElementById('dMetaLast').textContent = d.last_active
        ? new Date(d.last_active).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
        : '—';
    document.getElementById('dQuizNote').innerHTML =
        'Avg quiz score across all scored activities: <strong>' + pct + '%</strong>';

    // Domain progress bars (clickable)
    _domainModules = d.domain_modules || {};
    const domEl = document.getElementById('dDomains');
    domEl.innerHTML = (d.domains || []).map(dom => `
        <div class="ddom-item" id="ddom-${_slugify(dom.name)}"
             onclick="selectDomain('${escHtml(dom.name).replace(/'/g,"\\'")}')">
            <span class="ddom-name" title="${escHtml(dom.name)}">${escHtml(dom.name)}</span>
            <div class="ddom-bar-bg"><div class="ddom-bar-fill" style="width:${dom.progress}%"></div></div>
            <span class="ddom-pct">${dom.progress}%</span>
        </div>
    `).join('') || '<div class="dmod-empty">No domain data.</div>';

    // Auto-select the first domain that has module data, otherwise just the first domain
    const firstWithData = (d.domains || []).find(dom => {
        const courses = _domainModules[dom.name] || {};
        return Object.keys(courses).length > 0;
    });
    const autoSelect = firstWithData || (d.domains || [])[0];
    if (autoSelect) selectDomain(autoSelect.name);
}

function selectDomain(domainName) {
    _activeDomain = domainName;

    // Highlight selected domain row
    document.querySelectorAll('.ddom-item').forEach(el => el.classList.remove('active'));
    const activeEl = document.getElementById('ddom-' + _slugify(domainName));
    if (activeEl) activeEl.classList.add('active');

    // Update module section title
    document.getElementById('dModuleSectionTitle').textContent = 'Module Results — ' + domainName;

    // Build course tabs for this domain
    const courses = _domainModules[domainName] || {};
    const courseKeys = Object.keys(courses);
    _activeCourse = courseKeys[0] || null;

    const tabsEl = document.getElementById('dFdTabs');
    tabsEl.innerHTML = courseKeys.map(code =>
        `<button class="drawer-fd-tab${code === _activeCourse ? ' active' : ''}" onclick="switchCourse('${code}')">${code}</button>`
    ).join('');

    renderCourseModules();
}

function switchCourse(code) {
    _activeCourse = code;
    document.querySelectorAll('.drawer-fd-tab').forEach(btn => {
        btn.classList.toggle('active', btn.textContent.trim() === code);
    });
    renderCourseModules();
}

function renderCourseModules() {
    const el = document.getElementById('dModules');
    if (!_activeDomain) { el.innerHTML = ''; return; }

    const courses = _domainModules[_activeDomain] || {};
    const courseKeys = Object.keys(courses);

    if (courseKeys.length === 0) {
        el.innerHTML = '<div class="dmod-empty">No module data for this domain yet.</div>';
        return;
    }
    if (!_activeCourse || !courses[_activeCourse]) {
        _activeCourse = courseKeys[0];
    }

    const modules = courses[_activeCourse] || [];
    if (modules.length === 0) {
        el.innerHTML = `<div class="dmod-empty">No module data for ${escHtml(_activeCourse)} yet.</div>`;
        return;
    }
    const labels  = { pass: 'Pass', in_progress: 'In Progress', failed: 'Failed', not_started: 'Not Started' };
    el.innerHTML  = modules.map(m => `
        <div class="dmod-item">
            <span class="dmod-code">${escHtml(_activeCourse)}</span>
            <span class="dmod-title">${escHtml(m.title)}</span>
            <span class="dmod-badge ${m.status}">${labels[m.status] || m.status}</span>
            <span class="dmod-score">${m.status === 'not_started' ? '—' : m.score + '%'}</span>
        </div>
    `).join('');
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function closeDrawer() {
    document.getElementById('detailDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

// ── Live filter (name + status) ───────────────────────────
function filterTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase().trim();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('#tableBody tr[data-name]');
    let vis = 0;
    rows.forEach(r => {
        const nameMatch   = !q      || r.dataset.name.includes(q);
        const statusMatch = !status || r.dataset.status === status;
        const show = nameMatch && statusMatch;
        r.style.display = show ? '' : 'none';
        if (show) vis++;
    });
    updateRowCount(vis);
}

function updateRowCount(n) {
    const el = document.getElementById('rowCount');
    if (el) el.textContent = n + ' learner' + (n !== 1 ? 's' : '') + ' shown';
}

// ── Server filter (cohort / agency / course) ──────────────
function applyServerFilter(param, value) {
    const params = new URLSearchParams(window.location.search);
    value ? params.set(param, value) : params.delete(param);
    window.location.search = params.toString();
}

// ── Domain scope filter (resets course when domain changes) ─
function applyDomainScope(domainId) {
    const params = new URLSearchParams(window.location.search);
    if (domainId) {
        params.set('domain', domainId);
    } else {
        params.delete('domain');
    }
    params.delete('course');
    window.location.search = params.toString();
}

// ── CSV Export ────────────────────────────────────────────
function exportCsv() {
    const scopeLabel = _getScopeLabel();
    const now        = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

    const progressHeader = scopeLabel
        ? `Progress % (${scopeLabel})`
        : 'Progress % (Overall)';

    const headers = [
        'No.',
        'Learner Name',
        'Department',
        progressHeader,
        'Status',
    ];

    const dataRows = [...document.querySelectorAll('#tableBody tr[data-name]')]
        .filter(r => r.style.display !== 'none')
        .map((r, i) => {
            const d = r.dataset;
            return [
                String(i + 1),
                d.drawerName   || '',
                d.drawerDept   || '',
                (d.drawerPct   || '0') + '%',
                d.drawerStatus || '',
            ];
        });

    const q = v => `"${String(v).replace(/"/g, '""')}"`;

    const metaRows = [
        [q('CFIP System — Student Progress Report')],
        [q(`Generated: ${now}`)],
        [q(`Scope: ${scopeLabel || 'All Domains (Overall)'}`)],
        [q(`Total learners shown: ${dataRows.length}`)],
        [''],
    ];

    const csv = [
        ...metaRows.map(r => r.join(',')),
        headers.map(q).join(','),
        ...dataRows.map(r => r.map(q).join(',')),
    ].join('\r\n');

    const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = Object.assign(document.createElement('a'), {
        href: url,
        download: `student_progress_${now.replace(/ /g, '_')}.csv`,
    });
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    try {
        fetch('{{ route("api.report.log") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ title: 'Student Progress Report', format: 'Excel' }),
        });
    } catch (_) {}
}

function _getScopeLabel() {
    const domainEl = document.getElementById('domainScopeFilter');
    const courseEl = document.getElementById('courseScopeFilter');
    if (courseEl && courseEl.value) {
        return courseEl.options[courseEl.selectedIndex].text.trim();
    }
    if (domainEl && domainEl.value) {
        return domainEl.options[domainEl.selectedIndex].text.trim();
    }
    return '';
}

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    updateRowCount(document.querySelectorAll('#tableBody tr[data-name]').length);

    // Auto-open drawer when navigating from at-risk panel
    const urlParams = new URLSearchParams(window.location.search);
    const drawerId  = urlParams.get('drawer');
    if (drawerId) {
        const row = document.querySelector(`tr[data-drawer-user-id="${CSS.escape(drawerId)}"]`);
        if (row) {
            setTimeout(() => openDrawer(row), 150);
        }
        // Clean up the URL without reloading
        urlParams.delete('drawer');
        const newSearch = urlParams.toString();
        history.replaceState(null, '', window.location.pathname + (newSearch ? '?' + newSearch : ''));
    }
});
</script>

@include('partials.api-status')
</body>
</html>
