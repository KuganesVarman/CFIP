<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFIP Module Analytics Report — {{ $selectedDomain->name }} — {{ now()->format('d F Y') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 13px; color: #111827;
            background: #f1f5f9; line-height: 1.5;
        }

        /* ── Print bar ───────────────────────────────────────── */
        .print-bar {
            position: fixed; top: 0; left: 0; right: 0;
            background: #1a2744; color: #fff;
            padding: 10px 24px;
            display: flex; align-items: center; justify-content: space-between;
            z-index: 9999; box-shadow: 0 2px 8px rgba(0,0,0,.25);
        }
        .print-bar-title { font-size: 13px; font-weight: 500; opacity: .85; }
        .print-bar-actions { display: flex; gap: 10px; }
        .btn-print { background: #3b82f6; color: #fff; border: none; padding: 7px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
        .btn-print:hover { background: #2563eb; }
        .btn-close  { background: rgba(255,255,255,.12); color: #fff; border: none; padding: 7px 14px; border-radius: 6px; font-size: 13px; cursor: pointer; font-family: inherit; }
        .btn-close:hover { background: rgba(255,255,255,.22); }

        /* ── Wrapper ─────────────────────────────────────────── */
        .report-wrap { max-width: 900px; margin: 60px auto 40px; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.10); }

        /* ── Header ──────────────────────────────────────────── */
        .rpt-header { background: linear-gradient(135deg, #1a2744 0%, #0d1b3e 100%); color: #fff; padding: 28px 32px 22px; }
        .rpt-header-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 6px; }
        .rpt-tag  { font-size: 10px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #93c5fd; margin-bottom: 6px; }
        .rpt-title { font-size: 22px; font-weight: 700; letter-spacing: -.01em; line-height: 1.2; }
        .rpt-subtitle { font-size: 12px; color: #93c5fd; margin-top: 4px; }
        .rpt-date-col { text-align: right; flex-shrink: 0; padding-left: 20px; }
        .rpt-date { font-size: 15px; font-weight: 600; }
        .rpt-confidential { display: inline-block; margin-top: 8px; background: #ef4444; color: #fff; font-size: 10px; font-weight: 700; letter-spacing: .1em; padding: 3px 10px; border-radius: 3px; }

        /* ── Footer ──────────────────────────────────────────── */
        .rpt-footer { border-top: 1px solid #e5e7eb; padding: 10px 32px; display: flex; align-items: center; justify-content: space-between; font-size: 10px; color: #9ca3af; background: #fff; }

        /* ── Body / sections ─────────────────────────────────── */
        .rpt-body { padding: 0 32px 28px; }
        .rpt-section { margin-top: 22px; }
        .rpt-section-header { background: #1a2744; color: #fff; padding: 9px 16px; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 14px; }

        /* ── Scope box ───────────────────────────────────────── */
        .scope-box { border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 5px; padding: 9px 14px; font-size: 11px; color: #1e40af; margin-top: 10px; }
        .scope-box strong { font-weight: 700; }

        /* ── KPI cards ───────────────────────────────────────── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .kpi-card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px 16px; background: #fff; }
        .kpi-val  { font-size: 26px; font-weight: 700; color: #1a2744; line-height: 1.1; }
        .kpi-lbl  { font-size: 11px; font-weight: 600; color: #4b5563; margin-top: 4px; }
        .kpi-sub  { font-size: 10px; color: #9ca3af; margin-top: 2px; }
        .kpi-card.blue  { border-top: 3px solid #3b82f6; }
        .kpi-card.teal  { border-top: 3px solid #14b8a6; }
        .kpi-card.amber { border-top: 3px solid #f59e0b; }
        .kpi-card.red   { border-top: 3px solid #ef4444; }

        /* ── Module profile cards ────────────────────────────── */
        .module-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .module-card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; background: #fff; border-top: 4px solid; }
        .module-code { font-size: 15px; font-weight: 700; color: #1a2744; margin-bottom: 6px; }
        .module-rate { font-size: 22px; font-weight: 700; line-height: 1.1; }
        .module-rate-lbl { font-size: 10px; color: #6b7280; margin-bottom: 8px; }
        .module-bar-track { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; margin-bottom: 10px; }
        .module-bar-fill  { height: 6px; border-radius: 3px; }
        .module-counts { font-size: 10px; display: flex; gap: 10px; flex-wrap: wrap; }
        .mc-pass  { color: #15803d; font-weight: 600; }
        .mc-prog  { color: #92400e; font-weight: 600; }
        .mc-fail  { color: #b91c1c; font-weight: 600; }
        .mc-ns    { color: #9ca3af; font-weight: 600; }

        /* ── Breakdown table ─────────────────────────────────── */
        .rpt-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .rpt-table thead th { background: #1a2744; color: #fff; padding: 9px 12px; text-align: left; font-weight: 600; font-size: 11px; }
        .rpt-table thead th:not(:first-child) { text-align: center; }
        .rpt-table tbody tr:nth-child(even) { background: #f8fafc; }
        .rpt-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .rpt-table tbody td:not(:first-child) { text-align: center; }
        .td-code  { font-weight: 700; color: #1a2744; }
        .td-pass  { font-weight: 700; color: #15803d; }
        .td-fail  { font-weight: 700; color: #b91c1c; }
        .td-gray  { color: #9ca3af; }
        .rate-green  { font-weight: 700; color: #15803d; }
        .rate-amber  { font-weight: 700; color: #d97706; }
        .rate-red    { font-weight: 700; color: #b91c1c; }
        .dist-track { width: 60px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; display: inline-block; vertical-align: middle; }
        .dist-fill  { height: 6px; border-radius: 3px; background: #14b8a6; }

        /* ── Visual analytics ────────────────────────────────── */
        .chart-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .chart-box  { border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; background: #fff; }
        .chart-box-title   { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        .chart-box-caption { font-size: 10px; color: #9ca3af; margin-top: 8px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 2px; display: inline-block; }

        /* ── Highlight cards ─────────────────────────────────── */
        .highlight-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
        .highlight-card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 14px; background: #fff; }
        .hl-label { font-size: 9px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #9ca3af; margin-bottom: 4px; }
        .hl-value { font-size: 20px; font-weight: 700; }
        .hl-sub   { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .hl-dash  { font-size: 28px; font-weight: 700; color: #d1d5db; }
        .hl-red   { color: #ef4444; }
        .hl-amber { color: #f59e0b; }
        .hl-blue  { color: #3b82f6; }

        /* ── Score dist table ────────────────────────────────── */
        .td-failing    { font-weight: 700; color: #b91c1c; }
        .td-borderline { font-weight: 700; color: #d97706; }
        .td-solid      { font-weight: 700; color: #0d9488; }
        .td-strong     { font-weight: 700; color: #3b82f6; }

        /* ── Topic performance ───────────────────────────────── */
        .topic-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .topic-section-title { font-size: 11px; font-weight: 700; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid; }
        .topic-section-title.weak   { color: #ef4444; border-color: #ef4444; }
        .topic-section-title.strong { color: #10b981; border-color: #10b981; }
        .topic-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .topic-code  { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 3px; flex-shrink: 0; min-width: 34px; text-align: center; }
        .code-red    { background: #fee2e2; color: #b91c1c; }
        .code-green  { background: #d1fae5; color: #065f46; }
        .topic-name  { font-size: 11px; color: #374151; flex: 1; }
        .topic-track { width: 70px; height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; flex-shrink: 0; }
        .topic-fill-weak   { height: 6px; border-radius: 3px; background: #ef4444; }
        .topic-fill-strong { height: 6px; border-radius: 3px; background: #10b981; }
        .topic-pct   { font-size: 11px; font-weight: 700; width: 42px; text-align: right; flex-shrink: 0; }
        .pct-red     { color: #ef4444; }
        .pct-green   { color: #10b981; }

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

<div class="print-bar">
    <span class="print-bar-title">CFIP Module Analytics Report — {{ $selectedDomain->name }} Domain — {{ now()->format('d F Y') }}</span>
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
                <div class="rpt-tag">Module Analytics Report</div>
                <div class="rpt-title">CERTIFIED FINANCIAL INVESTIGATOR PROGRAM</div>
                <div class="rpt-subtitle">Academic Management System &middot; Module Analytics Report &middot; {{ $selectedDomain->name }} Domain</div>
            </div>
            <div class="rpt-date-col">
                <div class="rpt-date">{{ now()->format('d F Y') }}</div>
                <div class="rpt-confidential">CONFIDENTIAL</div>
            </div>
        </div>
    </div>

    <div class="rpt-body">

        {{-- 1. EXECUTIVE SUMMARY --}}
        <div class="rpt-section">
            <div class="rpt-section-header">1. &nbsp;Executive Summary</div>

            @php $moduleCount = $domainCourses->count(); @endphp
            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                This report presents a detailed module-level performance analysis for the <strong>{{ $selectedDomain->name }}</strong> domain
                of the Certified Financial Investigator Program (CFIP). All <strong>{{ number_format($totalEnrollment) }}</strong> enrolled learners
                across <strong>{{ $agencyCount }}</strong> registered agencies are covered as at <strong>{{ now()->format('d F Y') }}</strong>.
                The domain contains {{ $moduleCount === 1 ? 'one module' : $moduleCount . ' modules' }}
                ({{ $moduleCodes }}) with an overall completion rate of <strong>{{ $completionRate }}%</strong>
                — learners who have passed all modules in the domain.
            </p>

            <div class="scope-box">
                <strong>Domain:</strong> {{ $selectedDomain->name }} &middot;
                <strong>Modules:</strong> {{ $moduleCodes }} &middot;
                <strong>Total Learners:</strong> {{ number_format($totalEnrollment) }} &middot;
                <strong>Report Date:</strong> {{ now()->format('d F Y') }} &middot;
                <strong>Generated by:</strong> {{ $user->name }}
                ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }})
            </div>
        </div>

        {{-- 2. KEY PERFORMANCE INDICATORS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">2. &nbsp;Key Performance Indicators — {{ $selectedDomain->name }} Domain</div>

            <div class="kpi-grid">
                <div class="kpi-card blue">
                    <div class="kpi-val">{{ number_format($totalEnrollment) }}</div>
                    <div class="kpi-lbl">Total Enrollment</div>
                    <div class="kpi-sub">{{ $selectedDomain->name }} domain</div>
                </div>
                <div class="kpi-card teal">
                    <div class="kpi-val">{{ number_format($completionRate, 1) }}%</div>
                    <div class="kpi-lbl">Completion Rate</div>
                    <div class="kpi-sub">Passed all domain modules</div>
                </div>
                <div class="kpi-card amber">
                    <div class="kpi-val">{{ number_format($inProgressLearners) }}</div>
                    <div class="kpi-lbl">In Progress</div>
                    <div class="kpi-sub">Actively working</div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-val">{{ number_format($notStartedLearners) }}</div>
                    <div class="kpi-lbl">Not Started</div>
                    <div class="kpi-sub">No activity yet</div>
                </div>
            </div>
        </div>

        {{-- 3. MODULE PROFILE SUMMARY --}}
        @php
            $cardColors = ['#f59e0b', '#ef4444', '#14b8a6', '#3b82f6', '#8b5cf6', '#f97316', '#10b981'];
        @endphp
        <div class="rpt-section">
            <div class="rpt-section-header">3. &nbsp;Module Profile Summary</div>
            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                Each card below summarises a single module, showing pass rate, progress bar and learner counts by status
                (Passed / In Progress / Failed / Not Started).
            </p>

            <div class="module-cards">
                @foreach($domainCourses as $idx => $dc)
                    @php
                        $ms    = $courseStats[$dc->course_code] ?? ['pass'=>0,'progress'=>0,'failed'=>0,'not_started'=>0,'total'=>$totalEnrollment];
                        $rate  = $ms['total'] > 0 ? round(($ms['pass'] / $ms['total']) * 100, 1) : 0;
                        $color = $cardColors[$idx % count($cardColors)];
                    @endphp
                    <div class="module-card" style="border-top-color:{{ $color }};">
                        <div class="module-code">{{ $dc->course_code }}</div>
                        <div class="module-rate" style="color:{{ $color }};">{{ number_format($rate, 1) }}%</div>
                        <div class="module-rate-lbl">pass rate</div>
                        <div class="module-bar-track">
                            <div class="module-bar-fill" style="width:{{ min(100,$rate) }}%;background:{{ $color }};"></div>
                        </div>
                        <div class="module-counts">
                            <span class="mc-pass">&#10003; {{ $ms['pass'] }}</span>
                            <span class="mc-prog">&#9632; {{ $ms['progress'] }}</span>
                            <span class="mc-fail">&#10007; {{ $ms['failed'] }}</span>
                            <span class="mc-ns">&#9632; {{ $ms['not_started'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 4. MODULE BREAKDOWN TABLE --}}
        <div class="rpt-section">
            <div class="rpt-section-header">4. &nbsp;Module Breakdown Table</div>
            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                Full numeric breakdown per module sorted by pass rate (ascending).
                Pass rate colour: <strong style="color:#15803d;">Green &ge; 50%</strong> &middot;
                <strong style="color:#d97706;">Amber 30–49%</strong> &middot;
                <strong style="color:#b91c1c;">Red &lt; 30%</strong>.
            </p>

            @php
                $sortedStats = collect($courseStats)->map(function ($d, $code) {
                    $d['code'] = $code;
                    $d['rate'] = $d['total'] > 0 ? round(($d['pass'] / $d['total']) * 100, 1) : 0;
                    return $d;
                })->sortBy('rate')->values();
            @endphp

            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Enrolled</th>
                        <th>Passed</th>
                        <th>In Progress</th>
                        <th>Not Started</th>
                        <th>Failed</th>
                        <th>Distribution</th>
                        <th>Pass Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sortedStats as $s)
                        @php
                            $rateClass = $s['rate'] >= 50 ? 'rate-green' : ($s['rate'] >= 30 ? 'rate-amber' : 'rate-red');
                        @endphp
                        <tr>
                            <td class="td-code">{{ $s['code'] }}</td>
                            <td>{{ $s['total'] }}</td>
                            <td class="td-pass">{{ $s['pass'] }}</td>
                            <td>{{ $s['progress'] }}</td>
                            <td class="td-gray">{{ $s['not_started'] }}</td>
                            <td class="{{ $s['failed'] > 0 ? 'td-fail' : 'td-gray' }}">{{ $s['failed'] }}</td>
                            <td>
                                <span class="dist-track">
                                    <span class="dist-fill" style="width:{{ min(100,$s['rate']) }}%;"></span>
                                </span>
                            </td>
                            <td class="{{ $rateClass }}">{{ number_format($s['rate'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 5. VISUAL ANALYTICS --}}
        @php
            /* ── Stacked bar chart (left) ── */
            $stackColors = ['#14b8a6', '#f59e0b', '#e5e7eb', '#ef4444']; // pass, progress, not_started, failed
            $maxStack    = max(1, $totalEnrollment);
            $bW  = 380; $bH  = 180;
            $bPL = 32;  $bPR = 8;
            $bPT = 12;  $bPB = 36;
            $bIW = $bW - $bPL - $bPR;
            $bIH = $bH - $bPT - $bPB;
            $nMod = $domainCourses->count();
            $barSlot = $nMod > 0 ? $bIW / $nMod : $bIW;
            $barW    = max(20, $barSlot * 0.55);

            /* ── Score bands chart (right) ── */
            $bandColors  = ['#ef4444', '#f59e0b', '#14b8a6', '#3b82f6']; // fail, borderline, solid, strong
            $bandLabels  = ['0–49%', '50–69%', '70–89%', '90–100%'];
            $maxBandVal  = 1;
            foreach ($scoreBandsData as $bd) {
                $maxBandVal = max($maxBandVal, $bd['failing'], $bd['borderline'], $bd['solid'], $bd['strong']);
            }
            $sW  = 380; $sH  = 180;
            $sPL = 32;  $sPR = 8;
            $sPT = 12;  $sPB = 36;
            $sIW = $sW - $sPL - $sPR;
            $sIH = $sH - $sPT - $sPB;
            $sGrpW = $nMod > 0 ? $sIW / $nMod : $sIW;
            $sBW   = max(5, $sGrpW / 5);
            $sBGap = $sBW * 0.2;

            /* ── Highlight cards ── */
            $hardestCode     = '—'; $hardestVal  = 0; $borderlineCode = '—'; $borderlineVal = 0;
            $bestCode        = '—'; $bestVal     = 0;
            foreach ($scoreBandsData as $code => $bd) {
                if ($bd['failing']    > $hardestVal)    { $hardestVal    = $bd['failing'];    $hardestCode    = $code; }
                if ($bd['borderline'] > $borderlineVal) { $borderlineVal = $bd['borderline']; $borderlineCode = $code; }
                if ($bd['strong']     > $bestVal)       { $bestVal       = $bd['strong'];     $bestCode       = $code; }
            }
            $domainCourseArr = $domainCourses->values()->toArray();
        @endphp

        <div class="rpt-section">
            <div class="rpt-section-header">5. &nbsp;Visual Analytics</div>

            <div class="chart-pair">

                {{-- Left: Stacked bar chart --}}
                <div class="chart-box">
                    <div class="chart-box-title">
                        @foreach(['Passed','In Progress','Not Started','Failed'] as $bi => $bl)
                            <span style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;">
                                <span class="legend-dot" style="background:{{ $stackColors[$bi] }};"></span>
                                <span style="font-size:10px;">{{ $bl }}</span>
                            </span>
                        @endforeach
                    </div>
                    <svg viewBox="0 0 {{ $bW }} {{ $bH }}" width="100%" preserveAspectRatio="xMidYMid meet" style="display:block;">
                        {{-- Y gridlines --}}
                        @foreach([0,25,50,75,100] as $pct)
                            @php $gy = $bPT + $bIH - ($pct / 100) * $bIH; $gv = round($maxStack * $pct / 100); @endphp
                            <line x1="{{ $bPL }}" y1="{{ $gy }}" x2="{{ $bPL + $bIW }}" y2="{{ $gy }}" stroke="#e5e7eb" stroke-width="1"/>
                            <text x="{{ $bPL - 3 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="8" fill="#9ca3af" font-family="Poppins,Arial,sans-serif">{{ $gv }}</text>
                        @endforeach

                        {{-- Stacked bars --}}
                        @foreach($domainCourseArr as $gi => $dc)
                            @php
                                $ms   = $courseStats[$dc->course_code] ?? ['pass'=>0,'progress'=>0,'failed'=>0,'not_started'=>0,'total'=>$totalEnrollment];
                                $vals = [$ms['pass'], $ms['progress'], $ms['not_started'], $ms['failed']];
                                $bx   = $bPL + $gi * $barSlot + ($barSlot - $barW) / 2;
                                $curY = $bPT + $bIH;
                            @endphp
                            @foreach($vals as $vi => $val)
                                @if($val > 0)
                                    @php
                                        $bh   = ($val / $maxStack) * $bIH;
                                        $curY -= $bh;
                                    @endphp
                                    <rect x="{{ $bx }}" y="{{ $curY }}" width="{{ $barW }}" height="{{ $bh }}"
                                          fill="{{ $stackColors[$vi] }}" rx="{{ $vi === 0 ? 2 : 0 }}"/>
                                    @if($bh > 14)
                                        <text x="{{ $bx + $barW/2 }}" y="{{ $curY + $bh/2 + 4 }}"
                                              text-anchor="middle" font-size="8" fill="{{ $vi === 2 ? '#6b7280' : '#fff' }}"
                                              font-weight="600" font-family="Poppins,Arial,sans-serif">{{ $val }}</text>
                                    @endif
                                @endif
                            @endforeach
                            <text x="{{ $bx + $barW/2 }}" y="{{ $bPT + $bIH + 16 }}"
                                  text-anchor="middle" font-size="9" fill="#6b7280"
                                  font-family="Poppins,Arial,sans-serif">{{ $dc->course_code }}</text>
                        @endforeach
                    </svg>
                    <div class="chart-box-caption">Stacked bar — learner counts by status per module (Passed / In Progress / Not Started / Failed)</div>
                </div>

                {{-- Right: Score distribution bands --}}
                <div class="chart-box">
                    <div class="chart-box-title">
                        @foreach(['0–49% Failing','50–69% Borderline','70–89% Solid','90–100%'] as $bi => $bl)
                            <span style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;">
                                <span class="legend-dot" style="background:{{ $bandColors[$bi] }};"></span>
                                <span style="font-size:10px;">{{ $bl }}</span>
                            </span>
                        @endforeach
                    </div>
                    <svg viewBox="0 0 {{ $sW }} {{ $sH }}" width="100%" preserveAspectRatio="xMidYMid meet" style="display:block;">
                        @foreach([0,25,50,75,100] as $pct)
                            @php $gy = $sPT + $sIH - ($pct / 100) * $sIH; $gv = round($maxBandVal * $pct / 100); @endphp
                            <line x1="{{ $sPL }}" y1="{{ $gy }}" x2="{{ $sPL + $sIW }}" y2="{{ $gy }}" stroke="#e5e7eb" stroke-width="1"/>
                            <text x="{{ $sPL - 3 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="8" fill="#9ca3af" font-family="Poppins,Arial,sans-serif">{{ $gv }}</text>
                        @endforeach

                        @foreach($domainCourseArr as $gi => $dc)
                            @php
                                $bd   = $scoreBandsData[$dc->course_code] ?? ['failing'=>0,'borderline'=>0,'solid'=>0,'strong'=>0];
                                $bVals = [$bd['failing'], $bd['borderline'], $bd['solid'], $bd['strong']];
                                $grpX  = $sPL + $gi * $sGrpW + ($sGrpW - 4 * ($sBW + $sBGap)) / 2;
                            @endphp
                            @foreach($bVals as $bi => $bv)
                                @php
                                    $bx = $grpX + $bi * ($sBW + $sBGap);
                                    $bh = $maxBandVal > 0 ? ($bv / $maxBandVal) * $sIH : 0;
                                    $by = $sPT + $sIH - $bh;
                                @endphp
                                @if($bv > 0)
                                    <rect x="{{ $bx }}" y="{{ $by }}" width="{{ $sBW }}" height="{{ $bh }}"
                                          fill="{{ $bandColors[$bi] }}" rx="2"/>
                                    @if($bh > 12)
                                        <text x="{{ $bx + $sBW/2 }}" y="{{ $by - 2 }}"
                                              text-anchor="middle" font-size="7" fill="#374151"
                                              font-family="Poppins,Arial,sans-serif">{{ $bv }}</text>
                                    @endif
                                @endif
                            @endforeach
                            <text x="{{ $sPL + $gi * $sGrpW + $sGrpW/2 }}" y="{{ $sPT + $sIH + 16 }}"
                                  text-anchor="middle" font-size="9" fill="#6b7280"
                                  font-family="Poppins,Arial,sans-serif">{{ $dc->course_code }}</text>
                        @endforeach
                    </svg>
                    <div class="chart-box-caption">Score distribution bands per module (0–49% / 50–69% / 70–89% / 90–100%)</div>
                </div>

            </div>

            {{-- Highlight cards --}}
            <div class="highlight-grid">
                <div class="highlight-card">
                    <div class="hl-label">Hardest Module</div>
                    <div class="hl-value hl-red">{{ $hardestCode }}</div>
                    <div class="hl-sub">{{ $hardestVal > 0 ? number_format($hardestVal) . ' learners below 50%' : 'No data' }}</div>
                </div>
                <div class="highlight-card">
                    <div class="hl-label">Most Borderline</div>
                    @if($borderlineVal > 0)
                        <div class="hl-value hl-amber">{{ $borderlineCode }}</div>
                        <div class="hl-sub">{{ number_format($borderlineVal) }} in 50–69% band</div>
                    @else
                        <div class="hl-dash">—</div>
                        <div class="hl-sub">0 in 50–69% band</div>
                    @endif
                </div>
                <div class="highlight-card">
                    <div class="hl-label">Best Performer</div>
                    <div class="hl-value hl-blue">{{ $bestCode }}</div>
                    <div class="hl-sub">{{ $bestVal > 0 ? number_format($bestVal) . ' learners above 90%' : 'No data' }}</div>
                </div>
            </div>
        </div>

        {{-- 6. SCORE DISTRIBUTION DETAIL --}}
        <div class="rpt-section">
            <div class="rpt-section-header">6. &nbsp;Score Distribution Detail</div>
            <p style="font-size:12px;color:#374151;margin-bottom:12px;">
                Learner counts per score band per module. Bands:
                <strong style="color:#b91c1c;">0–49% Failing</strong> &middot;
                <strong style="color:#d97706;">50–69% Borderline</strong> &middot;
                <strong style="color:#0d9488;">70–89% Solid</strong> &middot;
                <strong style="color:#3b82f6;">90–100% Strong</strong>.
            </p>

            <table class="rpt-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Total</th>
                        <th>0–49% Failing</th>
                        <th>50–69% Borderline</th>
                        <th>70–89% Solid</th>
                        <th>90–100% Strong</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domainCourses as $dc)
                        @php $bd = $scoreBandsData[$dc->course_code] ?? ['failing'=>0,'borderline'=>0,'solid'=>0,'strong'=>0,'total'=>$totalEnrollment]; @endphp
                        <tr>
                            <td class="td-code">{{ $dc->course_code }}</td>
                            <td>{{ $bd['total'] }}</td>
                            <td class="td-failing">{{ $bd['failing'] }}</td>
                            <td class="{{ $bd['borderline'] > 0 ? 'td-borderline' : 'td-gray' }}">{{ $bd['borderline'] }}</td>
                            <td class="{{ $bd['solid'] > 0 ? 'td-solid' : 'td-gray' }}">{{ $bd['solid'] }}</td>
                            <td class="{{ $bd['strong'] > 0 ? 'td-strong' : 'td-gray' }}">{{ $bd['strong'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- 7. TOPIC PERFORMANCE --}}
        <div class="rpt-section">
            <div class="rpt-section-header">7. &nbsp;Topic Performance</div>
            <p style="font-size:12px;color:#374151;margin-bottom:14px;">
                Quiz and assessment-level scores identify which topics within the {{ $selectedDomain->name }} domain
                need the most attention and which are performing well.
            </p>

            <div class="topic-grid">
                <div>
                    <div class="topic-section-title weak">Weakest Topics</div>
                    @forelse($weakTopics->take(3) as $t)
                        @php $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $t->domain_name ?? $selectedDomain->name), 0, 2)) . sprintf('%02d', $loop->iteration); @endphp
                        <div class="topic-row">
                            <span class="topic-code code-red">{{ $code }}</span>
                            <span class="topic-name">{{ $t->module_title }}</span>
                            <div class="topic-track"><div class="topic-fill-weak" style="width:{{ min(100,$t->avg_progress) }}%;"></div></div>
                            <span class="topic-pct pct-red">{{ number_format($t->avg_progress, 1) }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No topic data available.</p>
                    @endforelse
                </div>
                <div>
                    <div class="topic-section-title strong">Strongest Topics</div>
                    @forelse($strongTopics->take(3) as $t)
                        @php $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $t->domain_name ?? $selectedDomain->name), 0, 2)) . sprintf('%02d', $loop->iteration); @endphp
                        <div class="topic-row">
                            <span class="topic-code code-green">{{ $code }}</span>
                            <span class="topic-name">{{ $t->module_title }}</span>
                            <div class="topic-track"><div class="topic-fill-strong" style="width:{{ min(100,$t->avg_progress) }}%;"></div></div>
                            <span class="topic-pct pct-green">{{ number_format($t->avg_progress, 1) }}%</span>
                        </div>
                    @empty
                        <p style="font-size:11px;color:#9ca3af;">No topic data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 8. MODULE OBSERVATIONS & RECOMMENDATIONS --}}
        <div class="rpt-section">
            <div class="rpt-section-header">8. &nbsp;Module Observations &amp; Recommendations</div>

            <table class="obs-table">
                @foreach($observations as $i => $obs)
                    <tr>
                        <td class="obs-title">{{ $i + 1 }}. {{ $obs['title'] }}</td>
                        <td style="font-size:12px;color:#374151;">{{ $obs['text'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

    </div>{{-- /rpt-body --}}

    <div class="rpt-footer">
        <span>Generated by: {{ $user->name }} ({{ match($user->role) { 'A' => 'Super Admin', 'PC' => 'Program Coordinator', default => $user->role } }}) &middot; Date: {{ now()->format('d F Y') }} &middot; CFIP — Module Analytics Report — {{ $selectedDomain->name }} Domain</span>
        <span>CFIP — CONFIDENTIAL</span>
    </div>

</div>{{-- /report-wrap --}}
</body>
</html>
