<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* ── Welcome Banner ─────────────────────────────────── */
        .welcome-banner {
            background: var(--cfip-blue-light);
            border-left: 3px solid var(--cfip-blue);
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 14px;
            color: var(--cfip-blue);
            margin-bottom: 20px;
        }

        /* ── KPI amber / red overrides ──────────────────────── */
        .kpi-card.amber::before    { background: var(--cfip-amber); }
        .kpi-card.cfip-red::before { background: var(--cfip-red); }
        .kpi-icon.amber    { background: #fef3c7; color: var(--cfip-amber); }
        .kpi-icon.cfip-red { background: #fee2e1; color: var(--cfip-red); }
        .kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

        /* ── Shared card shell ──────────────────────────────── */
        .overview-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 20px 22px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .overview-card-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 18px;
        }

        /* ── Cohort trend chart card ────────────────────────── */
        .trend-card {
            margin-bottom: 20px;
        }

        .trend-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .trend-card-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .trend-kpi-label {
            text-align: right;
        }

        .trend-kpi-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--cfip-blue);
            line-height: 1;
        }

        .trend-kpi-sub {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ── Middle row (60 / 40) ───────────────────────────── */
        .overview-mid-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        /* ── Level progress rows ────────────────────────────── */
        .level-progress-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            text-decoration: none;
        }

        .level-progress-row:last-child { margin-bottom: 0; }

        .level-progress-label {
            width: 120px;
            flex-shrink: 0;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .level-progress-bar-wrap {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .level-progress-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--cfip-blue);
            transition: width 0.8s ease;
        }

        .level-progress-fill.inactive { background: #d1d5db; }

        .level-progress-pct {
            width: 36px;
            text-align: right;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            flex-shrink: 0;
        }

        .level-status-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            white-space: nowrap;
        }

        .level-status-badge.active   { background: #dcfce7; color: #15803d; }
        .level-status-badge.inactive { background: #f3f4f6; color: #9ca3af; }

        /* ── At-risk panel ──────────────────────────────────── */
        .atrisk-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .atrisk-badge {
            background: var(--cfip-red);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .atrisk-scroll {
            max-height: 260px;
            overflow-y: auto;
            margin-right: -4px;
            padding-right: 4px;
        }

        .atrisk-scroll::-webkit-scrollbar { width: 4px; }
        .atrisk-scroll::-webkit-scrollbar-track { background: transparent; }
        .atrisk-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .atrisk-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .atrisk-item:last-of-type { border-bottom: none; }

        .atrisk-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .atrisk-info { flex: 1; min-width: 0; }

        .atrisk-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--cfip-blue);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-decoration: none;
            cursor: pointer;
        }

        .atrisk-name:hover { text-decoration: underline; }

        .atrisk-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .atrisk-progress {
            font-size: 13px;
            font-weight: 700;
            color: var(--cfip-red);
            flex-shrink: 0;
        }

        /* ── Bottom row (50 / 50) ───────────────────────────── */
        .overview-bottom-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        /* ── Domain completion bars ─────────────────────────── */
        .domain-bar-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .domain-bar-label {
            font-size: 12px;
            color: var(--text-secondary);
            width: 110px;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .domain-bar-track {
            flex: 1;
            height: 7px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .domain-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--cfip-blue);
        }

        .domain-bar-pct {
            width: 34px;
            text-align: right;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            flex-shrink: 0;
        }

        /* ── Generate Report button ─────────────────────────── */
        .generate-report-btn {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--cfip-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.45rem 0.9rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            letter-spacing: 0.01em;
            white-space: nowrap;
            font-family: inherit;
        }
        .generate-report-btn:hover:not(:disabled) { background: #163d84; }
        .generate-report-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .generate-report-btn svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* ── Responsive ──────────────────────────────────────── */
        @media (max-width: 1024px) {
            .overview-mid-row    { grid-template-columns: 1fr; }
            .overview-bottom-row { grid-template-columns: 1fr; }
            .trend-kpi-value     { font-size: 22px; }
        }

        @media (max-width: 768px) {
            .trend-kpi-value     { font-size: 20px; }
            .overview-card       { padding: 14px; }
            .atrisk-scroll       { max-height: 200px; }
            .domain-bar-label    { width: 90px; font-size: 11px; }
        }

        @media (max-width: 480px) {
            .overview-mid-row    { gap: 10px; }
            .overview-bottom-row { gap: 10px; }
            .trend-kpi-value     { font-size: 18px; }
            .overview-card       { padding: 12px; }
        }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

{{-- SIDEBAR --}}
@include('partials.sidebar')

{{-- MAIN --}}
<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">Dashboard — Overview</span>
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
                    <polyline points="10 9 9 9 8 9"/>
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

    {{-- CONTENT --}}
    <div class="content">

        @php
            $atRiskCount         = $atRiskCount         ?? 0;
            $atRiskLearners      = $atRiskLearners      ?? collect();
            $entryDomains        = $entryDomains        ?? collect();
            $studentsUrl         = $studentsUrl         ?? '#';
            $analyticsDomainsUrl = $analyticsDomainsUrl ?? '#';
            $trendLabels         = $trendLabels         ?? [];
            $trendData           = $trendData           ?? [];
            $domainColorMap = [
                'Foundation'               => '#1a4fa8',
                'Legal & Ethics'           => '#22c7b8',
                'Crime Investigation'      => '#f7b84f',
                'Soft Skill Competencies'  => '#7f77dd',
                'Investigation Techniques' => '#d85a30',
            ];
        @endphp

        {{-- KPI CARDS --}}
        <div class="kpi-grid">

            <div class="kpi-card blue">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($totalEnrollment) }}</div>
                    <div class="kpi-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                            <path d="M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Total Enrollment</div>
                <div class="kpi-sub">Across all agencies</div>
            </div>

            <div class="kpi-card teal">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($completionRate, 1) }}%</div>
                    <div class="kpi-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Course Completion Rate</div>
                <div class="kpi-sub">Entry Level overall</div>
            </div>

            <div class="kpi-card amber">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($inProgressLearners) }}</div>
                    <div class="kpi-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Active Learners</div>
                <div class="kpi-sub">Currently in a module</div>
            </div>

            <div class="kpi-card cfip-red">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($atRiskCount) }}</div>
                    <div class="kpi-icon cfip-red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">At-Risk Learners</div>
                <div class="kpi-sub">0–49% overall progress</div>
            </div>

        </div>{{-- /kpi-grid --}}


        {{-- COHORT PROGRESS TREND (Chart.js) --}}
        <div class="overview-card trend-card">
            <div class="trend-card-header">
                <div>
                    <div class="overview-card-title" style="margin-bottom: 2px;">Cohort Progress Trend</div>
                    <div class="trend-card-subtitle">Simulated weekly completion rate from cohort start to present</div>
                </div>
                <div class="trend-kpi-label">
                    <div class="trend-kpi-value">{{ number_format($completionRate, 1) }}%</div>
                    <div class="trend-kpi-sub">Current rate</div>
                </div>
            </div>
            <div style="position: relative; height: 200px;">
                <canvas id="cohortTrendChart"></canvas>
            </div>
        </div>


        {{-- MIDDLE ROW: Level Progress + At-Risk --}}
        <div class="overview-mid-row">

            {{-- Level Progress (60%) --}}
            <div class="overview-card">
                <div class="overview-card-title">Program Level Progress</div>

                @foreach($levels as $level)
                    @php
                        $stats  = $levelStats[$level->name] ?? ['total' => 0, 'pass' => 0, 'rate' => 0];
                        $pct    = $stats['rate'] ?? 0;
                        $active = ($stats['total'] ?? 0) > 0;
                    @endphp
                    <a href="{{ $analyticsDomainsUrl }}?level_id={{ $level->id }}"
                       class="level-progress-row">
                        <div class="level-progress-label">{{ $level->name }} Level</div>
                        <div class="level-progress-bar-wrap">
                            <div class="level-progress-fill {{ $active ? '' : 'inactive' }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                        <div class="level-progress-pct">{{ $pct }}%</div>
                        <span class="level-status-badge active">Active</span>
                    </a>
                @endforeach

                @if($entryDomains->isNotEmpty())
                <div style="border-top:1px solid #f3f4f6;margin:14px 0 12px;"></div>
                <div style="font-size:10px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6b7280;margin-bottom:12px;">Domain Completion — Entry Level</div>
                @foreach($entryDomains as $domain)
                    @php $dc = $domainColorMap[$domain->name] ?? '#1a4fa8'; @endphp
                    <div class="domain-bar-row">
                        <div class="domain-bar-label" title="{{ $domain->name }}">{{ $domain->name }}</div>
                        <div class="domain-bar-track">
                            <div class="domain-bar-fill" style="width:{{ $domain->rate }}%;background:{{ $dc }};"></div>
                        </div>
                        <div class="domain-bar-pct">{{ $domain->rate }}%</div>
                    </div>
                @endforeach
                @endif
            </div>

            {{-- At-Risk Learners (40%) --}}
            <div class="overview-card">
                <div class="atrisk-header">
                    <div class="overview-card-title" style="margin-bottom: 0">At-Risk Learners</div>
                    <span class="atrisk-badge">{{ $atRiskCount }}</span>
                    <span style="font-size:11px;color:var(--text-muted);margin-left:2px">0–49% progress</span>
                </div>

                <div class="atrisk-scroll">
                @forelse($atRiskLearners as $learner)
                    @php
                        $avatarBg   = $learner->progress < 20 ? '#fee2e2' : '#fef3c7';
                        $avatarText = $learner->progress < 20 ? '#b91c1c' : '#92400e';
                        $progColor  = $learner->progress < 20 ? '#e24b4a' : '#f59e0b';
                    @endphp
                    <div class="atrisk-item">
                        <div class="atrisk-avatar" style="background:{{ $avatarBg }};color:{{ $avatarText }}">
                            {{ $learner->initials }}
                        </div>
                        <div class="atrisk-info">
                            <a href="{{ $studentsUrl }}?drawer={{ urlencode($learner->user_id) }}" class="atrisk-name">
                                {{ $learner->name }}
                            </a>
                            <div class="atrisk-meta">{{ $learner->dept }}</div>
                        </div>
                        <div class="atrisk-progress" style="color:{{ $progColor }}">{{ $learner->progress }}%</div>
                    </div>
                @empty
                    <p style="font-size:13px;color:#9ca3af;text-align:center;padding:20px 0">
                        No at-risk learners at this time.
                    </p>
                @endforelse
                </div>
            </div>

        </div>{{-- /overview-mid-row --}}


        {{-- BOTTOM ROW: Weakest Modules + Strongest Modules --}}
        <div class="overview-bottom-row">

            {{-- Weakest Modules --}}
            <div class="overview-card">
                <div class="overview-card-title">Weakest Modules</div>

                @forelse($weakTopics as $topic)
                    <div class="topic-item">
                        <div class="topic-thumb weak">{{ $topic->course_code ?? '—' }}</div>
                        <div class="topic-info">
                            <div class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</div>
                            <div class="topic-bar-wrap">
                                <div class="topic-bar-bg">
                                    <div class="topic-bar-fill weak" style="width:{{ $topic->avg_progress }}%"></div>
                                </div>
                                <span class="topic-pct weak">{{ $topic->avg_progress }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:12px;color:#9ca3af">No data available.</p>
                @endforelse
            </div>

            {{-- Strongest Modules --}}
            <div class="overview-card">
                <div class="overview-card-title">Strongest Modules</div>

                @forelse($strongTopics ?? collect() as $topic)
                    <div class="topic-item">
                        <div class="topic-thumb">{{ $topic->course_code ?? '—' }}</div>
                        <div class="topic-info">
                            <div class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</div>
                            <div class="topic-bar-wrap">
                                <div class="topic-bar-bg">
                                    <div class="topic-bar-fill strong" style="width:{{ $topic->avg_progress }}%"></div>
                                </div>
                                <span class="topic-pct correct">{{ $topic->avg_progress }}%</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:12px;color:#9ca3af">No data available.</p>
                @endforelse
            </div>

        </div>{{-- /overview-bottom-row --}}

    </div>{{-- /content --}}
</div>{{-- /main --}}


<script>
/* ── Cohort Trend Line Chart ──────────────────────────────── */
(function () {
    const ctx = document.getElementById('cohortTrendChart').getContext('2d');

    const grad = ctx.createLinearGradient(0, 0, 0, 200);
    grad.addColorStop(0, 'rgba(26,79,168,0.15)');
    grad.addColorStop(1, 'rgba(26,79,168,0)');

    const labels = @json($trendLabels);
    const data   = @json($trendData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor: '#1a4fa8',
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: (c) => c.dataIndex === data.length - 1 ? 5 : 2,
                pointBackgroundColor: '#1a4fa8',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 1.5,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        color: '#6b7280',
                        maxTicksLimit: 10,
                        maxRotation: 0,
                    }
                },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Poppins', size: 11 },
                        color: '#6b7280',
                        callback: (v) => v + '%',
                        stepSize: 25,
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1f36',
                    titleColor: '#9ca3af',
                    bodyColor: '#ffffff',
                    titleFont: { family: 'Poppins', size: 11 },
                    bodyFont: { family: 'Poppins', size: 13, weight: '600' },
                    padding: { x: 10, y: 8 },
                    displayColors: false,
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (c) => c.parsed.y + '% completion',
                    }
                }
            },
            animation: { duration: 900, easing: 'easeInOutQuart' },
        }
    });
})();

/* ── Generate Report (jsPDF) ─────────────────────────────── */
const _userName = @json($user->name);

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
        const cohort     = currentUrl.searchParams.get('cohort') || '';
        const agency     = currentUrl.searchParams.get('agency') || '';

        const baseUrl = {!! $isPc ? json_encode(route('pc.reports.generate')) : json_encode(route('admin.reports.generate')) !!};
        const params  = new URLSearchParams();
        if (cohort) params.append('cohort', cohort);
        if (agency) params.append('agency', agency);

        window.open(baseUrl + (params.toString() ? '?' + params.toString() : ''), '_blank');
        await logReport('Dashboard Overview', 'PDF');
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
</body>
</html>
