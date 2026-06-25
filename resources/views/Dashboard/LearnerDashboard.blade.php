<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ── Learner-specific KPI overrides ──────────────────── */
        .kpi-card.purple::before  { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .kpi-icon.purple          { background: #f5f3ff; color: #8b5cf6; }

        .kpi-badge-value {
            font-size: 22px;
            font-weight: 700;
            color: #d1d5db;
            line-height: 1;
            margin-bottom: 2px;
        }

        .coming-soon-pill {
            display: inline-block;
            background: #f3f4f6;
            color: #9ca3af;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            margin-top: 0.3rem;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
        }

        .status-badge.in-progress {
            background: rgba(247, 184, 79, 0.12);
            color: #d97706;
            border: 1px solid rgba(247, 184, 79, 0.3);
        }

        .status-badge.completed {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Domain progress fraction */
        .kpi-fraction {
            font-size: 34px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -1px;
            line-height: 1;
        }

        .kpi-fraction .denom {
            font-size: 20px;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Learner welcome strip */
        .welcome-strip {
            background: linear-gradient(135deg, #1e2a4a 0%, #2d3f6e 100%);
            border-radius: 14px;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            color: #fff;
        }

        .welcome-left { display: flex; flex-direction: column; gap: 4px; }

        .welcome-greeting {
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            font-weight: 500;
        }

        .welcome-name {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .welcome-sub {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 2px;
        }

        .welcome-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 10px 16px;
        }

        .welcome-badge-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(79, 110, 247, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .welcome-badge-icon svg { width: 18px; height: 18px; color: #a5b4fc; }

        .welcome-badge-text { display: flex; flex-direction: column; gap: 1px; }
        .welcome-badge-label { font-size: 10px; color: rgba(255,255,255,0.45); font-weight: 500; }
        .welcome-badge-val   { font-size: 14px; font-weight: 700; color: #fff; }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

{{-- SIDEBAR --}}
@include('partials.learner-sidebar')

{{-- MAIN --}}
<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">My Dashboard</span>
            <svg class="page-title-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
            <span style="font-size:13px;color:#6b7280;font-weight:500">Student</span>
        </div>
        <div class="topbar-right">
            <div class="online-dot"></div>
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- WELCOME STRIP --}}
        <div class="welcome-strip">
            <div class="welcome-left">
                <span class="welcome-greeting">Welcome back,</span>
                <span class="welcome-name">{{ $user->name }}</span>
                <span class="welcome-sub">CFIP Foundation Programme · Learner</span>
            </div>
            <div class="welcome-badge">
                <div class="welcome-badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <div class="welcome-badge-text">
                    <span class="welcome-badge-label">Current Programme</span>
                    <span class="welcome-badge-val">Foundation (CI 01)</span>
                </div>
            </div>
        </div>

        {{-- KPI CARDS --}}
        <div class="kpi-grid">

            {{-- Current Level --}}
            <div class="kpi-card blue">
                <div class="kpi-top">
                    <div class="kpi-value" style="font-size:28px;letter-spacing:-0.5px">CI 01</div>
                    <div class="kpi-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Current Level</div>
                <div class="kpi-trend up">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    Foundation Level
                </div>
            </div>

            {{-- Domains Completed --}}
            <div class="kpi-card teal">
                <div class="kpi-top">
                    <div>
                        <span class="kpi-fraction">3</span>
                        <span class="kpi-fraction denom">/5</span>
                    </div>
                    <div class="kpi-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Domains Completed</div>
                <div class="kpi-trend up">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    2 remaining
                </div>
            </div>

            {{-- Badges Earned --}}
            <div class="kpi-card purple">
                <div class="kpi-top">
                    <div>
                        <div class="kpi-badge-value">—</div>
                    </div>
                    <div class="kpi-icon purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="6"/>
                            <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Badges Earned</div>
                <div class="coming-soon-pill">Coming Soon</div>
            </div>

            {{-- Status --}}
            <div class="kpi-card green">
                <div class="kpi-top">
                    <div class="kpi-value" style="font-size:18px;letter-spacing:-0.3px;margin-top:6px">In Progress</div>
                    <div class="kpi-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Overall Status</div>
                <div class="status-badge in-progress">
                    <span class="status-dot"></span>
                    Active Learner
                </div>
            </div>

        </div>{{-- /kpi-grid --}}


        {{-- CHARTS ROW --}}
        <div class="charts-row">

            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">Foundation Module Progress</span>
                </div>
                <div class="bar-chart-wrap">
                    <canvas id="fdBarChart"></canvas>
                </div>
                <div class="bar-legend">
                    <div class="legend-item"><div class="legend-dot" style="background:#4f6ef7"></div> Pass</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#f7b84f"></div> In Progress</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#ff6b6b"></div> Failed</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#d1d5db"></div> Not Started</div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">Foundation Module Enrollment</span>
                </div>
                <div class="pie-wrap">
                    <canvas id="fdPieChart"></canvas>
                </div>
                <div class="pie-labels">
                    <div class="pie-label-row">
                        <div class="pie-label-left"><div class="pie-dot" style="background:#4f6ef7"></div><span>FD01</span></div>
                        <div class="pie-label-right">42 &middot; 38%</div>
                    </div>
                    <div class="pie-label-row">
                        <div class="pie-label-left"><div class="pie-dot" style="background:#ff9eb5"></div><span>FD02</span></div>
                        <div class="pie-label-right">38 &middot; 34%</div>
                    </div>
                    <div class="pie-label-row">
                        <div class="pie-label-left"><div class="pie-dot" style="background:#22c7b8"></div><span>FD03</span></div>
                        <div class="pie-label-right">31 &middot; 28%</div>
                    </div>
                </div>
            </div>

        </div>{{-- /charts-row --}}


        {{-- TOPICS ROW --}}
        <div class="topics-row">

            <div class="topic-card">
                <div class="topic-title">Weakest Topics</div>

                <div class="topic-item">
                    <div class="topic-thumb weak">FIN</div>
                    <div class="topic-info">
                        <div class="topic-name" title="Financial Crime Typologies">Financial Crime Typologies</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill weak" style="width:38%"></div>
                            </div>
                            <span class="topic-pct weak">38% Correct</span>
                        </div>
                    </div>
                </div>

                <div class="topic-item">
                    <div class="topic-thumb weak">AML</div>
                    <div class="topic-info">
                        <div class="topic-name" title="AML Frameworks & Regulations">AML Frameworks &amp; Regulations</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill weak" style="width:45%"></div>
                            </div>
                            <span class="topic-pct weak">45% Correct</span>
                        </div>
                    </div>
                </div>

                <div class="topic-item">
                    <div class="topic-thumb weak">KYC</div>
                    <div class="topic-info">
                        <div class="topic-name" title="KYC & Customer Due Diligence">KYC &amp; Customer Due Diligence</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill weak" style="width:52%"></div>
                            </div>
                            <span class="topic-pct weak">52% Correct</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="topic-card">
                <div class="topic-title">Strongest Topics</div>

                <div class="topic-item">
                    <div class="topic-thumb">INT</div>
                    <div class="topic-info">
                        <div class="topic-name" title="Introduction to Financial Investigation">Introduction to Financial Investigation</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill strong" style="width:92%"></div>
                            </div>
                            <span class="topic-pct correct">92% Correct</span>
                        </div>
                    </div>
                </div>

                <div class="topic-item">
                    <div class="topic-thumb">ETH</div>
                    <div class="topic-info">
                        <div class="topic-name" title="Ethics & Professional Standards">Ethics &amp; Professional Standards</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill strong" style="width:87%"></div>
                            </div>
                            <span class="topic-pct correct">87% Correct</span>
                        </div>
                    </div>
                </div>

                <div class="topic-item">
                    <div class="topic-thumb">RPT</div>
                    <div class="topic-info">
                        <div class="topic-name" title="Reporting Obligations">Reporting Obligations</div>
                        <div class="topic-bar-wrap">
                            <div class="topic-bar-bg">
                                <div class="topic-bar-fill strong" style="width:81%"></div>
                            </div>
                            <span class="topic-pct correct">81% Correct</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>{{-- /topics-row --}}

    </div>{{-- /content --}}
</div>{{-- /main --}}


<script>
// ── Dummy bar chart data ──────────────────────────────────
const barData = {
    FD01: { pass: 42, progress: 18, failed: 8,  not_started: 12 },
    FD02: { pass: 38, progress: 22, failed: 11, not_started: 9  },
    FD03: { pass: 31, progress: 25, failed: 14, not_started: 16 },
};

new Chart(document.getElementById('fdBarChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['FD01', 'FD02', 'FD03'],
        datasets: [
            { label: 'Pass',        data: [barData.FD01.pass,        barData.FD02.pass,        barData.FD03.pass],        backgroundColor: '#4f6ef7', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
            { label: 'In Progress', data: [barData.FD01.progress,    barData.FD02.progress,    barData.FD03.progress],    backgroundColor: '#f7b84f', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
            { label: 'Failed',      data: [barData.FD01.failed,      barData.FD02.failed,      barData.FD03.failed],      backgroundColor: '#ff6b6b', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
            { label: 'Not Started', data: [barData.FD01.not_started, barData.FD02.not_started, barData.FD03.not_started], backgroundColor: '#d1d5db', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { boxWidth: 12, padding: 16, color: '#374151', font: { size: 12 } },
            },
            tooltip: { backgroundColor: '#1a1f36', padding: 10, cornerRadius: 8 },
        },
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#6b7280' } },
            y: { grid: { color: '#f3f4f6' }, border: { display: false }, ticks: { color: '#9ca3af', maxTicksLimit: 5 } },
        },
    },
});

// ── Dummy doughnut chart data ─────────────────────────────
new Chart(document.getElementById('fdPieChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['FD01', 'FD02', 'FD03'],
        datasets: [{
            data: [42, 38, 31],
            backgroundColor: ['#4f6ef7', '#ff9eb5', '#22c7b8'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '60%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1f36',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => {
                        const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        return ` ${ctx.label}: ${ctx.parsed} (${t > 0 ? ((ctx.parsed / t) * 100).toFixed(1) : 0}%)`;
                    },
                },
            },
        },
    },
});
</script>

@include('partials.api-status')
</body>
</html>
