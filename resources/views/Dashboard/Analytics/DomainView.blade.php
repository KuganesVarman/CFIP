<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — Domains | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        /* ── KPI amber overrides + sub-label ────────────────── */
        .kpi-card.amber::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .kpi-icon.amber          { background: #fef3c7; color: #f59e0b; }
        .kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

        /* ── Level dropdown ──────────────────────────────────── */
        .level-dropdown {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
            position: relative;
            font-family: inherit;
        }
        .level-dropdown select {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }
        .level-dropdown svg { flex-shrink: 0; color: var(--text-muted); }

        /* ── Generate Report button ──────────────────────────── */
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
            .charts-mid-row { grid-template-columns: 1fr; }
            .topics-row     { grid-template-columns: 1fr 1fr; }
            .domain-cards-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
        }

        @media (max-width: 768px) {
            .charts-mid-row { grid-template-columns: 1fr; gap: 12px; }
            .topics-row     { grid-template-columns: 1fr; gap: 10px; }
            .domain-cards-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .domain-card    { padding: 12px 14px; }
            .domain-card-rate { font-size: 17px; }
            .chart-card     { padding: 14px; }
            .topic-card     { padding: 14px; }
            .level-dropdown { font-size: 12px; padding: 5px 10px; }
        }

        @media (max-width: 480px) {
            .domain-cards-grid { grid-template-columns: 1fr; }
            .domain-card-rate  { font-size: 15px; }
            .topics-row        { grid-template-columns: 1fr; }
        }

        /* ── Domain cards grid ───────────────────────────────── */
        .domain-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .domain-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1.5px solid var(--border);
            box-shadow: var(--shadow);
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            cursor: pointer;
            transition: border-color 0.18s, box-shadow 0.18s;
            user-select: none;
        }
        .domain-card:hover {
            border-color: rgba(26,79,168,0.4);
            box-shadow: 0 2px 12px rgba(26,79,168,0.1);
        }
        .domain-card.active {
            border-color: var(--cfip-blue);
            box-shadow: 0 0 0 2px rgba(26,79,168,0.15), var(--shadow);
        }

        .domain-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .domain-card-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .domain-card-rate {
            font-size: 20px;
            font-weight: 700;
            color: var(--cfip-blue);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .domain-progress-track {
            height: 5px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }

        .domain-progress-fill {
            height: 100%;
            border-radius: 3px;
            background: var(--cfip-blue);
            transition: width 0.6s ease;
        }

        .domain-status-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 8px;
            white-space: nowrap;
        }
        .status-pill.pass     { background: #dcfce7; color: #15803d; }
        .status-pill.progress { background: #fef3c7; color: #b45309; }
        .status-pill.failed   { background: #fee2e1; color: #b91c1c; }
        .status-pill.ns       { background: #f3f4f6; color: #6b7280; }

        .domain-card-link {
            font-size: 11px;
            color: var(--cfip-blue);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-top: auto;
        }
        .domain-card-link:hover { text-decoration: underline; }

        /* ── Charts mid-row ──────────────────────────────────── */
        .charts-mid-row {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .chart-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 20px 22px;
        }

        .chart-card-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .chart-card-subtitle {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .chart-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .trend-kpi-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--cfip-blue);
            line-height: 1;
        }

        .trend-kpi-sub {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 2px;
            text-align: right;
        }

        /* ── Reset filter hint ───────────────────────────────── */
        .filter-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 10px;
            display: none;
        }
        .filter-hint.visible { display: block; }
        .filter-hint a {
            color: var(--cfip-blue);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .filter-hint a:hover { text-decoration: underline; }

        /* ── Topics row ──────────────────────────────────────── */
        .topics-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .topic-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 18px 20px;
        }

        .topic-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 14px;
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
            <div class="level-dropdown">
                <span id="levelLabel">{{ $selectedLevel->name }} Level</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                     style="width:14px;height:14px"><polyline points="6 9 12 15 18 9"/></svg>
                <select onchange="changeLevel(this.value)">
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}"
                            {{ $level->id == $selectedLevel->id ? 'selected' : '' }}>
                            {{ $level->name }} Level
                        </option>
                    @endforeach
                </select>
            </div>
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
            $trendLabels = $trendLabels ?? [];
            $trendData   = $trendData   ?? [];
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
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-sub">{{ $selectedLevel->name }} Level overall</div>
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
                <div class="kpi-label">In Progress</div>
                <div class="kpi-sub">Still working through modules</div>
            </div>

            <div class="kpi-card red">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($notStartedLearners) }}</div>
                    <div class="kpi-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Not Started</div>
                <div class="kpi-sub">No activity yet</div>
            </div>

        </div>{{-- /kpi-grid --}}


        {{-- DOMAIN SUMMARY CARDS (clickable) --}}
        @php
            $dcMap = [
                'Foundation'               => '#1a4fa8',
                'Legal & Ethics'           => '#22c7b8',
                'Crime Investigation'      => '#f7b84f',
                'Soft Skill Competencies'  => '#7f77dd',
                'Investigation Techniques' => '#d85a30',
            ];
        @endphp
        <div class="domain-cards-grid" id="domainCardsGrid">
            @forelse($domains as $domIdx => $domain)
                @php
                    $stats   = $domainStats[$domain->name] ?? ['pass' => 0, 'progress' => 0, 'failed' => 0, 'not_started' => 0, 'total' => 0];
                    $rate    = $stats['total'] > 0 ? round($stats['pass'] / $stats['total'] * 100, 1) : 0;
                    $dColor  = $dcMap[$domain->name] ?? '#1a4fa8';
                @endphp
                <div class="domain-card"
                     data-domain-idx="{{ $domIdx }}"
                     onclick="filterDomain({{ $domIdx }})">
                    <div class="domain-card-header">
                        <div class="domain-card-name">{{ $domain->name }}</div>
                        <div class="domain-card-rate" style="color:{{ $dColor }}">{{ $rate }}%</div>
                    </div>
                    <div class="domain-progress-track">
                        <div class="domain-progress-fill" style="width:{{ $rate }}%;background:{{ $dColor }};"></div>
                    </div>
                    <div style="display:flex;gap:12px;font-size:11px;margin-top:2px;">
                        <span style="color:#1d9e75;">&#10003; {{ $stats['pass'] }} passed</span>
                        <span style="color:#9ca3af;">&#9675; {{ $stats['not_started'] }} not started</span>
                    </div>
                    <a href="{{ $moduleViewUrl }}?domain_id={{ $stats['domain_id'] ?? $domain->id }}"
                       class="domain-card-link"
                       onclick="event.stopPropagation()">
                        View modules &#8594;
                    </a>
                </div>
            @empty
                <div style="color:var(--text-muted);font-size:13px;padding:20px 0;grid-column:1/-1">
                    No domains found for this level.
                </div>
            @endforelse
        </div>

        {{-- Filter hint (shown when a domain card is active) --}}
        <div class="filter-hint" id="filterHint">
            Showing data for <strong id="filterHintName"></strong> —
            <a onclick="resetFilter()">Show all domains</a>
        </div>


        {{-- CHARTS MID-ROW: Grouped bar chart + Cohort trend --}}
        <div class="charts-mid-row">

            {{-- Grouped bar chart --}}
            <div class="chart-card">
                <div class="chart-card-title">Domain Breakdown</div>
                <div class="chart-card-subtitle">Learner counts by completion status per domain</div>
                <div style="position: relative; height: 260px;">
                    <canvas id="domainBarChart"></canvas>
                </div>
            </div>

            {{-- Cohort trend line --}}
            <div class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <div class="chart-card-title" style="margin-bottom: 2px;">Cohort Progress Trend</div>
                        <div class="chart-card-subtitle" style="margin-bottom: 0;">Simulated weekly completion rate</div>
                    </div>
                    <div style="text-align:right">
                        <div class="trend-kpi-value">{{ number_format($completionRate, 1) }}%</div>
                        <div class="trend-kpi-sub">Current rate</div>
                    </div>
                </div>
                <div style="position: relative; height: 260px; margin-top: 14px;">
                    <canvas id="cohortTrendChart"></canvas>
                </div>
            </div>

        </div>{{-- /charts-mid-row --}}


        {{-- TOPICS ROW --}}
        <div class="topics-row">

            <div class="topic-card">
                <div class="topic-title">Weakest Modules</div>
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

            <div class="topic-card">
                <div class="topic-title">Strongest Modules</div>
                @forelse($strongTopics as $topic)
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

        </div>{{-- /topics-row --}}

    </div>{{-- /content --}}
</div>{{-- /main --}}


<script>
/* ── Data from server ─────────────────────────────────────── */
const _domainData   = @json($domainStats);
const _trendLabels  = @json($trendLabels);
const _trendData    = @json($trendData);
const _userName     = @json($user->name);
const _levelLabel   = document.getElementById('levelLabel').textContent.trim();

const _dNames   = Object.keys(_domainData);
const _dShort   = _dNames.map(n => n.length > 16 ? n.slice(0, 14) + '…' : n);

/* ── Colour definitions ───────────────────────────────────── */
const CLR = {
    pass:     { full: 'rgba(29,158,117,0.85)',  dim: 'rgba(29,158,117,0.15)'  },
    progress: { full: 'rgba(245,158,11,0.85)',  dim: 'rgba(245,158,11,0.15)'  },
    failed:   { full: 'rgba(226,75,74,0.85)',   dim: 'rgba(226,75,74,0.15)'   },
    ns:       { full: 'rgba(209,213,219,0.85)', dim: 'rgba(209,213,219,0.15)' },
};

function makeBg(clr, activeIdx) {
    return _dNames.map((_, i) =>
        activeIdx === null || i === activeIdx ? clr.full : clr.dim
    );
}

/* ── Grouped bar chart ────────────────────────────────────── */
let _activeIdx = null;

const barCtx = document.getElementById('domainBarChart').getContext('2d');
const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: _dShort,
        datasets: [
            {
                label: 'Passed',
                data: _dNames.map(n => _domainData[n].pass),
                backgroundColor: makeBg(CLR.pass, null),
                borderRadius: 3,
                borderSkipped: false,
            },
            {
                label: 'In Progress',
                data: _dNames.map(n => _domainData[n].progress),
                backgroundColor: makeBg(CLR.progress, null),
                borderRadius: 3,
                borderSkipped: false,
            },
            {
                label: 'Failed',
                data: _dNames.map(n => _domainData[n].failed),
                backgroundColor: makeBg(CLR.failed, null),
                borderRadius: 3,
                borderSkipped: false,
            },
            {
                label: 'Not Started',
                data: _dNames.map(n => _domainData[n].not_started),
                backgroundColor: makeBg(CLR.ns, null),
                borderRadius: 3,
                borderSkipped: false,
            },
        ]
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
                }
            },
            y: {
                grid: { color: 'rgba(0,0,0,0.05)' },
                border: { display: false },
                ticks: {
                    font: { family: 'Poppins', size: 11 },
                    color: '#6b7280',
                    precision: 0,
                }
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: 'Poppins', size: 11 },
                    usePointStyle: true,
                    pointStyleWidth: 8,
                    padding: 16,
                    color: '#374151',
                }
            },
            tooltip: {
                backgroundColor: '#1a1f36',
                titleColor: '#9ca3af',
                bodyColor: '#ffffff',
                titleFont: { family: 'Poppins', size: 11 },
                bodyFont: { family: 'Poppins', size: 12 },
                padding: { x: 10, y: 8 },
                callbacks: {
                    title: (items) => _dNames[items[0].dataIndex] || items[0].label,
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0) {
                navigateToModule(elements[0].index);
            }
        },
        onHover: (event, elements) => {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        },
        animation: { duration: 350 },
    }
});

/* ── Bar click → Module View ──────────────────────────────── */
function navigateToModule(domainIdx) {
    const name     = _dNames[domainIdx];
    const domainId = _domainData[name]?.domain_id;
    if (!domainId) return;

    const params   = new URLSearchParams();
    params.set('domain_id', domainId);

    // Carry forward whatever cohort / agency filters are active in the URL
    const current = new URLSearchParams(window.location.search);
    if (current.get('cohort')) params.set('cohort', current.get('cohort'));
    if (current.get('agency')) params.set('agency', current.get('agency'));

    window.location.href = '{{ $moduleViewUrl }}?' + params.toString();
}

/* ── Domain card click: filter the bar chart ──────────────── */
function filterDomain(idx) {
    const cards = document.querySelectorAll('.domain-card');
    const hint  = document.getElementById('filterHint');

    if (_activeIdx === idx) {
        _activeIdx = null;
        cards.forEach(c => c.classList.remove('active'));
        hint.classList.remove('visible');
    } else {
        _activeIdx = idx;
        cards.forEach((c, i) => c.classList.toggle('active', i === idx));
        document.getElementById('filterHintName').textContent = _dNames[idx] || '';
        hint.classList.add('visible');
    }

    // Rebuild background arrays for all 4 datasets
    const clrKeys = [CLR.pass, CLR.progress, CLR.failed, CLR.ns];
    barChart.data.datasets.forEach((ds, di) => {
        ds.backgroundColor = makeBg(clrKeys[di], _activeIdx);
    });
    barChart.update('none'); // instant, no re-animation
}

function resetFilter() {
    filterDomain(_activeIdx); // toggle off
}

/* ── Cohort trend line chart ──────────────────────────────── */
(function () {
    const ctx  = document.getElementById('cohortTrendChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0, 'rgba(26,79,168,0.15)');
    grad.addColorStop(1, 'rgba(26,79,168,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: _trendLabels,
            datasets: [{
                data: _trendData,
                borderColor: '#1a4fa8',
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: (c) => c.dataIndex === _trendData.length - 1 ? 5 : 2,
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
                        font: { family: 'Poppins', size: 10 },
                        color: '#6b7280',
                        maxTicksLimit: 8,
                        maxRotation: 0,
                    }
                },
                y: {
                    min: 0,
                    max: 100,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Poppins', size: 10 },
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
                    titleFont: { family: 'Poppins', size: 10 },
                    bodyFont: { family: 'Poppins', size: 12, weight: '600' },
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

/* ── Level switcher ───────────────────────────────────────── */
function changeLevel(levelId) {
    const params = new URLSearchParams(window.location.search);
    params.set('level_id', levelId);
    window.location.href = '{{ $analyticsDomainsUrl }}?' + params.toString();
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
        const cohort   = currentUrl.searchParams.get('cohort')   || '';
        const agency   = currentUrl.searchParams.get('agency')   || '';
        const levelId  = currentUrl.searchParams.get('level_id') || '';

        const baseUrl = {!! $isPc ? json_encode(route('pc.reports.domain.generate')) : json_encode(route('admin.reports.domain.generate')) !!};
        const params  = new URLSearchParams();
        if (levelId) params.append('level_id', levelId);
        if (cohort)  params.append('cohort', cohort);
        if (agency)  params.append('agency', agency);

        window.open(baseUrl + (params.toString() ? '?' + params.toString() : ''), '_blank');
        await logReport('Domain Analytics Report', 'PDF');
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
