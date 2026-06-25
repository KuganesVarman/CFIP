<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFIP Dashboard Overview Report — {{ now()->format('d F Y') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ──────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ──────────────────────────────────────────────── */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            background: #f1f5f9;
            line-height: 1.5;
        }

        /* ── Print button (hidden when printing) ─────────────── */
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1a2744;
            color: #fff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }
        .print-bar-title { font-size: 13px; font-weight: 500; opacity: .85; }
        .print-bar-actions { display: flex; gap: 10px; }
        .btn-print {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-print:hover { background: #2563eb; }
        .btn-close {
            background: rgba(255,255,255,.12);
            color: #fff;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-family: inherit;
        }
        .btn-close:hover { background: rgba(255,255,255,.22); }

        /* ── Report wrapper ──────────────────────────────────── */
        .report-wrap {
            max-width: 900px;
            margin: 60px auto 40px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
        }

        /* ── Report header ───────────────────────────────────── */
        .rpt-header {
            background: linear-gradient(135deg, #1a2744 0%, #0d1b3e 100%);
            color: #fff;
            padding: 28px 32px 22px;
            position: relative;
        }
        .rpt-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .rpt-tag {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #93c5fd;
            margin-bottom: 6px;
        }
        .rpt-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.01em;
            line-height: 1.2;
        }
        .rpt-subtitle {
            font-size: 12px;
            color: #93c5fd;
            margin-top: 4px;
        }
        .rpt-date-col { text-align: right; flex-shrink: 0; padding-left: 20px; }
        .rpt-date { font-size: 15px; font-weight: 600; }
        .rpt-confidential {
            display: inline-block;
            margin-top: 8px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            padding: 3px 10px;
            border-radius: 3px;
        }

        /* ── Report footer ───────────────────────────────────── */
        .rpt-footer {
            border-top: 1px solid #e5e7eb;
            padding: 10px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
            background: #fff;
        }

        /* ── Report body ─────────────────────────────────────── */
        .rpt-body { padding: 0 32px 28px; }

        /* ── Section header ──────────────────────────────────── */
        .rpt-section {
            margin-top: 22px;
        }
        .rpt-section-header {
            background: #1a2744;
            color: #fff;
            padding: 9px 16px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        /* ── KPI cards ───────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 14px;
        }
        .kpi-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 14px 16px;
            background: #fff;
        }
        .kpi-val {
            font-size: 26px;
            font-weight: 700;
            color: #1a2744;
            line-height: 1.1;
        }
        .kpi-lbl {
            font-size: 11px;
            font-weight: 600;
            color: #4b5563;
            margin-top: 4px;
        }
        .kpi-sub {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 2px;
        }
        .kpi-card.blue  { border-top: 3px solid #3b82f6; }
        .kpi-card.teal  { border-top: 3px solid #14b8a6; }
        .kpi-card.amber { border-top: 3px solid #f59e0b; }
        .kpi-card.red   { border-top: 3px solid #ef4444; }

        /* ── Scope box ───────────────────────────────────────── */
        .scope-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 5px;
            padding: 9px 14px;
            font-size: 11px;
            color: #1e40af;
            margin-top: 10px;
        }
        .scope-box strong { font-weight: 700; }

        /* ── Trend chart ─────────────────────────────────────── */
        .trend-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            background: #fff;
        }
        .trend-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .trend-title { font-size: 12px; color: #6b7280; }
        .trend-rate { font-size: 26px; font-weight: 700; color: #3b82f6; }
        .trend-rate-sub { font-size: 10px; color: #9ca3af; }
        .trend-svg-wrap { overflow: hidden; }

        /* ── Tables ──────────────────────────────────────────── */
        .rpt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .rpt-table thead th {
            background: #1a2744;
            color: #fff;
            padding: 9px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
        }
        .rpt-table tbody tr:nth-child(even) { background: #f8fafc; }
        .rpt-table tbody td {
            padding: 9px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-green  { background: #dcfce7; color: #15803d; }
        .badge-amber  { background: #fef3c7; color: #92400e; }
        .badge-blue   { background: #dbeafe; color: #1d4ed8; }
        .badge-gray   { background: #f3f4f6; color: #6b7280; }

        /* ── Domain bars ─────────────────────────────────────── */
        .domain-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .domain-bars { }
        .domain-bar-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .domain-bar-name {
            font-size: 12px;
            color: #374151;
            width: 170px;
            flex-shrink: 0;
        }
        .domain-bar-track {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .domain-bar-fill {
            height: 8px;
            border-radius: 4px;
            background: #3b82f6;
        }
        .domain-bar-pct {
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            width: 40px;
            text-align: right;
            flex-shrink: 0;
        }

        /* ── Topic performance ───────────────────────────────── */
        .topic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .topic-section-title {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid;
        }
        .topic-section-title.weak   { color: #ef4444; border-color: #ef4444; }
        .topic-section-title.strong { color: #10b981; border-color: #10b981; }
        .topic-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }
        .topic-code {
            font-size: 9px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 3px;
            flex-shrink: 0;
            min-width: 34px;
            text-align: center;
        }
        .code-red   { background: #fee2e2; color: #b91c1c; }
        .code-green { background: #d1fae5; color: #065f46; }
        .code-blue  { background: #dbeafe; color: #1d4ed8; }
        .topic-name { font-size: 11px; color: #374151; flex: 1; }
        .topic-track {
            width: 70px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .topic-fill-weak   { height: 6px; border-radius: 3px; background: #ef4444; }
        .topic-fill-strong { height: 6px; border-radius: 3px; background: #10b981; }
        .topic-pct {
            font-size: 11px;
            font-weight: 700;
            width: 42px;
            text-align: right;
            flex-shrink: 0;
        }
        .pct-red   { color: #ef4444; }
        .pct-green { color: #10b981; }

        /* ── At-risk table ───────────────────────────────────── */
        .atrisk-val {
            font-size: 28px;
            font-weight: 700;
            color: #1a2744;
        }

        /* ── Agencies ────────────────────────────────────────── */
        .agency-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px 16px;
        }
        .agency-item {
            font-size: 11.5px;
            color: #374151;
            padding: 4px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        /* ── Observations ────────────────────────────────────── */
        .obs-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .obs-table tr { border-bottom: 1px solid #e5e7eb; }
        .obs-table td { padding: 11px 14px; vertical-align: top; }
        .obs-title {
            font-weight: 700;
            color: #1a2744;
            width: 190px;
            white-space: nowrap;
        }
        .obs-table tr:nth-child(even) { background: #f8fafc; }

        /* ── Print styles ────────────────────────────────────── */
        @media print {
            .print-bar { display: none !important; }
            body { background: #fff; font-size: 11px; }
            .report-wrap { margin: 0; box-shadow: none; max-width: 100%; }
            .rpt-body { padding: 0 24px 20px; }
            .rpt-section { page-break-inside: avoid; }
            @page { margin: 1.2cm 1.5cm; size: A4; }
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }
    </style>
</head>
<body>

{{-- Print bar --}}
<div class="print-bar no-print">
    <span class="print-bar-title">CFIP Dashboard Overview Report — {{ now()->format('d F Y') }}</span>
    <div class="print-bar-actions">
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
</div>

<div class="report-wrap">

    {{-- ── Report Header ──────────────────────────────────── --}}
    <div class="rpt-header">
        <div class="rpt-header-top">
            <div>
                <div class="rpt-tag">Dashboard Overview Report</div>
                <div class="rpt-title">CERTIFIED FINANCIAL INVESTIGATOR PROGRAM</div>
                <div class="rpt-subtitle">Academic Management System · Dashboard Overview Report</div>
            </div>
            <div class="rpt-date-col">
                <div class="rpt-date">{{ now()->format('d F Y') }}</div>
                <div class="rpt-confidential">CONFIDENTIAL</div>
            </div>
        </div>
    </div>

    {{-- ── Body ────────────────────────────────────────────── --}}
    <div class="rpt-body">

        {{-- 1. EXECUTIVE SUMMARY --}}
        <div class="rpt-section">
            <div class="rpt-section-header">1. &nbsp;Executive Summary</div>

            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                This report provides a comprehensive overview of learner performance, program progress, and domain analytics for the
                Certified Financial Investigator Program (CFIP) as at <strong>{{ now()->format('d F Y') }}</strong>.
                Data is sourced directly from the Academic Management System dashboard and covers all enrolled learners across all registered agencies.
            </p>

            <div class="kpi-grid">
                <div class="kpi-card blue">
                    <div class="kpi-val">{{ number_format($totalEnrollment) }}</div>
                    <div class="kpi-lbl">Total Enrollment</div>
                    <div class="kpi-sub">Across all agencies</div>
                </div>
                <div class="kpi-card teal">
                    <div class="kpi-val">{{ number_format($completionRate, 1) }}%</div>
                    <div class="kpi-lbl">Course Completion Rate</div>
                    <div class="kpi-sub">Entry Level overall</div>
                </div>
                <div class="kpi-card amber">
                    <div class="kpi-val">{{ number_format($inProgressLearners) }}</div>
                    <div class="kpi-lbl">Active Learners</div>
                    <div class="kpi-sub">Currently in progress</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-val">{{ number_format($atRiskCount) }}</div>
                    <div class="kpi-lbl">At-Risk Learners</div>
                    <div class="kpi-sub">0–49% overall progress</div>
                </div>
            </div>

            <div class="scope-box">
                <strong>Report Scope:</strong>
                {{ $cohortLabel }} &middot; {{ $agencyLabel }} &middot; All Program Levels &middot;
                Data generated: {{ now()->format('d F Y') }} &middot;
                Generated by: {{ $user->name }} ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }})
            </div>
        </div>

        {{-- 2. COHORT PROGRESS TREND --}}
        <div class="rpt-section">
            <div class="rpt-section-header">2. &nbsp;Cohort Progress Trend</div>

            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                The chart below tracks the simulated weekly course completion rate from cohort inception
                @if(count($trendLabels) > 0)
                    ({{ $trendLabels[0] }})
                @endif
                through to the current reporting date. The current overall completion rate stands at <strong>{{ $completionRate }}%</strong>.
            </p>

            <div class="trend-wrap">
                <div class="trend-header">
                    <div class="trend-title">Cohort progress — simulated S-curve</div>
                    <div style="text-align:right;">
                        <div class="trend-rate">{{ $completionRate }}%</div>
                        <div class="trend-rate-sub">Current completion</div>
                    </div>
                </div>

                @php
                    $svgW  = 800; $svgH  = 160;
                    $padL  = 36;  $padR  = 20;
                    $padT  = 10;  $padB  = 28;
                    $iW    = $svgW - $padL - $padR;
                    $iH    = $svgH - $padT - $padB;
                    $n     = count($trendData);
                    $yGrids = [0, 25, 50, 75, 100];

                    $svgPts  = [];
                    $fillPts = [];
                    foreach ($trendData as $i => $v) {
                        $x = $padL + ($n > 1 ? ($i / ($n - 1)) * $iW : $iW);
                        $y = $padT + $iH - ($v / 100) * $iH;
                        $svgPts[]  = compact('x', 'y');
                        $fillPts[] = "$x,$y";
                    }
                    $lineD = count($svgPts) > 0
                        ? 'M ' . collect($svgPts)->map(fn($p) => "{$p['x']},{$p['y']}")->join(' L ')
                        : '';
                    $fillD = count($fillPts) > 1
                        ? "M {$padL}," . ($padT + $iH) . " L " . implode(' L ', $fillPts) . " L " . ($padL + $iW) . "," . ($padT + $iH) . " Z"
                        : '';
                    $lastPt = count($svgPts) > 0 ? end($svgPts) : null;
                @endphp

                <div class="trend-svg-wrap">
                    <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" width="100%" preserveAspectRatio="xMidYMid meet" style="display:block;">
                        {{-- Y gridlines --}}
                        @foreach($yGrids as $pct)
                            @php $gy = $padT + $iH - ($pct / 100) * $iH; @endphp
                            <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $padL + $iW }}" y2="{{ $gy }}"
                                  stroke="#e5e7eb" stroke-width="1"/>
                            <text x="{{ $padL - 4 }}" y="{{ $gy + 4 }}" text-anchor="end"
                                  font-size="9" fill="#9ca3af" font-family="Poppins, Arial, sans-serif">{{ $pct }}%</text>
                        @endforeach

                        {{-- Filled area --}}
                        @if($fillD)
                            <path d="{{ $fillD }}" fill="#eff6ff" opacity="0.9"/>
                        @endif

                        {{-- Line --}}
                        @if($lineD)
                            <path d="{{ $lineD }}" fill="none" stroke="#3b82f6" stroke-width="2.5"
                                  stroke-linejoin="round" stroke-linecap="round"/>
                        @endif

                        {{-- X-axis labels --}}
                        @if($n > 0)
                            @php $step = max(1, (int) ceil($n / 8)); @endphp
                            @foreach($trendLabels as $i => $lbl)
                                @if($i % $step === 0 || $i === $n - 1)
                                    @php $lx = $padL + ($n > 1 ? ($i / ($n - 1)) * $iW : $iW); @endphp
                                    <text x="{{ $lx }}" y="{{ $padT + $iH + 18 }}"
                                          text-anchor="middle" font-size="9" fill="#9ca3af"
                                          font-family="Poppins, Arial, sans-serif">{{ $lbl }}</text>
                                @endif
                            @endforeach
                        @endif

                        {{-- End marker --}}
                        @if($lastPt)
                            <circle cx="{{ $lastPt['x'] }}" cy="{{ $lastPt['y'] }}" r="5"
                                    fill="white" stroke="#3b82f6" stroke-width="2.5"/>
                            <text x="{{ $lastPt['x'] + 8 }}" y="{{ $lastPt['y'] + 4 }}"
                                  font-size="10" font-weight="700" fill="#3b82f6"
                                  font-family="Poppins, Arial, sans-serif">{{ $completionRate }}%</text>
                        @endif
                    </svg>
                </div>
            </div>
        </div>

        {{-- 3. PROGRAM LEVEL PROGRESS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">3. &nbsp;Program Level Progress</div>

            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                The table below summarises completion progress across each certification level within the CFIP program.
                Only Entry Level is currently active with meaningful progress.
            </p>

            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Program Level</th>
                        <th>Completion Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($levelStats as $levelName => $stat)
                        <tr>
                            <td>{{ $levelName }} Level</td>
                            <td><strong>{{ number_format($stat['rate'], 1) }}%</strong></td>
                            <td>
                                @if($stat['rate'] >= 1)
                                    <span class="status-badge badge-green">Active</span>
                                @else
                                    <span class="status-badge badge-gray">Active</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 4. DOMAIN ANALYTICS — ENTRY LEVEL --}}
        <div class="rpt-section">
            <div class="rpt-section-header">4. &nbsp;Domain Analytics — Entry Level</div>

            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                Domain completion rates below reflect the proportion of learners who have fully passed each domain within the Entry Level curriculum.
            </p>

            <div class="domain-grid">
                {{-- Bar indicators --}}
                <div class="domain-bars">
                    @foreach($entryDomains as $domain)
                        <div class="domain-bar-row">
                            <div class="domain-bar-name">{{ $domain->name }}</div>
                            <div class="domain-bar-track">
                                <div class="domain-bar-fill" style="width:{{ min(100, $domain->rate) }}%;"></div>
                            </div>
                            <div class="domain-bar-pct">{{ number_format($domain->rate, 1) }}%</div>
                        </div>
                    @endforeach
                </div>

                {{-- Domain table --}}
                <div>
                    <table class="rpt-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th style="text-align:right;">Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entryDomains as $domain)
                                @php
                                    $domStatus = match(true) {
                                        $domain->rate >= 30 => ['label' => 'On Track',    'class' => 'badge-green'],
                                        $domain->rate >= 5  => ['label' => 'In Progress', 'class' => 'badge-blue'],
                                        default              => ['label' => 'Early Stage', 'class' => 'badge-amber'],
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $domain->name }}</td>
                                    <td style="text-align:right;font-weight:600;">{{ number_format($domain->rate, 1) }}%</td>
                                    <td><span class="status-badge {{ $domStatus['class'] }}">{{ $domStatus['label'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 5. TOPIC PERFORMANCE --}}
        <div class="rpt-section">
            <div class="rpt-section-header">5. &nbsp;Topic Performance</div>

            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                The following tables highlight the lowest and highest performing topics based on average learner scores across all completed attempts.
            </p>

            <div class="topic-grid">
                {{-- Weakest --}}
                <div>
                    <div class="topic-section-title weak">Weakest Modules</div>
                    @forelse($weakTopics as $topic)
                        <div class="topic-row">
                            <span class="topic-code code-red">{{ $topic->course_code ?? '—' }}</span>
                            <span class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</span>
                            <div class="topic-track">
                                <div class="topic-fill-weak" style="width:{{ min(100, $topic->avg_progress) }}%;"></div>
                            </div>
                            <span class="topic-pct pct-red">{{ $topic->avg_progress }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No data available.</p>
                    @endforelse
                </div>

                {{-- Strongest --}}
                <div>
                    <div class="topic-section-title strong">Strongest Modules</div>
                    @forelse($strongTopics as $topic)
                        <div class="topic-row">
                            <span class="topic-code code-green">{{ $topic->course_code ?? '—' }}</span>
                            <span class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</span>
                            <div class="topic-track">
                                <div class="topic-fill-strong" style="width:{{ min(100, $topic->avg_progress) }}%;"></div>
                            </div>
                            <span class="topic-pct pct-green">{{ $topic->avg_progress }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 6. AT-RISK LEARNERS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">6. &nbsp;At-Risk Learners</div>

            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                Learners below are identified as at-risk based on an overall progress of 0–49% across all assessed modules.
                @if($atRiskCount > 0)
                    A total of <strong>{{ $atRiskCount }}</strong> learner(s) require follow-up.
                @else
                    No learners are currently at risk.
                @endif
            </p>

            @if($atRiskLearners->isNotEmpty())
                <table class="rpt-table">
                    <thead>
                        <tr>
                            <th style="width:36px;">#</th>
                            <th>Learner Name</th>
                            <th>Department / Agency</th>
                            <th style="width:120px;">Progress</th>
                            <th style="width:130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($atRiskLearners as $i => $learner)
                            @php $progColor = $learner->progress < 20 ? '#dc2626' : '#f59e0b'; @endphp
                            <tr>
                                <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                                <td><strong>{{ $learner->name }}</strong></td>
                                <td style="color:#6b7280;">{{ $learner->dept }}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
                                            <div style="height:6px;border-radius:3px;background:{{ $progColor }};width:{{ $learner->progress }}%;"></div>
                                        </div>
                                        <span style="font-size:11px;font-weight:700;color:{{ $progColor }};width:34px;text-align:right;flex-shrink:0;">{{ $learner->progress }}%</span>
                                    </div>
                                </td>
                                <td><span class="status-badge badge-amber">Follow-up required</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="font-size:12px;color:#9ca3af;padding:12px 0;">No at-risk learners at this time. All learners are progressing above 49%.</p>
            @endif
        </div>

        {{-- 7. REGISTERED AGENCIES --}}
        <div class="rpt-section">
            <div class="rpt-section-header">7. &nbsp;Registered Agencies</div>

            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                The following government agencies and institutions are enrolled in the CFIP program:
            </p>

            <div class="agency-grid">
                @foreach($allAgencies as $i => $agency)
                    <div class="agency-item">{{ $i + 1 }}. {{ $agency->name }}</div>
                @endforeach
            </div>
        </div>

        {{-- 8. OBSERVATIONS & RECOMMENDATIONS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">8. &nbsp;Observations &amp; Recommendations</div>

            <table class="obs-table">
                <tbody>
                    @foreach($observations as $i => $obs)
                        <tr>
                            <td class="obs-title">{{ $i + 1 }}. {{ $obs['title'] }}</td>
                            <td style="color:#374151;">{{ $obs['text'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>{{-- /rpt-body --}}

    {{-- Footer --}}
    <div class="rpt-footer">
        <span>
            Generated by: {{ $user->name }}
            ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }})
            &middot; Date: {{ now()->format('d F Y') }}
            &middot; CERTIFIED FINANCIAL INVESTIGATOR PROGRAM — Academic Management System CFIP — CONFIDENTIAL
        </span>
    </div>

</div>{{-- /report-wrap --}}

</body>
</html>
