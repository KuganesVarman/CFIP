<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — Module View | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* ── Topbar: two-row layout ──────────────────────────── */
        .topbar {
            flex-direction: column !important;
            height: auto !important;
            padding: 0 !important;
            align-items: stretch !important;
        }
        .topbar-main {
            display: flex;
            align-items: center;
            height: 56px;
            padding: 0 24px;
            gap: 12px;
        }
        .topbar-breadcrumb-bar {
            border-top: 1px solid var(--border);
            padding: 5px 24px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Topbar filter selects ──────────────────────────── */
        .topbar-filter-select {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border) !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            cursor: pointer !important;
            font-family: inherit !important;
        }
        .topbar-filters { display: flex; gap: 8px; align-items: center; flex-shrink: 1; }

        /* ── Domain picker (topbar) ──────────────────────────── */
        .domain-picker {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            font-family: inherit;
            flex-shrink: 0;
        }
        .domain-picker svg { color: var(--text-muted); flex-shrink: 0; }
        .domain-picker select {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }

        /* ── Generate Report button ──────────────────────────── */
        .generate-report-btn {
            display: flex; align-items: center; gap: 0.4rem;
            background: #1a4fa8; color: #fff; border: none;
            border-radius: 8px; padding: 7px 14px; font-size: 13px;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
            font-family: inherit; white-space: nowrap;
        }
        .generate-report-btn:hover:not(:disabled) { background: #163d84; }
        .generate-report-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .generate-report-btn svg { width: 16px; height: 16px; flex-shrink: 0; }

        /* ── KPI cards (new layout: label above number) ──────── */
        .kpi-card-v2 {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .kpi-card-v2::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            border-radius: 12px 12px 0 0;
        }
        .kpi-card-v2.blue::before   { background: #1a4fa8; }
        .kpi-card-v2.green::before  { background: #1d9e75; }
        .kpi-card-v2.amber::before  { background: #f59e0b; }
        .kpi-card-v2.red::before    { background: #e24b4a; }

        .kpi-card-v2-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .kpi-card-v2-body { flex: 1; }

        .kpi-v2-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .kpi-v2-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        .kpi-v2-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }
        .kpi-icon-sq {
            width: 32px; height: 32px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .kpi-icon-sq svg { width: 18px; height: 18px; }
        .kpi-icon-sq.blue  { background: #eff6ff; color: #1a4fa8; }
        .kpi-icon-sq.green { background: #f0fdf4; color: #1d9e75; }
        .kpi-icon-sq.amber { background: #fffbeb; color: #f59e0b; }
        .kpi-icon-sq.red   { background: #fff1f2; color: #e24b4a; }
        html.dark-mode .kpi-icon-sq.blue  { background: rgba(26,79,168,0.2); }
        html.dark-mode .kpi-icon-sq.green { background: rgba(29,158,117,0.2); }
        html.dark-mode .kpi-icon-sq.amber { background: rgba(245,158,11,0.2); }
        html.dark-mode .kpi-icon-sq.red   { background: rgba(226,75,74,0.2); }

        /* ── Charts row ──────────────────────────────────────── */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-panel {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px;
        }
        .panel-title {
            font-size: 12px; font-weight: 700;
            color: var(--text-secondary); text-transform: uppercase;
            letter-spacing: 0.06em; line-height: 1.4;
            margin-bottom: 12px;
        }
        .chart-legend {
            display: flex; gap: 12px; flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .legend-item {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--text-muted);
        }
        .legend-sq { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

        /* ── Right panel sub-sections ────────────────────────── */
        .panel-sub-title {
            font-size: 11px; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 10px;
        }
        .panel-divider { border: none; border-top: 1px solid var(--border); margin: 14px 0; }

        /* ── Donut legend ─────────────────────────────────────── */
        .donut-legend {
            display: flex; justify-content: center; gap: 16px;
            margin-top: 8px; flex-wrap: wrap;
        }
        .donut-legend-item {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--text-muted);
        }
        .donut-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        /* ── Ring charts row ─────────────────────────────────── */
        .rings-row {
            display: flex; justify-content: space-around; align-items: flex-start;
            margin-top: 14px;
        }
        .ring-item { display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .ring-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-align: center; }

        /* ── Module breakdown card ───────────────────────────── */
        .module-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }
        .module-card-header {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 16px;
            margin-bottom: 16px;
        }
        .module-card-title { font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.06em; line-height: 1.4; }

        /* ── Dark search + sort controls ─────────────────────── */
        .table-controls { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }

        .dark-search-wrap { position: relative; }
        .dark-search-icon {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%); color: var(--text-muted);
            width: 14px; height: 14px; pointer-events: none;
        }
        .dark-search-input {
            background: var(--bg-main); color: var(--text-primary); border: 1px solid var(--border);
            border-radius: 8px; padding: 9px 16px 9px 34px;
            font-size: 13px; width: 200px; outline: none;
            font-family: inherit;
        }
        .dark-search-input::placeholder { color: var(--text-muted); }

        .dark-sort-select {
            background: var(--bg-main); color: var(--text-primary); border: 1px solid var(--border);
            border-radius: 8px; padding: 9px 16px; font-size: 13px;
            cursor: pointer; font-family: inherit; width: 160px; outline: none;
        }

        /* ── Module table ────────────────────────────────────── */
        .module-table { width: 100%; border-collapse: collapse; font-family: inherit; }
        .module-table thead tr { border-bottom: 1px solid var(--border); }
        .module-table th {
            padding: 10px 8px 10px 0;
            font-size: 11px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.06em;
            text-align: left; white-space: nowrap;
        }
        .module-table td {
            padding: 16px 8px 16px 0;
            font-size: 13px; color: var(--text-secondary);
            border-bottom: 1px solid var(--border); vertical-align: middle;
        }
        .module-table tbody tr:last-child td { border-bottom: none; }
        .module-table tbody tr { cursor: pointer; }
        .module-table tbody tr:hover td { background: var(--bg-main); }

        .mod-code { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .count-green  { color: #1d9e75; font-weight: 600; }
        .count-amber  { color: #f59e0b; font-weight: 600; }
        .count-muted  { color: var(--text-muted); }
        .count-red    { color: #e24b4a; font-weight: 600; }

        .dist-bar-mini {
            width: 90px; height: 8px; border-radius: 4px;
            overflow: hidden; display: flex; flex-shrink: 0;
        }
        .dist-seg { height: 100%; }

        .rate-text-green { color: #1d9e75; font-weight: 700; }
        .rate-text-amber { color: #f59e0b; font-weight: 700; }
        .rate-text-red   { color: #e24b4a; font-weight: 700; }

        .table-footnote {
            font-size: 11px; margin-top: 12px;
            border-top: 1px solid var(--border); padding-top: 10px;
        }

        /* ── Bottom row ──────────────────────────────────────── */
        .bottom-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .topic-card { background: var(--bg-card); border-radius: 12px; padding: 20px 24px; }
        .topic-card-title {
            font-size: 11px; font-weight: 700; letter-spacing: 0.07em;
            text-transform: uppercase; color: var(--text-muted); margin-bottom: 16px;
        }
        .topic-row { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .topic-row:last-child { margin-bottom: 0; }
        .topic-badge {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; text-align: center; flex-shrink: 0;
        }
        .topic-info { flex: 1; min-width: 0; }
        .topic-name { font-size: 13px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topic-bar-track { height: 5px; border-radius: 3px; background: var(--bg-main); overflow: hidden; margin-top: 6px; }
        .topic-bar-fill  { height: 100%; border-radius: 3px; }
        .topic-score { font-size: 14px; font-weight: 700; flex-shrink: 0; width: 40px; text-align: right; }

        /* ── Learner popup modal ──────────────────────────────── */
        .lm-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .lm-overlay.open { display: flex; }
        .lm-modal {
            background: var(--bg-card);
            border-radius: 16px;
            width: 100%;
            max-width: 560px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .lm-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .lm-header-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .lm-header-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .lm-close-btn {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--bg-main); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted); flex-shrink: 0;
            transition: background 0.15s;
        }
        .lm-close-btn:hover { background: var(--border); }
        .lm-filters {
            display: flex;
            gap: 10px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .lm-select {
            flex: 1;
            background: var(--bg-main); color: var(--text-primary);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 8px 12px; font-size: 13px;
            cursor: pointer; font-family: inherit; outline: none;
        }
        .lm-stats {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .lm-stat {
            flex: 1;
            padding: 10px 8px;
            text-align: center;
        }
        .lm-stat + .lm-stat { border-left: 1px solid var(--border); }
        .lm-stat-num {
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
        }
        .lm-stat-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 3px;
        }
        .lm-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        .lm-learner-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }
        .lm-learner-row:last-child { border-bottom: none; }
        .lm-learner-row:hover { background: var(--bg-main); }
        .lm-avatar {
            width: 34px; height: 34px; border-radius: 8px;
            background: #eff6ff; color: #1a4fa8;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .lm-name {
            flex: 1;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lm-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .lm-empty, .lm-loading {
            padding: 40px 20px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }
        .lm-loading::before {
            content: '⏳ ';
        }

        /* ── Score insight mini-cards ────────────────────────── */
        .score-insight {
            border-radius: 8px;
            padding: 10px 8px;
        }
        .score-insight--red   { background: #fff1f2; }
        .score-insight--amber { background: #fffbeb; }
        .score-insight--green { background: #f0fdf4; }
        .score-insight-lbl {
            font-size: 9px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px;
        }
        .score-insight-val  { font-size: 16px; font-weight: 700; }
        .score-insight-sub  { font-size: 10px; color: var(--text-muted); margin-top: 2px; }

        html.dark-mode .score-insight--red   { background: rgba(226,75,74,0.15); }
        html.dark-mode .score-insight--amber { background: rgba(245,158,11,0.15); }
        html.dark-mode .score-insight--green { background: rgba(29,158,117,0.15); }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 1024px) {
            .charts-row    { grid-template-columns: 1fr; }
            .bottom-row    { grid-template-columns: 1fr; }
            .kpi-grid      { grid-template-columns: repeat(2, 1fr); }
            .topbar-main   { height: auto; min-height: 52px; padding: 8px 20px; }
            .kpi-v2-number { font-size: 24px; }
        }

        @media (max-width: 768px) {
            .topbar-main   { padding: 8px 12px 8px 56px; gap: 8px; }
            .topbar-breadcrumb-bar { padding: 4px 12px; font-size: 11px; }
            .topbar-filters { gap: 6px; }
            .domain-picker, .topbar-filter-select { font-size: 12px !important; padding: 5px 10px !important; }
            .generate-report-btn { font-size: 12px; padding: 6px 10px; }
            .generate-report-btn svg { display: none; }
            .kpi-grid      { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .kpi-v2-number { font-size: 22px; }
            .kpi-card-v2   { padding: 14px; }
            .module-card   { padding: 14px; }
            .module-card-header { flex-direction: column; align-items: stretch; gap: 10px; }
            .table-controls { flex-wrap: wrap; }
            .dark-search-input { width: 100%; }
            .dark-sort-select  { width: 100%; }
            .module-table th, .module-table td { font-size: 11px; padding: 8px 4px 8px 0; }
            .dist-bar-mini { width: 60px; }
            .rings-row     { flex-wrap: wrap; gap: 16px; }
        }

        @media (max-width: 480px) {
            .kpi-v2-number { font-size: 18px; }
            .kpi-card-v2   { padding: 12px; }
            .topbar-main   { padding: 8px 8px 8px 48px; }
        }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

@include('partials.sidebar')

<div class="main">

    {{-- ── TOPBAR ─────────────────────────────────────────────── --}}
    <div class="topbar">
        <div class="topbar-main">
            <div class="page-title-wrap" style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                {{-- Domain picker --}}
                <div class="domain-picker">
                    <span id="domainPickerLabel">{{ $selectedDomain ? $selectedDomain->name : 'Select Domain' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                         style="width:13px;height:13px"><polyline points="6 9 12 15 18 9"/></svg>
                    <select onchange="changeDomain(this.value)">
                        @php $currentLevelName = null; @endphp
                        @foreach($allDomains as $domain)
                            @if($domain->level_name !== $currentLevelName)
                                @if($currentLevelName !== null)</optgroup>@endif
                                <optgroup label="{{ $domain->level_name }} Level">
                                @php $currentLevelName = $domain->level_name; @endphp
                            @endif
                            <option value="{{ $domain->id }}"
                                {{ ($selectedDomain && $selectedDomain->id == $domain->id) ? 'selected' : '' }}>
                                {{ $domain->name }}
                            </option>
                        @endforeach
                        @if($currentLevelName !== null)</optgroup>@endif
                    </select>
                </div>
                <span style="font-size:13px;color:#9ca3af;font-weight:500;">— Module View</span>
            </div>
            @include('partials.topbar-filters')
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
                <button class="generate-report-btn" id="reportBtn" onclick="generateReport()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Generate Report
                </button>
                @include('partials.api-dot')
                <div class="user-chip">
                    <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <span>{{ $user->name }}</span>
                </div>
            </div>
        </div>
        <div class="topbar-breadcrumb-bar">
            <span>Home</span>
            <span>›</span>
            <span>Analytics</span>
            <span>›</span>
            <span>Domain</span>
            <span>›</span>
            <span style="color:#1a4fa8;font-weight:600;">Module View</span>
        </div>
    </div>

    {{-- ── CONTENT ───────────────────────────────────────────── --}}
    <div class="content">

        @php $studentsUrl = $studentsUrl ?? '#'; @endphp

        @if(!$hasCourses)

            <div style="background:var(--bg-card);border-radius:12px;padding:60px;text-align:center;color:var(--text-muted);font-size:14px;">
                No modules found for <strong>{{ $selectedDomain->name ?? 'this domain' }}</strong>.
            </div>

        @else

            @php
                $domainLabel = $selectedDomain->name ?? 'Domain';

                /* ── Build module collection ─────────────────── */
                $allModules = collect($courseStats)->map(function ($stats, $code) {
                    $total = $stats['total'] ?? 0;
                    $rate  = $total > 0 ? round($stats['pass'] / $total * 100, 1) : 0;
                    return (object)[
                        'code'        => $code,
                        'enrolled'    => $total,
                        'passed'      => $stats['pass'],
                        'in_progress' => $stats['progress'],
                        'not_started' => $stats['not_started'],
                        'failed'      => $stats['failed'],
                        'total'       => $total,
                        'pass_rate'   => $rate,
                    ];
                })->values();

                /* ── Stacked bar chart data (original order) ─── */
                $chartLabels   = $allModules->pluck('code')->toArray();
                $chartPassed   = $allModules->pluck('passed')->toArray();
                $chartProgress = $allModules->pluck('in_progress')->toArray();
                $chartNS       = $allModules->pluck('not_started')->toArray();
                $chartFailed   = $allModules->pluck('failed')->toArray();

                /* ── Donut data ──────────────────────────────── */
                $donutColors   = ['#4f6ef7', '#22c7b8', '#f7b84f'];
                $totalEnrolled = $allModules->sum('enrolled');
                $donutCounts   = $allModules->pluck('enrolled')->toArray();
                $donutPcts     = $allModules->map(fn($m) =>
                    $totalEnrolled > 0 ? round($m->enrolled / $totalEnrolled * 100) : 0
                )->toArray();

                /* ── Ring chart constants ─────────────────────── */
                $circ   = 2 * M_PI * 32;   // 201.06
                $offset = -($circ / 4);     // -50.27 (start from top)

                /* ── Y-axis max for stacked bar ──────────────── */
                $yMax = (int) (ceil(($totalEnrollment + 10) / 10) * 10 + 10);

                /* ── Table (sorted ascending by pass_rate) ────── */
                $tableModules = $allModules->sortBy('pass_rate')->values();
            @endphp

            {{-- Pass chart data to JavaScript --}}
            <script>
                const moduleChartData = {
                    labels:     @json($chartLabels),
                    passed:     @json($chartPassed),
                    inProgress: @json($chartProgress),
                    notStarted: @json($chartNS),
                    failed:     @json($chartFailed),
                };
                const scoreBandsData = @json($scoreBandsData);
                const chartYMax = {{ $yMax }};
            </script>


            {{-- ── SECTION 2 — KPI CARDS ───────────────────── --}}
            <div class="kpi-grid" style="margin-bottom:20px;">

                <div class="kpi-card-v2 blue">
                    <div class="kpi-card-v2-inner">
                        <div class="kpi-card-v2-body">
                            <div class="kpi-v2-label">Total Enrollment</div>
                            <div class="kpi-v2-number">{{ number_format($totalEnrollment) }}</div>
                            <div class="kpi-v2-sub">{{ $domainLabel }} domain</div>
                        </div>
                        <div class="kpi-icon-sq blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                <path d="M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="kpi-card-v2 green">
                    <div class="kpi-card-v2-inner">
                        <div class="kpi-card-v2-body">
                            <div class="kpi-v2-label">Completion Rate</div>
                            <div class="kpi-v2-number">{{ number_format($completionRate, 1) }}%</div>
                            <div class="kpi-v2-sub">Passed all modules</div>
                        </div>
                        <div class="kpi-icon-sq green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="kpi-card-v2 amber">
                    <div class="kpi-card-v2-inner">
                        <div class="kpi-card-v2-body">
                            <div class="kpi-v2-label">In Progress</div>
                            <div class="kpi-v2-number">{{ number_format($inProgressLearners) }}</div>
                            <div class="kpi-v2-sub">Still working through modules</div>
                        </div>
                        <div class="kpi-icon-sq amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="kpi-card-v2 red">
                    <div class="kpi-card-v2-inner">
                        <div class="kpi-card-v2-body">
                            <div class="kpi-v2-label">Not Started</div>
                            <div class="kpi-v2-number">{{ number_format($notStartedLearners) }}</div>
                            <div class="kpi-v2-sub">No activity yet</div>
                        </div>
                        <div class="kpi-icon-sq red">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>{{-- /kpi-grid --}}


            {{-- ── SECTION 3 — CHARTS ROW ──────────────────── --}}
            <div class="charts-row">

                {{-- LEFT — Stacked Bar Chart --}}
                <div class="chart-panel">
                    <div class="panel-title">
                        MODULE PROGRESS —<br>{{ strtoupper($domainLabel) }}
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item"><div class="legend-sq" style="background:#1d9e75"></div>Passed</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#f59e0b"></div>In Progress</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#d1d5db"></div>Not Started</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#e24b4a"></div>Failed</div>
                    </div>
                    <div style="position:relative;width:100%;height:260px;">
                        <canvas id="moduleStackedBar"></canvas>
                    </div>
                </div>

                {{-- RIGHT — Score Distribution --}}
                <div class="chart-panel">
                    <div class="panel-title">
                        SCORE DISTRIBUTION —<br>{{ strtoupper($domainLabel) }}
                    </div>
                    <div style="font-size:11px;color:#9ca3af;margin-bottom:10px;">
                        How learner scores cluster across 4 bands per module
                    </div>
                    <div class="chart-legend" style="margin-bottom:10px;">
                        <div class="legend-item"><div class="legend-sq" style="background:#e24b4a"></div>0–49% failing</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#f59e0b"></div>50–69% borderline</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#4f6ef7"></div>70–89% solid</div>
                        <div class="legend-item"><div class="legend-sq" style="background:#1d9e75"></div>90–100% strong</div>
                    </div>
                    <div style="position:relative;width:100%;height:200px;">
                        <canvas id="scoreBandsChart"></canvas>
                    </div>

                    {{-- Insight cards --}}
                    @php
                        $sbHardestMod = null; $sbHardestCnt = 0;
                        $sbBorderMod  = null; $sbBorderCnt  = 0;
                        $sbStrongMod  = null; $sbStrongCnt  = 0;
                        foreach ($scoreBandsData as $sbCode => $sbBands) {
                            if ($sbBands['failing']    > $sbHardestCnt) { $sbHardestCnt = $sbBands['failing'];    $sbHardestMod = $sbCode; }
                            if ($sbBands['borderline'] > $sbBorderCnt)  { $sbBorderCnt  = $sbBands['borderline']; $sbBorderMod  = $sbCode; }
                            if ($sbBands['strong']     > $sbStrongCnt)  { $sbStrongCnt  = $sbBands['strong'];     $sbStrongMod  = $sbCode; }
                        }
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px;">
                        <div class="score-insight score-insight--red">
                            <div class="score-insight-lbl">Hardest module</div>
                            <div class="score-insight-val" style="color:#e24b4a;">{{ $sbHardestMod ?? '—' }}</div>
                            <div class="score-insight-sub">{{ $sbHardestCnt }} learners below 50%</div>
                        </div>
                        <div class="score-insight score-insight--amber">
                            <div class="score-insight-lbl">Most borderline</div>
                            <div class="score-insight-val" style="color:#f59e0b;">{{ $sbBorderMod ?? '—' }}</div>
                            <div class="score-insight-sub">{{ $sbBorderCnt }} in 50–69% band</div>
                        </div>
                        <div class="score-insight score-insight--green">
                            <div class="score-insight-lbl">Best performer</div>
                            <div class="score-insight-val" style="color:#1d9e75;">{{ $sbStrongMod ?? '—' }}</div>
                            <div class="score-insight-sub">{{ $sbStrongCnt }} learners above 90%</div>
                        </div>
                    </div>
                </div>

            </div>{{-- /charts-row --}}


            {{-- ── SECTION 4 — MODULE BREAKDOWN TABLE ─────── --}}
            <div class="module-card">
                <div class="module-card-header">
                    <div>
                        <div class="module-card-title">
                            MODULE BREAKDOWN &mdash;<br>{{ strtoupper($domainLabel) }}
                        </div>
                    </div>
                    <div class="table-controls">
                        <div class="dark-search-wrap">
                            <svg class="dark-search-icon" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" id="module-search" class="dark-search-input"
                                   placeholder="Search module...">
                        </div>
                        <select id="sort-select" class="dark-sort-select">
                            <option value="pass_rate_asc" selected>Pass Rate ↑</option>
                            <option value="pass_rate_desc">Pass Rate ↓</option>
                            <option value="enrolled">Enrolled</option>
                            <option value="passed">Passed</option>
                            <option value="name">Module Name</option>
                        </select>
                    </div>
                </div>

                <table class="module-table" id="module-table">
                    <thead>
                        <tr>
                            <th style="width:70px">Module</th>
                            <th style="width:80px">Enrolled</th>
                            <th style="width:75px">Passed</th>
                            <th style="width:90px">In Progress</th>
                            <th style="width:95px">Not Started</th>
                            <th style="width:65px">Failed</th>
                            <th style="width:120px">Distribution</th>
                            <th style="width:85px">Pass Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableModules as $mod)
                            @php
                                $total   = $mod->total > 0 ? $mod->total : 1;
                                $passPct = round($mod->passed / $total * 100);
                                $progPct = round($mod->in_progress / $total * 100);
                                $nsPct   = round($mod->not_started / $total * 100);
                                $failPct = max(0, 100 - $passPct - $progPct - $nsPct);
                                $rateClass = $mod->pass_rate >= 50 ? 'rate-text-green'
                                           : ($mod->pass_rate >= 30 ? 'rate-text-amber' : 'rate-text-red');
                            @endphp
                            <tr data-module="{{ $mod->code }}"
                                data-pass-rate="{{ $mod->pass_rate }}"
                                data-passed="{{ $mod->passed }}"
                                data-enrolled="{{ $mod->enrolled }}"
                                onclick="viewLearners('{{ $mod->code }}')"
                                title="View learners for {{ $mod->code }}">
                                <td><span class="mod-code">{{ $mod->code }}</span></td>
                                <td>{{ $mod->enrolled }}</td>
                                <td class="count-green">{{ $mod->passed }}</td>
                                <td class="count-amber">{{ $mod->in_progress }}</td>
                                <td class="count-muted">{{ $mod->not_started }}</td>
                                <td class="count-red">{{ $mod->failed }}</td>
                                <td>
                                    <div class="dist-bar-mini">
                                        @if($passPct > 0)<div class="dist-seg" style="width:{{ $passPct }}%;background:#1d9e75;"></div>@endif
                                        @if($progPct > 0)<div class="dist-seg" style="width:{{ $progPct }}%;background:#f59e0b;"></div>@endif
                                        @if($nsPct  > 0)<div class="dist-seg" style="width:{{ $nsPct  }}%;background:#d1d5db;"></div>@endif
                                        @if($failPct > 0)<div class="dist-seg" style="width:{{ $failPct }}%;background:#e24b4a;"></div>@endif
                                    </div>
                                </td>
                                <td class="{{ $rateClass }}">{{ number_format($mod->pass_rate, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;">
                                    No module data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="table-footnote">
                    <span style="color:#1d9e75;">Green ≥ 50%</span>
                    <span style="color:#9ca3af;"> · </span>
                    <span style="color:#f59e0b;">Amber 30–49%</span>
                    <span style="color:#9ca3af;"> · </span>
                    <span style="color:#e24b4a;">Red &lt; 30%</span>
                    <span style="color:#9ca3af;"> · Click any row to view learner-level detail</span>
                </div>
            </div>{{-- /module-card --}}


            {{-- ── SECTION 5 — BOTTOM ROW ──────────────────── --}}
            <div class="bottom-row">

                {{-- WEAKEST TOPICS --}}
                <div class="topic-card">
                    <div class="topic-card-title">Weakest Topics</div>

                    @forelse($weakTopics as $topic)
                        @php
                            $sc    = (float) $topic->avg_progress;
                            if ($sc < 45) {
                                $badgeBg   = '#fca5a5';
                                $badgeText = '#b91c1c';
                                $barFill   = '#ef4444';
                                $scoreCol  = '#e24b4a';
                            } else {
                                $badgeBg   = '#fde68a';
                                $badgeText = '#92400e';
                                $barFill   = '#f59e0b';
                                $scoreCol  = '#f59e0b';
                            }
                        @endphp
                        <div class="topic-row">
                            <div class="topic-badge"
                                 style="background:{{ $badgeBg }};color:{{ $badgeText }}">
                                {{ $topic->course_code ?? '—' }}
                            </div>
                            <div class="topic-info">
                                <div class="topic-name" title="{{ $topic->module_title }}">
                                    {{ $topic->module_title }}
                                </div>
                                <div class="topic-bar-track">
                                    <div class="topic-bar-fill"
                                         style="width:{{ $sc }}%;background:{{ $barFill }};"></div>
                                </div>
                            </div>
                            <div class="topic-score" style="color:{{ $scoreCol }}">
                                {{ round($sc) }}%
                            </div>
                        </div>
                    @empty
                        <p style="font-size:13px;color:#9ca3af">No data available.</p>
                    @endforelse
                </div>

                {{-- STRONGEST TOPICS --}}
                <div class="topic-card">
                    <div class="topic-card-title">Strongest Topics</div>

                    @forelse($strongTopics as $topic)
                        @php
                            $sc2 = (float) $topic->avg_progress;
                        @endphp
                        <div class="topic-row">
                            <div class="topic-badge"
                                 style="background:#d1fae5;color:#065f46">
                                {{ $topic->course_code ?? '—' }}
                            </div>
                            <div class="topic-info">
                                <div class="topic-name" title="{{ $topic->module_title }}">
                                    {{ $topic->module_title }}
                                </div>
                                <div class="topic-bar-track">
                                    <div class="topic-bar-fill"
                                         style="width:{{ $sc2 }}%;background:#1d9e75;"></div>
                                </div>
                            </div>
                            <div class="topic-score" style="color:#1d9e75">
                                {{ round($sc2) }}%
                            </div>
                        </div>
                    @empty
                        <p style="font-size:13px;color:#9ca3af">No data available.</p>
                    @endforelse
                </div>

            </div>{{-- /bottom-row --}}

        @endif

    </div>{{-- /content --}}
</div>{{-- /main --}}


<script>
const _userName         = @json($user->name);
const _studentsUrl      = @json($studentsUrl ?? '#');
const _selectedCohort   = @json(request('cohort') ?? '');
const _selectedAgency   = @json(request('agency') ?? '');
const _baseUrl          = '{{ request()->url() }}';
const _domainId         = @json($selectedDomain->id ?? null);
const _moduleLearnerUrl  = '{{ route("api.module.learners") }}';
const _sbLearnerUrl      = '{{ route("api.score.band.learners") }}';
const _domain           = @json($selectedDomain->name ?? 'Domain');
const _kpi = {
    total:      {{ $totalEnrollment      ?? 0 }},
    rate:       {{ $completionRate       ?? 0 }},
    inProgress: {{ $inProgressLearners   ?? 0 }},
    notStarted: {{ $notStartedLearners   ?? 0 }},
};
@php
    $rptWeak   = ($weakTopics   ?? collect())->map(fn($t) => ['code' => $t->course_code ?? '', 'title' => $t->module_title ?? '', 'score' => (float)($t->avg_progress ?? 0)])->values();
    $rptStrong = ($strongTopics ?? collect())->map(fn($t) => ['code' => $t->course_code ?? '', 'title' => $t->module_title ?? '', 'score' => (float)($t->avg_progress ?? 0)])->values();
    $rptCohort = ($cohorts  ?? collect())->firstWhere('group_id',      request('cohort'))?->name  ?? '';
    $rptAgency = ($agencies ?? collect())->firstWhere('department_id', request('agency'))?->name  ?? '';
@endphp
const _weakTopics   = @json($rptWeak);
const _strongTopics = @json($rptStrong);
const _cohortName   = @json($rptCohort);
const _agencyName   = @json($rptAgency);

/* ── Domain picker navigation ─────────────────────────────── */
function changeDomain(domainId) {
    const params = new URLSearchParams(window.location.search);
    params.set('domain_id', domainId);
    window.location.href = _baseUrl + '?' + params.toString();
}

/* ── Row click → Student Progress filtered by module ─────── */
function viewLearners(moduleCode) {
    const params = new URLSearchParams();
    params.set('module_code', moduleCode);
    if (_selectedCohort) params.set('cohort', _selectedCohort);
    if (_selectedAgency) params.set('agency', _selectedAgency);
    window.location.href = _studentsUrl + '?' + params.toString();
}

@if($hasCourses ?? false)
/* ── Stacked Bar Chart ────────────────────────────────────── */
(function () {
    const ctx = document.getElementById('moduleStackedBar').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: moduleChartData.labels,
            datasets: [
                {
                    label: 'Passed',
                    data: moduleChartData.passed,
                    backgroundColor: '#1d9e75',
                    stack: 'main',
                    borderRadius: 0,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'In Progress',
                    data: moduleChartData.inProgress,
                    backgroundColor: '#f59e0b',
                    stack: 'main',
                    borderRadius: 0,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Not Started',
                    data: moduleChartData.notStarted,
                    backgroundColor: '#d1d5db',
                    stack: 'main',
                    borderRadius: 0,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                },
                {
                    label: 'Failed',
                    data: moduleChartData.failed,
                    backgroundColor: '#e24b4a',
                    stack: 'main',
                    borderRadius: 0,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const idx = elements[0].index;
                    openLmModal(moduleChartData.labels[idx]);
                }
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#1a1f36',
                    titleColor: '#9ca3af',
                    bodyColor: '#ffffff',
                    titleFont: { family: 'Poppins', size: 11 },
                    bodyFont: { family: 'Poppins', size: 12 },
                    padding: { x: 10, y: 8 },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#9ca3af' },
                },
                y: {
                    stacked: true,
                    min: 0,
                    max: chartYMax,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Poppins', size: 10 },
                        color: '#9ca3af',
                        stepSize: 50,
                    },
                },
            },
        },
    });
})();

/* ── Score Distribution Grouped Bar ──────────────────────── */
(function () {
    const bands  = scoreBandsData;
    const labels = Object.keys(bands);
    if (!labels.length) return;

    const failing    = labels.map(l => bands[l].failing);
    const borderline = labels.map(l => bands[l].borderline);
    const solid      = labels.map(l => bands[l].solid);
    const strong     = labels.map(l => bands[l].strong);
    const totals     = labels.map(l => bands[l].total);

    const ctx = document.getElementById('scoreBandsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: '0–49% failing',     data: failing,    backgroundColor: '#e24b4a' },
                { label: '50–69% borderline', data: borderline, backgroundColor: '#f59e0b' },
                { label: '70–89% solid',      data: solid,      backgroundColor: '#4f6ef7' },
                { label: '90–100% strong',    data: strong,     backgroundColor: '#1d9e75' },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (event, elements) => {
                if (!elements.length) return;
                const el         = elements[0];
                const courseCode = labels[el.index];
                const bandKeys   = ['failing', 'borderline', 'solid', 'strong'];
                openSbModal(courseCode, bandKeys[el.datasetIndex]);
            },
            onHover: (event, elements) => {
                event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleColor: '#f9fafb',
                    bodyColor: '#d1d5db',
                    titleFont: { family: 'Poppins', size: 12, weight: '700' },
                    bodyFont:  { family: 'Poppins', size: 12 },
                    padding: 10,
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (item) => {
                            const total = totals[item.dataIndex] || 1;
                            const pct   = Math.round(item.parsed.y / total * 100);
                            return ` ${item.dataset.label}: ${item.parsed.y} learners (${pct}%)`;
                        },
                        labelColor: (item) => ({
                            borderColor: 'transparent',
                            backgroundColor: item.dataset.backgroundColor,
                            borderRadius: 3,
                        }),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { family: 'Poppins', size: 11 }, color: '#6b7280' },
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Poppins', size: 10 },
                        color: '#9ca3af',
                        stepSize: 10,
                    },
                },
            },
        },
    });
})();
@endif

/* ── Table search ─────────────────────────────────────────── */
document.getElementById('module-search')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#module-table tbody tr').forEach(row => {
        const code = (row.dataset.module ?? '').toLowerCase();
        row.style.display = code.includes(q) ? '' : 'none';
    });
});

/* ── Table sort ───────────────────────────────────────────── */
document.getElementById('sort-select')?.addEventListener('change', function () {
    const sortBy = this.value;
    const tbody  = document.querySelector('#module-table tbody');
    const rows   = Array.from(tbody.querySelectorAll('tr[data-module]'));
    rows.sort((a, b) => {
        if (sortBy === 'pass_rate_asc')  return parseFloat(a.dataset.passRate)  - parseFloat(b.dataset.passRate);
        if (sortBy === 'pass_rate_desc') return parseFloat(b.dataset.passRate)  - parseFloat(a.dataset.passRate);
        if (sortBy === 'passed')         return parseInt(b.dataset.passed)       - parseInt(a.dataset.passed);
        if (sortBy === 'enrolled')       return parseInt(b.dataset.enrolled)     - parseInt(a.dataset.enrolled);
        return (a.dataset.module ?? '').localeCompare(b.dataset.module ?? '');
    });
    rows.forEach(r => tbody.appendChild(r));
});

/* ── Learner Modal ────────────────────────────────────────── */
let _lmAllLearners = [];

function openLmModal(courseCode) {
    const overlay = document.getElementById('lm-overlay');

    // Populate module select from chart labels
    const moduleSelect = document.getElementById('lm-module-select');
    moduleSelect.innerHTML = moduleChartData.labels
        .map(l => `<option value="${l}"${l === courseCode ? ' selected' : ''}>${l}</option>`)
        .join('');

    // Reset status filter
    document.getElementById('lm-status-select').value = 'all';

    // Update subtitle with current filter context
    const parts = [];
    if (_selectedCohort) parts.push('Cohort filtered');
    if (_selectedAgency) parts.push('Agency filtered');
    document.getElementById('lm-subtitle').textContent =
        parts.length ? parts.join(' · ') : 'All cohorts & agencies';

    overlay.classList.add('open');
    lmFetch(courseCode);
}

function closeLmModal(event) {
    if (event && event.target !== document.getElementById('lm-overlay')) return;
    document.getElementById('lm-overlay').classList.remove('open');
    _lmAllLearners = [];
}

async function lmFetch(courseCode) {
    const list = document.getElementById('lm-list');
    list.innerHTML = '<div class="lm-loading">Loading learners…</div>';

    const params = new URLSearchParams({ course_code: courseCode });
    if (_domainId)       params.set('domain_id', _domainId);
    if (_selectedCohort) params.set('cohort',    _selectedCohort);
    if (_selectedAgency) params.set('agency',    _selectedAgency);
    if (new URL(window.location.href).searchParams.has('include_lessons')) params.set('include_lessons', '1');

    try {
        const resp = await fetch(_moduleLearnerUrl + '?' + params.toString(), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await resp.json();
        _lmAllLearners = data.learners ?? [];
        lmRender();
    } catch (_) {
        list.innerHTML = '<div class="lm-empty">Failed to load learners.</div>';
    }
}

function lmOnModuleChange(courseCode) {
    document.getElementById('lm-status-select').value = 'all';
    lmFetch(courseCode);
}

const LM_STATUS = {
    pass:        { label: 'Passed',      color: '#1d9e75', bg: '#f0fdf4' },
    progress:    { label: 'In Progress', color: '#f59e0b', bg: '#fffbeb' },
    not_started: { label: 'Not Started', color: '#9ca3af', bg: '#f3f4f6' },
    failed:      { label: 'Failed',      color: '#e24b4a', bg: '#fff1f2' },
};

function lmRender() {
    const statusFilter = document.getElementById('lm-status-select').value;
    const list = document.getElementById('lm-list');

    // Update summary counts (always based on full list)
    const counts = { pass: 0, progress: 0, not_started: 0, failed: 0 };
    _lmAllLearners.forEach(l => { counts[l.status] = (counts[l.status] || 0) + 1; });
    document.getElementById('lm-cnt-pass').textContent     = counts.pass;
    document.getElementById('lm-cnt-progress').textContent = counts.progress;
    document.getElementById('lm-cnt-ns').textContent       = counts.not_started;
    document.getElementById('lm-cnt-failed').textContent   = counts.failed;

    const filtered = statusFilter === 'all'
        ? _lmAllLearners
        : _lmAllLearners.filter(l => l.status === statusFilter);

    if (filtered.length === 0) {
        list.innerHTML = '<div class="lm-empty">No learners match this filter.</div>';
        return;
    }

    list.innerHTML = filtered.map(l => {
        const initials = l.name.trim().split(/\s+/).slice(0, 2)
            .map(w => w[0] || '').join('').toUpperCase() || '?';
        const s = LM_STATUS[l.status] ?? { label: l.status, color: '#9ca3af', bg: '#f3f4f6' };
        return `<div class="lm-learner-row">
            <div class="lm-avatar">${initials}</div>
            <div class="lm-name">${l.name}</div>
            <div class="lm-pill" style="color:${s.color};background:${s.bg}">${s.label}</div>
        </div>`;
    }).join('');
}

/* ── Score Band Modal ─────────────────────────────────────── */
let _sbAllLearners = [];

function openSbModal(courseCode, band) {
    const overlay      = document.getElementById('sb-overlay');
    const moduleSelect = document.getElementById('sb-module-select');
    moduleSelect.innerHTML = Object.keys(scoreBandsData)
        .map(l => `<option value="${l}"${l === courseCode ? ' selected' : ''}>${l}</option>`)
        .join('');

    document.getElementById('sb-band-select').value = band || 'all';

    const parts = [];
    if (_selectedCohort) parts.push('Cohort filtered');
    if (_selectedAgency) parts.push('Agency filtered');
    document.getElementById('sb-subtitle').textContent =
        parts.length ? parts.join(' · ') : 'All cohorts & agencies';

    overlay.classList.add('open');
    sbFetch(courseCode);
}

function closeSbModal(event) {
    if (event && event.target !== document.getElementById('sb-overlay')) return;
    document.getElementById('sb-overlay').classList.remove('open');
    _sbAllLearners = [];
}

async function sbFetch(courseCode) {
    const list = document.getElementById('sb-list');
    list.innerHTML = '<div class="lm-loading">Loading learners…</div>';

    const params = new URLSearchParams({ course_code: courseCode });
    if (_domainId)       params.set('domain_id', _domainId);
    if (_selectedCohort) params.set('cohort',    _selectedCohort);
    if (_selectedAgency) params.set('agency',    _selectedAgency);
    if (new URL(window.location.href).searchParams.has('include_lessons')) params.set('include_lessons', '1');

    try {
        const resp = await fetch(_sbLearnerUrl + '?' + params.toString(), {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        });
        const data = await resp.json();
        _sbAllLearners = data.learners ?? [];
        sbRender();
    } catch (_) {
        list.innerHTML = '<div class="lm-empty">Failed to load learners.</div>';
    }
}

function sbOnModuleChange(courseCode) {
    document.getElementById('sb-band-select').value = 'all';
    sbFetch(courseCode);
}

const SB_BAND = {
    failing:    { label: '0–49% Failing',     color: '#e24b4a', bg: '#fff1f2' },
    borderline: { label: '50–69% Borderline', color: '#f59e0b', bg: '#fffbeb' },
    solid:      { label: '70–89% Solid',      color: '#4f6ef7', bg: '#eff6ff' },
    strong:     { label: '90–100% Strong',    color: '#1d9e75', bg: '#f0fdf4' },
};

function sbRender() {
    const bandFilter = document.getElementById('sb-band-select').value;
    const list       = document.getElementById('sb-list');

    const counts = { failing: 0, borderline: 0, solid: 0, strong: 0 };
    _sbAllLearners.forEach(l => { counts[l.band] = (counts[l.band] || 0) + 1; });
    document.getElementById('sb-cnt-failing').textContent    = counts.failing;
    document.getElementById('sb-cnt-borderline').textContent = counts.borderline;
    document.getElementById('sb-cnt-solid').textContent      = counts.solid;
    document.getElementById('sb-cnt-strong').textContent     = counts.strong;

    const filtered = bandFilter === 'all'
        ? _sbAllLearners
        : _sbAllLearners.filter(l => l.band === bandFilter);

    if (filtered.length === 0) {
        list.innerHTML = '<div class="lm-empty">No learners match this filter.</div>';
        return;
    }

    list.innerHTML = filtered.map(l => {
        const initials = l.name.trim().split(/\s+/).slice(0, 2)
            .map(w => w[0] || '').join('').toUpperCase() || '?';
        const b = SB_BAND[l.band] ?? { label: l.band, color: '#9ca3af', bg: '#f3f4f6' };
        return `<div class="lm-learner-row">
            <div class="lm-avatar">${initials}</div>
            <div class="lm-name">${l.name}</div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <div class="lm-pill" style="color:${b.color};background:${b.bg}">${b.label}</div>
                <span style="font-size:12px;font-weight:700;color:#374151;min-width:36px;text-align:right">${l.score}%</span>
            </div>
        </div>`;
    }).join('');
}

/* ── Generate Report (jsPDF) ─────────────────────────────── */
async function logReport(title, format) {
    try {
        await fetch('{{ route("api.report.log") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ title, format }),
        });
    } catch (_) {}
}

async function generateReport() {
    const btn      = document.getElementById('reportBtn');
    const origHTML = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Opening...';

    try {
        const currentUrl = new URL(window.location.href);
        const cohort   = currentUrl.searchParams.get('cohort')    || '';
        const agency   = currentUrl.searchParams.get('agency')    || '';
        const domainId = currentUrl.searchParams.get('domain_id') || '';

        const baseUrl = {!! $isPc ? json_encode(route('pc.reports.module.generate')) : json_encode(route('admin.reports.module.generate')) !!};
        const params  = new URLSearchParams();
        if (domainId) params.append('domain_id', domainId);
        if (cohort)   params.append('cohort', cohort);
        if (agency)   params.append('agency', agency);

        window.open(baseUrl + (params.toString() ? '?' + params.toString() : ''), '_blank');
        await logReport('Module Analytics Report', 'PDF');
    } catch (err) {
        console.error('Report error:', err);
        alert('Failed to open report. Please try again.');
    } finally {
        btn.disabled   = false;
        btn.innerHTML  = origHTML;
    }
}
</script>

@include('partials.api-status')

{{-- ── LEARNER POPUP MODAL ──────────────────────────────────── --}}
<div id="lm-overlay" class="lm-overlay" onclick="closeLmModal(event)">
    <div class="lm-modal" onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="lm-header">
            <div>
                <div class="lm-header-title">Module Progress Detail</div>
                <div class="lm-header-sub" id="lm-subtitle">—</div>
            </div>
            <button class="lm-close-btn" onclick="closeLmModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Filters --}}
        <div class="lm-filters">
            <select id="lm-module-select" class="lm-select" onchange="lmOnModuleChange(this.value)">
                {{-- populated from JS --}}
            </select>
            <select id="lm-status-select" class="lm-select" onchange="lmRender()">
                <option value="all">All Statuses</option>
                <option value="pass">Passed</option>
                <option value="progress">In Progress</option>
                <option value="not_started">Not Started</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        {{-- Stat summary --}}
        <div class="lm-stats">
            <div class="lm-stat">
                <div class="lm-stat-num" id="lm-cnt-pass" style="color:#1d9e75">0</div>
                <div class="lm-stat-label" style="color:#1d9e75">Passed</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="lm-cnt-progress" style="color:#f59e0b">0</div>
                <div class="lm-stat-label" style="color:#f59e0b">In Progress</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="lm-cnt-ns" style="color:#9ca3af">0</div>
                <div class="lm-stat-label" style="color:#9ca3af">Not Started</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="lm-cnt-failed" style="color:#e24b4a">0</div>
                <div class="lm-stat-label" style="color:#e24b4a">Failed</div>
            </div>
        </div>

        {{-- Scrollable list --}}
        <div class="lm-list" id="lm-list">
            <div class="lm-empty">Click a bar to load learner details.</div>
        </div>

    </div>
</div>

{{-- ── SCORE BAND DRILL-DOWN MODAL ─────────────────────────── --}}
<div id="sb-overlay" class="lm-overlay" onclick="closeSbModal(event)">
    <div class="lm-modal" onclick="event.stopPropagation()">

        {{-- Header --}}
        <div class="lm-header">
            <div>
                <div class="lm-header-title">Score Distribution Detail</div>
                <div class="lm-header-sub" id="sb-subtitle">—</div>
            </div>
            <button class="lm-close-btn" onclick="closeSbModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Filters --}}
        <div class="lm-filters">
            <select id="sb-module-select" class="lm-select" onchange="sbOnModuleChange(this.value)">
                {{-- populated from JS --}}
            </select>
            <select id="sb-band-select" class="lm-select" onchange="sbRender()">
                <option value="all">All Bands</option>
                <option value="failing">0–49% Failing</option>
                <option value="borderline">50–69% Borderline</option>
                <option value="solid">70–89% Solid</option>
                <option value="strong">90–100% Strong</option>
            </select>
        </div>

        {{-- Stat summary --}}
        <div class="lm-stats">
            <div class="lm-stat">
                <div class="lm-stat-num" id="sb-cnt-failing" style="color:#e24b4a">0</div>
                <div class="lm-stat-label" style="color:#e24b4a">Failing</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="sb-cnt-borderline" style="color:#f59e0b">0</div>
                <div class="lm-stat-label" style="color:#f59e0b">Borderline</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="sb-cnt-solid" style="color:#4f6ef7">0</div>
                <div class="lm-stat-label" style="color:#4f6ef7">Solid</div>
            </div>
            <div class="lm-stat">
                <div class="lm-stat-num" id="sb-cnt-strong" style="color:#1d9e75">0</div>
                <div class="lm-stat-label" style="color:#1d9e75">Strong</div>
            </div>
        </div>

        {{-- Scrollable list --}}
        <div class="lm-list" id="sb-list">
            <div class="lm-empty">Click a bar to load learner details.</div>
        </div>

    </div>
</div>

</body>
</html>
