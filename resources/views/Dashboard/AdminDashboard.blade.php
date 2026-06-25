<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

{{-- AJAX filter spinner (kept for in-page filter updates) --}}
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <svg class="spin" style="width:32px;height:32px;margin:0 auto 12px;display:block;color:#4f6ef7"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <polyline points="1 20 1 14 7 14"/>
            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
        </svg>
        Refreshing data…
    </div>
</div>

{{-- SIDEBAR --}}
@include('partials.sidebar')

{{-- MAIN --}}
<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">FOUNDATION</span>
            <svg class="page-title-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
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
            @include('partials.api-dot')
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- KPI CARDS --}}
        <div class="kpi-grid">

            <div class="kpi-card blue">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($totalEnrollment) }}</div>
                    <div class="kpi-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi-label">Total enrollment</div>
                <div class="kpi-trend up">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    +1.01% <span class="week">this week</span>
                </div>
            </div>

            <div class="kpi-card teal">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($completionRate, 1) }}%</div>
                    <div class="kpi-icon teal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                </div>
                <div class="kpi-label">Course completion rate</div>
                <div class="kpi-trend up">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    +0.49% <span class="week">this week</span>
                </div>
            </div>

            <div class="kpi-card green">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($inProgress) }}</div>
                    <div class="kpi-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="kpi-label">In Progress Learners</div>
                <div class="kpi-trend down">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                    -0.91% <span class="week">this week</span>
                </div>
            </div>

            <div class="kpi-card red">
                <div class="kpi-top">
                    <div class="kpi-value">{{ number_format($notStarted) }}</div>
                    <div class="kpi-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                </div>
                <div class="kpi-label">Not Started Learners</div>
                <div class="kpi-trend up">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    +1.51% <span class="week">this week</span>
                </div>
            </div>

        </div>{{-- /kpi-grid --}}


        {{-- CHARTS ROW --}}
        <div class="charts-row">

            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">Foundation Module Progress</span>
                    <div class="filter-group">

                        {{-- COHORT DROPDOWN
                             value  = group_id  (sent to AJAX)
                             label  = group name (shown to user)
                        --}}
                        <select class="filter-select" id="cohortFilter" onchange="applyFilters()">
                            <option value="">All Cohorts</option>
                            @foreach($cohorts as $cohort)
                                <option
                                    value="{{ $cohort->group_id }}"
                                    {{ $selectedCohort === $cohort->group_id ? 'selected' : '' }}
                                >
                                    {{ $cohort->name }}
                                </option>
                            @endforeach
                        </select>

                        {{-- AGENCY DROPDOWN
                             value  = department_id  (sent to AJAX)
                             label  = department name (shown to user)
                        --}}
                        <select class="filter-select" id="agencyFilter" onchange="applyFilters()">
                            <option value="">All Agencies</option>
                            @foreach($agencies as $agency)
                                <option
                                    value="{{ $agency->department_id }}"
                                    {{ $selectedAgency === $agency->department_id ? 'selected' : '' }}
                                >
                                    {{ $agency->name }}
                                </option>
                            @endforeach
                        </select>

                    </div>
                </div>
                <div class="bar-chart-wrap">
                    <canvas id="fdBarChart"></canvas>
                </div>
                <div class="bar-legend">
                    <div class="legend-item"><div class="legend-dot" style="background:#4f6ef7"></div> Pass</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#f7b84f"></div> In Progress</div>
                    <div class="legend-item"><div class="legend-dot" style="background:#ff6b6b"></div> Failed</div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <span class="chart-title">Foundation Module Enrollment</span>
                </div>
                <div class="pie-wrap">
                    <canvas id="fdPieChart"></canvas>
                </div>
                @php
                    $pieColors  = ['#4f6ef7', '#ff9eb5', '#22c7b8'];
                    $fdKeys     = ['FD01', 'FD02', 'FD03'];
                    $fdTotals   = [];
                    foreach ($fdKeys as $fd) {
                        $fdTotals[$fd] = $barChart[$fd]['pass'] + $barChart[$fd]['progress'] + $barChart[$fd]['failed'];
                    }
                    $grandTotal = array_sum($fdTotals);
                @endphp
                <div class="pie-labels">
                    @foreach($fdKeys as $i => $fd)
                        @php $pct = $grandTotal > 0 ? round(($fdTotals[$fd] / $grandTotal) * 100, 1) : 0; @endphp
                        <div class="pie-label-row">
                            <div class="pie-label-left">
                                <div class="pie-dot" style="background:{{ $pieColors[$i] }}"></div>
                                <span>{{ $fd }}</span>
                            </div>
                            <div class="pie-label-right">{{ $fdTotals[$fd] }} &middot; {{ $pct }}%</div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>{{-- /charts-row --}}


        {{-- TOPICS ROW --}}
        <div class="topics-row">

            <div class="topic-card">
                <div class="topic-title">Weakest Topics</div>
                @forelse($weakTopics as $topic)
                    @php
                        $initials = implode('', array_map(
                            fn($w) => strtoupper(substr($w, 0, 1)),
                            array_slice(explode(' ', $topic->module_title), 0, 3)
                        ));
                    @endphp
                    <div class="topic-item">
                        <div class="topic-thumb weak">{{ $initials }}</div>
                        <div class="topic-info">
                            <div class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</div>
                            <div class="topic-bar-wrap">
                                <div class="topic-bar-bg">
                                    <div class="topic-bar-fill weak" style="width:{{ $topic->avg_progress }}%"></div>
                                </div>
                                <span class="topic-pct weak">{{ $topic->avg_progress }}% Correct</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="font-size:12px;color:#9ca3af">No data available.</p>
                @endforelse
            </div>

            <div class="topic-card">
                <div class="topic-title">Strongest Topics</div>
                @forelse($strongTopics as $topic)
                    @php
                        $initials = implode('', array_map(
                            fn($w) => strtoupper(substr($w, 0, 1)),
                            array_slice(explode(' ', $topic->module_title), 0, 3)
                        ));
                    @endphp
                    <div class="topic-item">
                        <div class="topic-thumb">{{ $initials }}</div>
                        <div class="topic-info">
                            <div class="topic-name" title="{{ $topic->module_title }}">{{ $topic->module_title }}</div>
                            <div class="topic-bar-wrap">
                                <div class="topic-bar-bg">
                                    <div class="topic-bar-fill strong" style="width:{{ $topic->avg_progress }}%"></div>
                                </div>
                                <span class="topic-pct correct">{{ $topic->avg_progress }}% Correct</span>
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
const barData    = @json($barChart);
const filterUrl  = '{{ route("api.filter.barchart") }}';

const fdEnrollment = {
    FD01: barData.FD01.pass + barData.FD01.progress + barData.FD01.failed,
    FD02: barData.FD02.pass + barData.FD02.progress + barData.FD02.failed,
    FD03: barData.FD03.pass + barData.FD03.progress + barData.FD03.failed,
};

// ── Bar Chart ─────────────────────────────────────────────
const barChart = new Chart(document.getElementById('fdBarChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['FD01', 'FD02', 'FD03'],
        datasets: [
            { label: 'Pass',        data: [barData.FD01.pass,     barData.FD02.pass,     barData.FD03.pass],     backgroundColor: '#4f6ef7', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
            { label: 'In Progress', data: [barData.FD01.progress, barData.FD02.progress, barData.FD03.progress], backgroundColor: '#f7b84f', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
            { label: 'Failed',      data: [barData.FD01.failed,   barData.FD02.failed,   barData.FD03.failed],   backgroundColor: '#ff6b6b', borderRadius: 4, barPercentage: 0.6, categoryPercentage: 0.7 },
        ],
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1a1f36', padding: 10, cornerRadius: 8 } },
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { color: '#6b7280' } },
            y: { grid: { color: '#f3f4f6' }, border: { display: false }, ticks: { color: '#9ca3af', maxTicksLimit: 5 } },
        },
    },
});

// ── Doughnut Chart ────────────────────────────────────────
new Chart(document.getElementById('fdPieChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['FD01', 'FD02', 'FD03'],
        datasets: [{ data: [fdEnrollment.FD01, fdEnrollment.FD02, fdEnrollment.FD03], backgroundColor: ['#4f6ef7', '#ff9eb5', '#22c7b8'], borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }],
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '60%',
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1f36', padding: 10, cornerRadius: 8,
                callbacks: { label: ctx => { const t = ctx.dataset.data.reduce((a,b)=>a+b,0); return ` ${ctx.label}: ${ctx.parsed} (${t>0?((ctx.parsed/t)*100).toFixed(1):0}%)`; } },
            },
        },
    },
});

// ── Filters — called when EITHER dropdown changes ─────────
async function applyFilters() {
    const agency = document.getElementById('agencyFilter').value;
    const cohort = document.getElementById('cohortFilter').value;
    const overlay = document.getElementById('loadingOverlay');

    overlay.classList.add('active');

    try {
        // Build query string with whichever filters are set
        const params = new URLSearchParams();
        if (agency) params.append('agency', agency);
        if (cohort) params.append('cohort', cohort);
        // Preserve lesson toggle state from the current URL
        const currentUrl = new URL(window.location.href);
        if (currentUrl.searchParams.has('include_lessons')) {
            params.append('include_lessons', '1');
        }

        const url      = filterUrl + (params.toString() ? '?' + params.toString() : '');
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const result   = await response.json();

        if (result.success) {
            const d = result.barChart;
            barChart.data.datasets[0].data = [d.FD01.pass,     d.FD02.pass,     d.FD03.pass];
            barChart.data.datasets[1].data = [d.FD01.progress, d.FD02.progress, d.FD03.progress];
            barChart.data.datasets[2].data = [d.FD01.failed,   d.FD02.failed,   d.FD03.failed];
            barChart.update();
        }
    } catch (e) {
        console.error('Filter error:', e);
    } finally {
        overlay.classList.remove('active');
    }
}

</script>

@include('partials.api-status')
</body>
</html>