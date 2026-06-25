<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Summary – CFIP Dev</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            color: #111827;
            padding: 2rem;
            max-width: 1100px;
        }

        a { color: #4f6ef7; text-decoration: none; font-size: 0.82rem; }
        a:hover { text-decoration: underline; }

        h1 { font-size: 1.4rem; font-weight: 700; color: #1e3a5f; margin-bottom: 0.2rem; }

        .sub {
            font-size: 0.82rem;
            color: #6b7280;
            margin-bottom: 1.75rem;
        }

        h2 {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
            margin: 1.75rem 0 0.6rem;
        }

        /* ── KPI strip ──────────────────────────────────────── */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .kpi-box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }

        .kpi-val {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            line-height: 1;
        }

        .kpi-val.warn { color: #dc2626; }

        .kpi-lbl {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.3rem;
        }

        /* ── Generic card ───────────────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        /* ── Tables ─────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        thead tr { background: #f9fafb; }

        th {
            padding: 0.6rem 1.1rem;
            text-align: left;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        td {
            padding: 0.6rem 1.1rem;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* ── Status badge ───────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 0.18rem 0.55rem;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-pass     { background: #d1fae5; color: #065f46; }
        .badge-progress { background: #fef3c7; color: #92400e; }
        .badge-failed   { background: #fee2e2; color: #991b1b; }
        .badge-notstart { background: #f3f4f6; color: #6b7280; }
        .badge-other    { background: #ede9fe; color: #5b21b6; }
        .badge-blue     { background: #dbeafe; color: #1d4ed8; }
        .badge-red      { background: #fee2e2; color: #991b1b; }

        /* ── Progress bar ───────────────────────────────────── */
        .bar-wrap {
            background: #f3f4f6;
            border-radius: 20px;
            height: 6px;
            width: 120px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 20px;
        }

        /* ── Course block (collapsible) ─────────────────────── */
        .course-block { border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 0.6rem; overflow: hidden; background: #fff; }

        .course-header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.75rem 1.1rem;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            user-select: none;
        }

        .course-header:hover { background: #f0f4ff; }
        .course-block.open .chevron { transform: rotate(180deg); }

        .chevron { font-size: 0.7rem; color: #9ca3af; transition: transform 0.2s; margin-left: auto; }

        .course-code-badge {
            font-size: 0.8rem;
            font-weight: 700;
            background: #4f6ef7;
            color: #fff;
            padding: 0.18rem 0.6rem;
            border-radius: 6px;
        }

        .course-id-mono { font-size: 0.72rem; color: #9ca3af; font-family: monospace; }

        .course-meta { font-size: 0.78rem; color: #6b7280; margin-left: auto; margin-right: 1rem; }

        .warn-chip {
            font-size: 0.7rem;
            font-weight: 700;
            background: #fee2e2;
            color: #991b1b;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
        }

        .ok-chip {
            font-size: 0.7rem;
            font-weight: 700;
            background: #d1fae5;
            color: #065f46;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
        }

        .course-body { padding: 1rem 1.1rem; }

        .course-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }

        .course-stat-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
        }

        .course-stat-val { font-size: 1.1rem; font-weight: 700; color: #111827; }
        .course-stat-lbl { font-size: 0.7rem; color: #9ca3af; margin-top: 0.1rem; }

        .expected-row {
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .expected-ok      { background: #d1fae5; color: #065f46; }
        .expected-warn    { background: #fee2e2; color: #991b1b; }
        .expected-unknown { background: #f3f4f6; color: #6b7280; }

        /* ── Status mini-table inside course block ───────────── */
        .status-mini { font-size: 0.8rem; }
        .status-mini td { padding: 0.3rem 0.5rem; border: none; }
        .status-mini tr:hover td { background: transparent; }

        /* ── Mono cell ──────────────────────────────────────── */
        .mono { font-family: monospace; font-size: 0.75rem; color: #6b7280; }

        /* ── Empty ──────────────────────────────────────────── */
        .empty-state {
            padding: 1.5rem;
            text-align: center;
            font-size: 0.82rem;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>

<h1>🔍 Data Summary — learner_module_results</h1>
<p class="sub">
    Live read from <code>learner_module_results</code> &nbsp;·&nbsp;
    <a href="{{ route('dev.course-structure') }}">← Course Structure</a>
    &nbsp;·&nbsp; Generated {{ now()->format('Y-m-d H:i:s') }}
</p>

{{-- ═══════════════════════════════════════════════════════
     1. OVERVIEW COUNTS
═══════════════════════════════════════════════════════ --}}
<h2>Overview</h2>
<div class="kpi-strip">
    <div class="kpi-box">
        <div class="kpi-val">{{ number_format($totalRows) }}</div>
        <div class="kpi-lbl">Total rows</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val">{{ number_format($distinctLearners) }}</div>
        <div class="kpi-lbl">Distinct learners</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val">{{ number_format($distinctCourses) }}</div>
        <div class="kpi-lbl">Distinct courses</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val">{{ number_format($distinctEnrollments) }}</div>
        <div class="kpi-lbl">Distinct enrollments</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val {{ $duplicates->count() > 0 ? 'warn' : '' }}">
            {{ number_format($duplicates->count()) }}
        </div>
        <div class="kpi-lbl">Duplicate combos</div>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════
     2. DEPARTMENT BREAKDOWN
═══════════════════════════════════════════════════════ --}}
<h2>Learners by Department</h2>
<p style="font-size:0.78rem;color:#6b7280;margin-bottom:0.75rem">
    Compares every learner in <code>users_ispring</code> against those who actually have rows in
    <code>learner_module_results</code>. <strong>Coverage&nbsp;%</strong> = learners with results ÷ total iSpring learners.
    A low % means data hasn't been synced for that department yet.
</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th style="width:130px">In iSpring</th>
                <th style="width:140px">Have results</th>
                <th style="width:100px">Coverage</th>
                <th style="width:120px">Result rows</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deptBreakdown as $dept)
                @php
                    $cov = $dept->coverage_pct;
                    $covColor = $cov === null ? '#6b7280'
                        : ($cov >= 80 ? '#065f46' : ($cov >= 40 ? '#92400e' : '#991b1b'));
                    $covBg = $cov === null ? '#f3f4f6'
                        : ($cov >= 80 ? '#d1fae5' : ($cov >= 40 ? '#fef3c7' : '#fee2e2'));
                @endphp
                <tr>
                    <td>
                        <strong>{{ $dept->dept_name }}</strong>
                        @if(!$dept->department_id)
                            <span style="font-size:0.7rem;color:#9ca3af;margin-left:4px">(not in iSpring / unlinked)</span>
                        @endif
                    </td>
                    <td>
                        @if($dept->total_in_ispring !== null)
                            {{ number_format($dept->total_in_ispring) }}
                        @else
                            <span style="color:#9ca3af;font-style:italic">unknown</span>
                        @endif
                    </td>
                    <td>{{ number_format($dept->learners_with_results) }}</td>
                    <td>
                        @if($cov !== null)
                            <span class="badge" style="background:{{ $covBg }};color:{{ $covColor }}">
                                {{ $cov }}%
                            </span>
                        @else
                            <span style="color:#9ca3af;font-size:0.75rem">—</span>
                        @endif
                    </td>
                    <td>{{ number_format($dept->result_rows) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">No results data found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($deptsNoResults->count() > 0)
    <div style="margin-bottom:1rem">
        <div style="font-size:0.75rem;font-weight:700;color:#991b1b;margin-bottom:0.4rem">
            ⚠ {{ $deptsNoResults->count() }} department(s) in iSpring with 0 result rows:
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:0.4rem">
            @foreach($deptsNoResults as $d)
                <span class="badge badge-red">
                    {{ $d->dept_name ?? '(no name)' }}
                    &nbsp;·&nbsp; {{ number_format($d->total_learners) }} learners
                </span>
            @endforeach
        </div>
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════
     3. COMPLETION STATUS BREAKDOWN
═══════════════════════════════════════════════════════ --}}
<h2>Completion Status Breakdown</h2>
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th style="width:100px">Count</th>
                <th style="width:80px">%</th>
                <th style="width:160px">Distribution</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statusBreakdown as $row)
                @php
                    $sl = strtolower($row->completion_status ?? '');
                    $badgeCls = match(true) {
                        str_contains($sl, 'pass')     => 'badge-pass',
                        str_contains($sl, 'progress') => 'badge-progress',
                        str_contains($sl, 'fail')     => 'badge-failed',
                        str_contains($sl, 'not')      => 'badge-notstart',
                        default                       => 'badge-other',
                    };
                    $barColor = match(true) {
                        str_contains($sl, 'pass')     => '#10b981',
                        str_contains($sl, 'progress') => '#f59e0b',
                        str_contains($sl, 'fail')     => '#ef4444',
                        str_contains($sl, 'not')      => '#d1d5db',
                        default                       => '#a78bfa',
                    };
                @endphp
                <tr>
                    <td><span class="badge {{ $badgeCls }}">{{ $row->completion_status ?? 'null' }}</span></td>
                    <td>{{ number_format($row->total) }}</td>
                    <td>{{ $row->pct }}%</td>
                    <td>
                        <div class="bar-wrap">
                            <div class="bar-fill" style="width:{{ $row->pct }}%;background:{{ $barColor }}"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">No data in table.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>


{{-- ═══════════════════════════════════════════════════════
     4. PER-COURSE BREAKDOWN
═══════════════════════════════════════════════════════ --}}
<h2>Per-Course Breakdown</h2>
<p style="font-size:0.78rem;color:#6b7280;margin-bottom:0.75rem">
    <strong>Expected rows</strong> = distinct learners × distinct modules per course. A gap means some learner/module results are missing.
</p>

@forelse($perCourse as $i => $c)
    @php
        $hasMissing = $c->missing_rows > 0;
        $hasExpected = $c->expected_rows > 0;
    @endphp
    <div class="course-block" id="cb-{{ $i }}">
        <div class="course-header" onclick="toggle({{ $i }})">
            @if($c->course_code)
                <span class="course-code-badge">{{ $c->course_code }}</span>
            @endif
            <span class="course-id-mono">{{ $c->course_id }}</span>
            @if($hasMissing)
                <span class="warn-chip">⚠ {{ number_format($c->missing_rows) }} missing</span>
            @elseif($hasExpected)
                <span class="ok-chip">✓ complete</span>
            @endif
            <span class="course-meta">
                {{ number_format($c->total_rows) }} rows &nbsp;·&nbsp;
                {{ number_format($c->learner_count) }} learners &nbsp;·&nbsp;
                {{ number_format($c->distinct_modules) }} modules
            </span>
            <span class="chevron">▼</span>
        </div>

        <div id="cb-body-{{ $i }}" style="display:none">
            <div class="course-body">

                {{-- Stats grid --}}
                <div class="course-stats-grid">
                    <div class="course-stat-box">
                        <div class="course-stat-val">{{ number_format($c->total_rows) }}</div>
                        <div class="course-stat-lbl">Total rows</div>
                    </div>
                    <div class="course-stat-box">
                        <div class="course-stat-val">{{ number_format($c->learner_count) }}</div>
                        <div class="course-stat-lbl">Distinct learners</div>
                    </div>
                    <div class="course-stat-box">
                        <div class="course-stat-val">{{ number_format($c->distinct_modules) }}</div>
                        <div class="course-stat-lbl">Distinct modules</div>
                    </div>
                    <div class="course-stat-box">
                        <div class="course-stat-val">{{ number_format($c->enrollment_count) }}</div>
                        <div class="course-stat-lbl">Distinct enrollments</div>
                    </div>
                    <div class="course-stat-box">
                        <div class="course-stat-val">{{ number_format($c->expected_rows) }}</div>
                        <div class="course-stat-lbl">Expected rows</div>
                    </div>
                    <div class="course-stat-box" style="{{ $hasMissing ? 'border-color:#fca5a5' : '' }}">
                        <div class="course-stat-val {{ $hasMissing ? 'warn' : '' }}">
                            {{ $hasMissing ? '−' . number_format($c->missing_rows) : '0' }}
                        </div>
                        <div class="course-stat-lbl">Missing rows</div>
                    </div>
                </div>

                {{-- Expected vs actual bar --}}
                @if($c->expected_rows > 0)
                    @php $fillPct = min(100, round(($c->total_rows / $c->expected_rows) * 100, 1)); @endphp
                    <div class="expected-row {{ $hasMissing ? 'expected-warn' : 'expected-ok' }}">
                        <strong>{{ $fillPct }}%</strong> of expected rows present
                        ({{ number_format($c->total_rows) }} / {{ number_format($c->expected_rows) }})
                    </div>
                @else
                    <div class="expected-row expected-unknown">
                        Expected row count unavailable (no module data linked yet)
                    </div>
                @endif

                {{-- Status breakdown for this course --}}
                @if($c->status_counts->isNotEmpty())
                    <div style="font-size:0.72rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.4rem">
                        Status breakdown
                    </div>
                    <table class="status-mini">
                        <tbody>
                            @foreach($c->status_counts as $sc)
                                @php
                                    $sl2 = strtolower($sc->completion_status ?? '');
                                    $bc2 = match(true) {
                                        str_contains($sl2, 'pass')     => 'badge-pass',
                                        str_contains($sl2, 'progress') => 'badge-progress',
                                        str_contains($sl2, 'fail')     => 'badge-failed',
                                        str_contains($sl2, 'not')      => 'badge-notstart',
                                        default                        => 'badge-other',
                                    };
                                    $pct2 = $c->total_rows > 0
                                        ? round(($sc->cnt / $c->total_rows) * 100, 1)
                                        : 0;
                                @endphp
                                <tr>
                                    <td style="width:160px">
                                        <span class="badge {{ $bc2 }}">{{ $sc->completion_status ?? 'null' }}</span>
                                    </td>
                                    <td style="width:80px">{{ number_format($sc->cnt) }}</td>
                                    <td style="color:#9ca3af;width:55px">{{ $pct2 }}%</td>
                                    <td>
                                        <div class="bar-wrap" style="width:80px">
                                            <div class="bar-fill" style="width:{{ $pct2 }}%;background:#4f6ef7"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

            </div>
        </div>
    </div>
@empty
    <p style="color:#9ca3af;font-style:italic">No courses found.</p>
@endforelse


{{-- ═══════════════════════════════════════════════════════
     5. LEARNER ROW DISTRIBUTION
═══════════════════════════════════════════════════════ --}}
<h2>Rows per Learner</h2>
<div class="kpi-strip" style="margin-bottom:0.75rem">
    <div class="kpi-box">
        <div class="kpi-val">{{ $learnerMin }}</div>
        <div class="kpi-lbl">Min rows (fewest)</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val">{{ $learnerAvg }}</div>
        <div class="kpi-lbl">Average rows</div>
    </div>
    <div class="kpi-box">
        <div class="kpi-val">{{ $learnerMax }}</div>
        <div class="kpi-lbl">Max rows (most)</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
    <div class="card">
        <table>
            <thead>
                <tr><th colspan="2">Top 10 learners (most rows)</th></tr>
                <tr><th>user_id</th><th>Rows</th></tr>
            </thead>
            <tbody>
                @foreach($topLearners as $l)
                    <tr>
                        <td class="mono">{{ $l->user_id }}</td>
                        <td><span class="badge badge-blue">{{ number_format($l->row_count) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card">
        <table>
            <thead>
                <tr><th colspan="2">Bottom 10 learners (fewest rows)</th></tr>
                <tr><th>user_id</th><th>Rows</th></tr>
            </thead>
            <tbody>
                @foreach($bottomLearners as $l)
                    <tr>
                        <td class="mono">{{ $l->user_id }}</td>
                        <td><span class="badge badge-red">{{ number_format($l->row_count) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ═══════════════════════════════════════════════════════
     6. DUPLICATE DETECTION
═══════════════════════════════════════════════════════ --}}
<h2>Duplicate Rows
    @if($duplicates->count() > 0)
        <span class="warn-chip" style="vertical-align:middle">{{ $duplicates->count() }} combo(s)</span>
    @else
        <span class="ok-chip" style="vertical-align:middle">None found</span>
    @endif
</h2>
<p style="font-size:0.78rem;color:#6b7280;margin-bottom:0.75rem">
    Groups where the same <code>(user_id, course_item_id, enrollment_id)</code> appears more than once — these are likely sync artifacts.
</p>

@if($duplicates->count() > 0)
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>user_id</th>
                    <th>course_item_id</th>
                    <th>enrollment_id</th>
                    <th style="width:80px">Dupes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($duplicates as $d)
                    <tr>
                        <td class="mono">{{ $d->user_id }}</td>
                        <td class="mono">{{ $d->course_item_id }}</td>
                        <td class="mono">{{ $d->enrollment_id }}</td>
                        <td><span class="badge badge-red">{{ $d->dupes }}×</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="card">
        <div class="empty-state">No duplicates detected. Each (user, course_item, enrollment) combination is unique.</div>
    </div>
@endif


{{-- ═══════════════════════════════════════════════════════
     7. DATA FRESHNESS
═══════════════════════════════════════════════════════ --}}
<h2>Data Freshness</h2>
<div class="card">
    <table>
        <thead>
            <tr><th>Field</th><th>Earliest</th><th>Latest</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>access_date <span style="color:#9ca3af;font-size:0.72rem">(learner activity)</span></td>
                <td class="mono">{{ $freshness->earliest_access ?? '—' }}</td>
                <td class="mono">{{ $freshness->latest_access ?? '—' }}</td>
            </tr>
            <tr>
                <td>created_at <span style="color:#9ca3af;font-size:0.72rem">(sync timestamp)</span></td>
                <td class="mono">{{ $freshness->earliest_sync ?? '—' }}</td>
                <td class="mono">{{ $freshness->latest_sync ?? '—' }}</td>
            </tr>
        </tbody>
    </table>
</div>

<p style="font-size:0.75rem;color:#9ca3af;margin-top:1.5rem;margin-bottom:2rem">
    All counts are live — refresh the page to recheck. &nbsp;·&nbsp;
    <a href="{{ route('dev.data-summary') }}">↺ Refresh</a>
    &nbsp;·&nbsp;
    <a href="{{ route('dev.course-structure') }}">← Course Structure</a>
</p>

<script>
function toggle(i) {
    const block = document.getElementById('cb-' + i);
    const body  = document.getElementById('cb-body-' + i);
    const open  = block.classList.toggle('open');
    body.style.display = open ? '' : 'none';
}
</script>

</body>
</html>
