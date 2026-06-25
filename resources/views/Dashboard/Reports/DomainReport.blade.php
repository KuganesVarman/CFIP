<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFIP Domain Analytics Report — {{ $selectedLevel->name }} — {{ now()->format('d F Y') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            background: #f1f5f9;
            line-height: 1.5;
        }

        /* ── Print bar ───────────────────────────────────────── */
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
            background: #3b82f6; color: #fff; border: none;
            padding: 7px 18px; border-radius: 6px;
            font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit;
        }
        .btn-print:hover { background: #2563eb; }
        .btn-close {
            background: rgba(255,255,255,.12); color: #fff; border: none;
            padding: 7px 14px; border-radius: 6px;
            font-size: 13px; cursor: pointer; font-family: inherit;
        }
        .btn-close:hover { background: rgba(255,255,255,.22); }

        /* ── Wrapper ─────────────────────────────────────────── */
        .report-wrap {
            max-width: 900px;
            margin: 60px auto 40px;
            background: #fff;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
        }

        /* ── Header ──────────────────────────────────────────── */
        .rpt-header {
            background: linear-gradient(135deg, #1a2744 0%, #0d1b3e 100%);
            color: #fff;
            padding: 28px 32px 22px;
        }
        .rpt-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .rpt-tag {
            font-size: 10px; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            color: #93c5fd; margin-bottom: 6px;
        }
        .rpt-title { font-size: 22px; font-weight: 700; letter-spacing: -.01em; line-height: 1.2; }
        .rpt-subtitle { font-size: 12px; color: #93c5fd; margin-top: 4px; }
        .rpt-date-col { text-align: right; flex-shrink: 0; padding-left: 20px; }
        .rpt-date { font-size: 15px; font-weight: 600; }
        .rpt-confidential {
            display: inline-block; margin-top: 8px;
            background: #ef4444; color: #fff;
            font-size: 10px; font-weight: 700; letter-spacing: .1em;
            padding: 3px 10px; border-radius: 3px;
        }

        /* ── Footer ──────────────────────────────────────────── */
        .rpt-footer {
            border-top: 1px solid #e5e7eb;
            padding: 10px 32px;
            display: flex; align-items: center; justify-content: space-between;
            font-size: 10px; color: #9ca3af; background: #fff;
        }

        /* ── Body ────────────────────────────────────────────── */
        .rpt-body { padding: 0 32px 28px; }

        /* ── Section ─────────────────────────────────────────── */
        .rpt-section { margin-top: 22px; }
        .rpt-section-header {
            background: #1a2744; color: #fff;
            padding: 9px 16px;
            font-size: 11px; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            margin-bottom: 14px;
        }

        /* ── Scope box ───────────────────────────────────────── */
        .scope-box {
            border: 1px solid #bfdbfe; background: #eff6ff;
            border-radius: 5px; padding: 9px 14px;
            font-size: 11px; color: #1e40af; margin-top: 10px;
        }
        .scope-box strong { font-weight: 700; }

        /* ── KPI cards ───────────────────────────────────────── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 14px; }
        .kpi-card {
            border: 1px solid #e5e7eb; border-radius: 6px;
            padding: 14px 16px; background: #fff;
        }
        .kpi-val { font-size: 26px; font-weight: 700; color: #1a2744; line-height: 1.1; }
        .kpi-lbl { font-size: 11px; font-weight: 600; color: #4b5563; margin-top: 4px; }
        .kpi-sub { font-size: 10px; color: #9ca3af; margin-top: 2px; }
        .kpi-card.blue  { border-top: 3px solid #3b82f6; }
        .kpi-card.teal  { border-top: 3px solid #14b8a6; }
        .kpi-card.amber { border-top: 3px solid #f59e0b; }
        .kpi-card.red   { border-top: 3px solid #ef4444; }

        /* ── Domain summary cards ────────────────────────────── */
        .domain-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .domain-card {
            border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 14px 16px; background: #fff;
            border-left: 4px solid;
        }
        .domain-card-name { font-size: 12px; font-weight: 700; color: #1a2744; margin-bottom: 6px; }
        .domain-card-rate { font-size: 22px; font-weight: 700; line-height: 1.1; }
        .domain-card-rate-lbl { font-size: 10px; color: #6b7280; margin-bottom: 8px; }
        .domain-card-bar-track {
            height: 6px; background: #e5e7eb; border-radius: 3px;
            overflow: hidden; margin-bottom: 10px;
        }
        .domain-card-bar-fill { height: 6px; border-radius: 3px; }
        .domain-card-counts { font-size: 10px; color: #6b7280; }
        .domain-card-counts span { font-weight: 600; }

        /* ── Status table ────────────────────────────────────── */
        .rpt-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .rpt-table thead th {
            background: #1a2744; color: #fff;
            padding: 9px 14px; text-align: left;
            font-weight: 600; font-size: 11px;
        }
        .rpt-table thead th:not(:first-child) { text-align: center; }
        .rpt-table tbody tr:nth-child(even) { background: #f8fafc; }
        .rpt-table tbody td {
            padding: 9px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
        }
        .rpt-table tbody td:not(:first-child) { text-align: center; }
        .td-pass  { font-weight: 700; color: #15803d; }
        .td-fail  { font-weight: 700; color: #b91c1c; }
        .td-rate  { font-weight: 700; color: #1d4ed8; }
        .td-gray  { color: #9ca3af; }

        /* ── Stacked bar rows ────────────────────────────────── */
        .stack-row { display: flex; align-items: center; gap: 12px; margin-bottom: 11px; }
        .stack-name { font-size: 11.5px; color: #374151; width: 160px; flex-shrink: 0; }
        .stack-track {
            flex: 1; height: 10px; background: #e5e7eb;
            border-radius: 5px; overflow: hidden;
        }
        .stack-fill { height: 10px; border-radius: 5px; }
        .stack-pct { font-size: 11px; font-weight: 700; color: #1d4ed8; width: 42px; text-align: right; flex-shrink: 0; }

        /* ── Visual analytics two-col ────────────────────────── */
        .chart-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .chart-box {
            border: 1px solid #e5e7eb; border-radius: 6px;
            padding: 14px; background: #fff;
        }
        .chart-box-title { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        .chart-box-caption { font-size: 10px; color: #9ca3af; margin-top: 8px; }
        .trend-rate { font-size: 22px; font-weight: 700; color: #3b82f6; }
        .trend-rate-sub { font-size: 10px; color: #9ca3af; }

        /* ── Topic performance ───────────────────────────────── */
        .topic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .topic-section-title {
            font-size: 11px; font-weight: 700;
            margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid;
        }
        .topic-section-title.weak   { color: #ef4444; border-color: #ef4444; }
        .topic-section-title.strong { color: #10b981; border-color: #10b981; }
        .topic-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .topic-code {
            font-size: 9px; font-weight: 700;
            padding: 2px 6px; border-radius: 3px;
            flex-shrink: 0; min-width: 34px; text-align: center;
        }
        .code-red   { background: #fee2e2; color: #b91c1c; }
        .code-green { background: #d1fae5; color: #065f46; }
        .topic-name { font-size: 11px; color: #374151; flex: 1; }
        .topic-track { width: 70px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; flex-shrink: 0; }
        .topic-fill-weak   { height: 6px; border-radius: 3px; background: #ef4444; }
        .topic-fill-strong { height: 6px; border-radius: 3px; background: #10b981; }
        .topic-pct { font-size: 11px; font-weight: 700; width: 42px; text-align: right; flex-shrink: 0; }
        .pct-red   { color: #ef4444; }
        .pct-green { color: #10b981; }

        /* ── Observations ────────────────────────────────────── */
        .obs-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .obs-table tr { border-bottom: 1px solid #e5e7eb; }
        .obs-table td { padding: 11px 14px; vertical-align: top; }
        .obs-title { font-weight: 700; color: #1a2744; width: 200px; }
        .obs-table tr:nth-child(even) { background: #f8fafc; }

        /* ── Print ───────────────────────────────────────────── */
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
<div class="print-bar">
    <span class="print-bar-title">CFIP Domain Analytics Report — {{ $selectedLevel->name }} Level — {{ now()->format('d F Y') }}</span>
    <div class="print-bar-actions">
        <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
</div>

<div class="report-wrap">

    {{-- ── Header ──────────────────────────────────────────── --}}
    <div class="rpt-header">
        <div class="rpt-header-top">
            <div>
                <div class="rpt-tag">Domain Analytics Report</div>
                <div class="rpt-title">CERTIFIED FINANCIAL INVESTIGATOR PROGRAM</div>
                <div class="rpt-subtitle">Academic Management System &middot; Domain Analytics Report &middot; {{ $selectedLevel->name }} Level</div>
            </div>
            <div class="rpt-date-col">
                <div class="rpt-date">{{ now()->format('d F Y') }}</div>
                <div class="rpt-confidential">CONFIDENTIAL</div>
            </div>
        </div>
    </div>

    {{-- ── Body ─────────────────────────────────────────────── --}}
    <div class="rpt-body">

        {{-- 1. EXECUTIVE SUMMARY --}}
        <div class="rpt-section">
            <div class="rpt-section-header">1. &nbsp;Executive Summary</div>

            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                This report presents a detailed breakdown of learner performance across all training domains of the
                Certified Financial Investigator Program (CFIP), {{ $selectedLevel->name }} Level.
                Data reflects all <strong>{{ number_format($totalEnrollment) }}</strong> enrolled learners
                across <strong>{{ $agencyCount }}</strong> registered agencies as at <strong>{{ now()->format('d F Y') }}</strong>.
                The overall completion rate currently stands at <strong>{{ $completionRate }}%</strong>,
                driven primarily by the
                @php $leadDomain = collect($domainStats)->sortByDesc(fn($d) => $d['total'] > 0 ? $d['pass'] / $d['total'] : 0)->keys()->first(); @endphp
                {{ $leadDomain }} domain, while remaining domains remain in early stages.
            </p>

            <div class="scope-box">
                <strong>Scope:</strong>
                {{ $cohortLabel }} &middot; {{ $agencyLabel }} &middot; {{ $selectedLevel->name }} Level &middot;
                Report Date: {{ now()->format('d F Y') }} &middot;
                Generated by: {{ $user->name }} ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }})
            </div>
        </div>

        {{-- 2. KEY PERFORMANCE INDICATORS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">2. &nbsp;Key Performance Indicators</div>

            @php
                $firstDomainName = $domains->first()->name ?? 'Foundation';
                $firstDomainNS   = $domainStats[$firstDomainName]['not_started'] ?? $notStartedLearners;
            @endphp

            <div class="kpi-grid">
                <div class="kpi-card blue">
                    <div class="kpi-val">{{ number_format($totalEnrollment) }}</div>
                    <div class="kpi-lbl">Total Enrollment</div>
                    <div class="kpi-sub">Across all agencies</div>
                </div>
                <div class="kpi-card teal">
                    <div class="kpi-val">{{ number_format($completionRate, 1) }}%</div>
                    <div class="kpi-lbl">Completion Rate</div>
                    <div class="kpi-sub">{{ $selectedLevel->name }} Level overall</div>
                </div>
                <div class="kpi-card amber">
                    <div class="kpi-val">{{ number_format($inProgressLearners) }}</div>
                    <div class="kpi-lbl">In Progress</div>
                    <div class="kpi-sub">Still working through modules</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-val">{{ number_format($firstDomainNS) }}</div>
                    <div class="kpi-lbl">Not Started</div>
                    <div class="kpi-sub">No activity yet ({{ $firstDomainName }})</div>
                </div>
            </div>
        </div>

        {{-- 3. DOMAIN SUMMARY CARDS --}}
        @php
            $domainColors = [
                '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444',
                '#14b8a6', '#f97316', '#6366f1', '#ec4899', '#0ea5e9',
            ];
        @endphp
        <div class="rpt-section">
            <div class="rpt-section-header">3. &nbsp;Domain Summary Cards</div>
            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                Each card below represents one training domain, showing the pass rate, learner counts by status, and a visual progress indicator.
            </p>

            <div class="domain-cards">
                @foreach($domains as $idx => $domain)
                    @php
                        $ds    = $domainStats[$domain->name] ?? ['pass'=>0,'progress'=>0,'failed'=>0,'not_started'=>0,'total'=>$totalEnrollment];
                        $rate  = $ds['total'] > 0 ? round(($ds['pass'] / $ds['total']) * 100, 1) : 0;
                        $color = $domainColors[$idx % count($domainColors)];
                    @endphp
                    <div class="domain-card" style="border-left-color:{{ $color }};">
                        <div class="domain-card-name">{{ $domain->name }}</div>
                        <div class="domain-card-rate" style="color:{{ $color }};">{{ number_format($rate, 1) }}%</div>
                        <div class="domain-card-rate-lbl">completion rate</div>
                        <div class="domain-card-bar-track">
                            <div class="domain-card-bar-fill" style="width:{{ min(100,$rate) }}%;background:{{ $color }};"></div>
                        </div>
                        <div class="domain-card-counts">
                            @if($ds['pass'] > 0)<span style="color:{{ $color }};">&#10003; {{ $ds['pass'] }} passed</span>@endif
                            @if($ds['failed'] > 0) &nbsp;<span style="color:#ef4444;">&#10007; {{ $ds['failed'] }} failed</span>@endif
                            @if($ds['not_started'] > 0) &nbsp;<span style="color:#9ca3af;">&#9632; {{ $ds['not_started'] }} not started</span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. LEARNER STATUS BY DOMAIN --}}
        <div class="rpt-section">
            <div class="rpt-section-header">4. &nbsp;Learner Status by Domain</div>
            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                The table below shows a complete numeric and percentage breakdown of learner status
                (Passed, In Progress, Failed, Not Started) for each domain.
            </p>

            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Total</th>
                        <th>Passed</th>
                        <th>In Progress</th>
                        <th>Failed</th>
                        <th>Not Started</th>
                        <th>Pass Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $domain)
                        @php
                            $ds   = $domainStats[$domain->name] ?? ['pass'=>0,'progress'=>0,'failed'=>0,'not_started'=>0,'total'=>$totalEnrollment];
                            $rate = $ds['total'] > 0 ? round(($ds['pass'] / $ds['total']) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td>{{ $domain->name }}</td>
                            <td>{{ $ds['total'] }}</td>
                            <td class="td-pass">{{ $ds['pass'] }}</td>
                            <td>{{ $ds['progress'] }}</td>
                            <td class="{{ $ds['failed'] > 0 ? 'td-fail' : 'td-gray' }}">{{ $ds['failed'] }}</td>
                            <td class="td-gray">{{ $ds['not_started'] }}</td>
                            <td class="td-rate">{{ number_format($rate, 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Stacked progress bars --}}
            <p style="font-size:11px;color:#6b7280;margin:14px 0 10px;">
                Visual learner status distribution per domain (stacked: Passed / In Progress / Failed):
            </p>
            @foreach($domains as $idx => $domain)
                @php
                    $ds    = $domainStats[$domain->name] ?? ['pass'=>0,'total'=>$totalEnrollment];
                    $rate  = $ds['total'] > 0 ? round(($ds['pass'] / $ds['total']) * 100, 1) : 0;
                    $color = $domainColors[$idx % count($domainColors)];
                @endphp
                <div class="stack-row">
                    <div class="stack-name">{{ $domain->name }}</div>
                    <div class="stack-track">
                        <div class="stack-fill" style="width:{{ min(100,$rate) }}%;background:{{ $color }};"></div>
                    </div>
                    <div class="stack-pct" style="color:{{ $color }};">{{ number_format($rate, 1) }}%</div>
                </div>
            @endforeach
        </div>

        {{-- 5. VISUAL ANALYTICS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">5. &nbsp;Visual Analytics</div>

            @php
                /* ── Grouped bar chart data ── */
                $barColors  = ['#3b82f6', '#f59e0b', '#ef4444', '#e5e7eb'];
                $barLabels  = ['Passed', 'In Progress', 'Failed', 'Not Started'];
                $maxVal     = 1;
                foreach ($domainStats as $ds) {
                    $maxVal = max($maxVal, $ds['pass'], $ds['progress'], $ds['failed'], $ds['not_started']);
                }

                $bSvgW  = 400; $bSvgH = 180;
                $bPadL  = 32;  $bPadR = 8;
                $bPadT  = 12;  $bPadB = 40;
                $bInW   = $bSvgW - $bPadL - $bPadR;
                $bInH   = $bSvgH - $bPadT - $bPadB;
                $domArr = array_values($domainStats);
                $dNames = array_keys($domainStats);
                $nD     = count($domArr);
                $nBars  = 4;
                $grpW   = $nD > 0 ? $bInW / $nD : $bInW;
                $barW   = max(4, $grpW / ($nBars + 1));
                $barGap = $barW * 0.25;

                /* ── Trend chart data ── */
                $tSvgW = 400; $tSvgH = 180;
                $tPadL = 36;  $tPadR = 20;
                $tPadT = 10;  $tPadB = 28;
                $tInW  = $tSvgW - $tPadL - $tPadR;
                $tInH  = $tSvgH - $tPadT - $tPadB;
                $tN    = count($trendData);
                $tPts  = []; $tFill = [];
                foreach ($trendData as $i => $v) {
                    $x = $tPadL + ($tN > 1 ? ($i / ($tN - 1)) * $tInW : $tInW);
                    $y = $tPadT + $tInH - ($v / 100) * $tInH;
                    $tPts[]  = compact('x', 'y');
                    $tFill[] = "$x,$y";
                }
                $tLineD = count($tPts) > 0
                    ? 'M ' . collect($tPts)->map(fn($p) => "{$p['x']},{$p['y']}")->join(' L ')
                    : '';
                $tFillD = count($tFill) > 1
                    ? "M {$tPadL}," . ($tPadT + $tInH) . " L " . implode(' L ', $tFill) . " L " . ($tPadL + $tInW) . "," . ($tPadT + $tInH) . " Z"
                    : '';
                $tLast = count($tPts) > 0 ? end($tPts) : null;
                $tYGrids = [0, 25, 50, 75, 100];
                $tStep   = max(1, (int) ceil($tN / 8));
            @endphp

            <div class="chart-pair">

                {{-- Bar chart --}}
                <div class="chart-box">
                    <div class="chart-box-title">
                        @foreach($barLabels as $bi => $bl)
                            <span style="display:inline-flex;align-items:center;gap:4px;margin-right:10px;">
                                <span style="width:10px;height:10px;border-radius:2px;background:{{ $barColors[$bi] }};display:inline-block;"></span>
                                <span style="font-size:10px;">{{ $bl }}</span>
                            </span>
                        @endforeach
                    </div>
                    <svg viewBox="0 0 {{ $bSvgW }} {{ $bSvgH }}" width="100%" preserveAspectRatio="xMidYMid meet" style="display:block;">
                        {{-- Y gridlines --}}
                        @foreach([0, 25, 50, 75, 100] as $pct)
                            @php $gy = $bPadT + $bInH - ($pct / 100) * $bInH; $gVal = round($maxVal * $pct / 100); @endphp
                            <line x1="{{ $bPadL }}" y1="{{ $gy }}" x2="{{ $bPadL + $bInW }}" y2="{{ $gy }}"
                                  stroke="#e5e7eb" stroke-width="1"/>
                            <text x="{{ $bPadL - 3 }}" y="{{ $gy + 4 }}" text-anchor="end"
                                  font-size="8" fill="#9ca3af" font-family="Poppins,Arial,sans-serif">{{ $gVal }}</text>
                        @endforeach

                        {{-- Bars --}}
                        @foreach($domArr as $gi => $ds)
                            @php
                                $grpX  = $bPadL + $gi * $grpW + $barGap;
                                $vals  = [$ds['pass'], $ds['progress'], $ds['failed'], $ds['not_started']];
                            @endphp
                            @foreach($vals as $bi => $val)
                                @php
                                    $bx  = $grpX + $bi * ($barW + $barGap / 2);
                                    $bh  = $maxVal > 0 ? ($val / $maxVal) * $bInH : 0;
                                    $by  = $bPadT + $bInH - $bh;
                                @endphp
                                @if($val > 0)
                                    <rect x="{{ $bx }}" y="{{ $by }}" width="{{ $barW }}" height="{{ $bh }}"
                                          fill="{{ $barColors[$bi] }}" rx="2"/>
                                    @if($bh > 12)
                                        <text x="{{ $bx + $barW/2 }}" y="{{ $by - 2 }}"
                                              text-anchor="middle" font-size="7" fill="#374151"
                                              font-family="Poppins,Arial,sans-serif">{{ $val }}</text>
                                    @endif
                                @endif
                            @endforeach
                            {{-- X label --}}
                            @php $lx = $bPadL + $gi * $grpW + $grpW / 2; $shortName = mb_strimwidth($dNames[$gi], 0, 10, '…'); @endphp
                            <text x="{{ $lx }}" y="{{ $bPadT + $bInH + 14 }}"
                                  text-anchor="middle" font-size="8" fill="#6b7280"
                                  font-family="Poppins,Arial,sans-serif">{{ $shortName }}</text>
                        @endforeach
                    </svg>
                    <div class="chart-box-caption">Learner counts (Passed / In Progress / Failed / Not Started) per domain</div>
                </div>

                {{-- Trend chart --}}
                <div class="chart-box">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <div class="chart-box-title">Cohort weekly completion trend</div>
                        <div style="text-align:right;">
                            <div class="trend-rate">{{ $completionRate }}%</div>
                            <div class="trend-rate-sub">Current completion</div>
                        </div>
                    </div>
                    <svg viewBox="0 0 {{ $tSvgW }} {{ $tSvgH }}" width="100%" preserveAspectRatio="xMidYMid meet" style="display:block;">
                        @foreach($tYGrids as $pct)
                            @php $gy = $tPadT + $tInH - ($pct / 100) * $tInH; @endphp
                            <line x1="{{ $tPadL }}" y1="{{ $gy }}" x2="{{ $tPadL + $tInW }}" y2="{{ $gy }}"
                                  stroke="#e5e7eb" stroke-width="1"/>
                            <text x="{{ $tPadL - 4 }}" y="{{ $gy + 4 }}" text-anchor="end"
                                  font-size="9" fill="#9ca3af" font-family="Poppins,Arial,sans-serif">{{ $pct }}%</text>
                        @endforeach

                        @if($tFillD)
                            <path d="{{ $tFillD }}" fill="#eff6ff" opacity="0.9"/>
                        @endif
                        @if($tLineD)
                            <path d="{{ $tLineD }}" fill="none" stroke="#3b82f6" stroke-width="2.5"
                                  stroke-linejoin="round" stroke-linecap="round"/>
                        @endif

                        @foreach($trendLabels as $i => $lbl)
                            @if($i % $tStep === 0 || $i === $tN - 1)
                                @php $lx = $tPadL + ($tN > 1 ? ($i / ($tN - 1)) * $tInW : $tInW); @endphp
                                <text x="{{ $lx }}" y="{{ $tPadT + $tInH + 18 }}"
                                      text-anchor="middle" font-size="9" fill="#9ca3af"
                                      font-family="Poppins,Arial,sans-serif">{{ $lbl }}</text>
                            @endif
                        @endforeach

                        @if($tLast)
                            <circle cx="{{ $tLast['x'] }}" cy="{{ $tLast['y'] }}" r="5"
                                    fill="white" stroke="#3b82f6" stroke-width="2.5"/>
                            <text x="{{ $tLast['x'] + 8 }}" y="{{ $tLast['y'] + 4 }}"
                                  font-size="10" font-weight="700" fill="#3b82f6"
                                  font-family="Poppins,Arial,sans-serif">{{ $completionRate }}%</text>
                        @endif
                    </svg>
                    <div class="chart-box-caption">Cohort weekly completion trend — {{ $completionRate }}% current rate</div>
                </div>

            </div>
        </div>

        {{-- 6. TOPIC PERFORMANCE --}}
        <div class="rpt-section">
            <div class="rpt-section-header">6. &nbsp;Topic Performance</div>
            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                Topic-level performance highlights the modules where learners are excelling and those requiring immediate attention or content revision.
            </p>

            <div class="topic-grid">
                {{-- Weakest --}}
                <div>
                    <div class="topic-section-title weak">Weakest Modules</div>
                    @forelse($weakTopics->take(3) as $t)
                        @php $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $t->domain_name ?? ''), 0, 2)) . sprintf('%02d', $loop->iteration); @endphp
                        <div class="topic-row">
                            <span class="topic-code code-red">{{ $code }}</span>
                            <span class="topic-name">{{ $t->module_title }}</span>
                            <div class="topic-track"><div class="topic-fill-weak" style="width:{{ min(100,$t->avg_progress) }}%;"></div></div>
                            <span class="topic-pct pct-red">{{ number_format($t->avg_progress, 1) }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No module data available.</p>
                    @endforelse
                </div>

                {{-- Strongest --}}
                <div>
                    <div class="topic-section-title strong">Strongest Modules</div>
                    @forelse($strongTopics->take(3) as $t)
                        @php $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $t->domain_name ?? ''), 0, 2)) . sprintf('%02d', $loop->iteration); @endphp
                        <div class="topic-row">
                            <span class="topic-code code-green">{{ $code }}</span>
                            <span class="topic-name">{{ $t->module_title }}</span>
                            <div class="topic-track"><div class="topic-fill-strong" style="width:{{ min(100,$t->avg_progress) }}%;"></div></div>
                            <span class="topic-pct pct-green">{{ number_format($t->avg_progress, 1) }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No module data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 7. DOMAIN OBSERVATIONS & RECOMMENDATIONS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">7. &nbsp;Domain Observations &amp; Recommendations</div>

            <table class="obs-table">
                @foreach($observations as $i => $obs)
                    <tr>
                        <td class="obs-title">{{ ($i + 1) }}. {{ $obs['title'] }}</td>
                        <td style="font-size:12px;color:#374151;">{{ $obs['text'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

    </div>{{-- /rpt-body --}}

    {{-- Footer --}}
    <div class="rpt-footer">
        <span>Generated by: {{ $user->name }} ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }}) &middot; Date: {{ now()->format('d F Y') }} &middot; CERTIFIED FINANCIAL INVESTIGATOR PROGRAM — Domain Analytics Report</span>
        <span>CFIP — CONFIDENTIAL</span>
    </div>

</div>{{-- /report-wrap --}}

</body>
</html>
