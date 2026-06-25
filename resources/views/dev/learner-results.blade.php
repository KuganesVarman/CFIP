<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Results – CFIP Dev</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f3f4f6; color: #111827; padding: 2rem; }

        h1 { font-size: 1.4rem; font-weight: 700; color: #1e3a5f; margin-bottom: 0.25rem; }
        .sub { font-size: 0.82rem; color: #6b7280; margin-bottom: 1.5rem; }

        /* ── Filter form ──────────────────────────────────── */
        .filter-form {
            display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            padding: 1rem 1.25rem; margin-bottom: 1.5rem;
        }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.07em; color: #9ca3af; }
        .filter-group select {
            background: #111827; color: #fff; border: none; border-radius: 7px;
            padding: 7px 14px; font-size: 13px; font-weight: 500;
            cursor: pointer; font-family: inherit; min-width: 160px;
        }
        .filter-group select:focus { outline: none; }
        .filter-btn {
            background: #1a4fa8; color: #fff; border: none; border-radius: 7px;
            padding: 8px 20px; font-size: 13px; font-weight: 600;
            cursor: pointer; font-family: inherit; align-self: flex-end;
        }
        .filter-btn:hover { background: #163d84; }
        .clear-link { font-size: 12px; color: #9ca3af; align-self: flex-end; padding-bottom: 8px; cursor: pointer; text-decoration: none; }
        .clear-link:hover { color: #374151; }

        /* ── Search + stats bar ───────────────────────────── */
        .toolbar {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .search-input {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 7px;
            padding: 7px 12px; font-size: 13px; font-family: inherit;
            width: 220px; outline: none; color: #111827;
        }
        .search-input:focus { border-color: #6b7280; }
        .stat-tag {
            font-size: 11px; font-weight: 600; padding: 4px 10px;
            border-radius: 20px; background: #f3f4f6; color: #374151;
        }
        .stat-tag.passed      { background: #d1fae5; color: #065f46; }
        .stat-tag.failed      { background: #fee2e2; color: #991b1b; }
        .stat-tag.in-progress { background: #fef3c7; color: #92400e; }
        .stat-tag.not-started { background: #f3f4f6; color: #6b7280; }
        .export-btn {
            margin-left: auto; background: #f9fafb; border: 1px solid #e5e7eb;
            border-radius: 7px; padding: 6px 14px; font-size: 12px; font-weight: 600;
            cursor: pointer; font-family: inherit; color: #374151;
        }
        .export-btn:hover { background: #f3f4f6; }

        /* ── Table wrapper ────────────────────────────────── */
        .table-wrap {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            overflow: hidden;
        }
        .table-scroll { overflow-x: auto; }

        table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 12px; }

        /* Group header (course codes) */
        thead tr.grp th {
            background: #1a4fa8; color: #fff; font-size: 11px; font-weight: 700;
            text-align: center; padding: 7px 6px; letter-spacing: 0.05em;
            border-right: 2px solid #c7d7f5;
        }
        thead tr.grp th.hdr-blank { background: #f9fafb; border-right: 2px solid #e5e7eb; }

        /* Module header (QL1, QL2, MA…) */
        thead tr.mod th {
            background: #f9fafb; color: #6b7280; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            padding: 6px 4px; text-align: center; white-space: nowrap;
            border-bottom: 2px solid #e5e7eb; border-right: 1px solid #f0f0f0;
            min-width: 58px;
        }
        thead tr.mod th.last-grp { border-right: 2px solid #e5e7eb; }
        thead tr.mod th.hdr-name { text-align: left; min-width: 180px; padding-left: 12px; }
        thead tr.mod th.hdr-dept { text-align: left; min-width: 130px; padding-left: 12px; }

        /* Sticky columns */
        .sname { position: sticky; left: 0; z-index: 2; background: #fff; }
        .sdept { position: sticky; left: 180px; z-index: 2; background: #fff; border-right: 2px solid #e5e7eb !important; }
        thead .sname, thead .sdept { background: #f9fafb; z-index: 3; }
        tr:hover .sname, tr:hover .sdept { background: #f9fafb; }

        td {
            padding: 8px 4px; border-bottom: 1px solid #f3f4f6;
            border-right: 1px solid #f3f4f6; text-align: center; vertical-align: middle;
        }
        td.last-grp { border-right: 2px solid #e5e7eb; }
        td.sname { padding: 8px 12px; text-align: left; border-right: 1px solid #e5e7eb; }
        td.sdept { padding: 8px 12px; text-align: left; font-size: 11px; color: #6b7280; }
        tr:hover td { background: #f9fafb; }
        tr:hover .sname, tr:hover .sdept { background: #f9fafb; }
        tr[data-hidden="1"] { display: none; }

        .learner-name { font-weight: 600; color: #111827; font-size: 12px; }
        .learner-no-data { font-size: 10px; color: #d1d5db; }

        /* Status cells */
        .s-passed      { color: #065f46; font-weight: 700; font-size: 13px; }
        .s-failed      { color: #991b1b; font-weight: 700; font-size: 13px; }
        .s-in_progress { color: #92400e; font-weight: 700; font-size: 12px; }
        .s-not_started { color: #d1d5db; font-size: 13px; }

        .dup-tag {
            display: inline-block; background: #fef3c7; color: #92400e;
            border-radius: 3px; font-size: 9px; font-weight: 700;
            padding: 1px 3px; margin-left: 2px; vertical-align: top;
        }

        /* Footer pass-rate row */
        tfoot td {
            background: #f9fafb; font-size: 10px; font-weight: 700;
            border-top: 2px solid #e5e7eb; padding: 6px 4px;
        }
        tfoot td.sname, tfoot td.sdept { text-align: left; color: #6b7280; padding-left: 12px; }

        /* ── Empty / prompt state ─────────────────────────── */
        .empty {
            text-align: center; padding: 4rem 2rem;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
            color: #9ca3af; font-size: 0.9rem;
        }
        .empty strong { display: block; color: #374151; font-size: 1rem; margin-bottom: 6px; }
    </style>
</head>
<body>

<h1>🎓 Learner Results</h1>
<p class="sub">Quiz Lesson &amp; Module Assessment results per learner — filtered by cohort, domain, agency.</p>

{{-- ── FILTER FORM ──────────────────────────────────────────── --}}
<form method="GET" class="filter-form">
    <div class="filter-group">
        <label>Cohort *</label>
        <select name="cohort">
            <option value="">— select cohort —</option>
            @foreach($cohorts as $c)
                <option value="{{ $c->group_id }}" {{ $selectedCohort === $c->group_id ? 'selected' : '' }}>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label>Domain</label>
        <select name="domain_id">
            <option value="">All Domains</option>
            @foreach($domains as $d)
                <option value="{{ $d->id }}" {{ (int)$selectedDomainId === $d->id ? 'selected' : '' }}>
                    {{ $d->level_name }} — {{ $d->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filter-group">
        <label>Agency</label>
        <select name="agency">
            <option value="">All Agencies</option>
            @foreach($agencies as $a)
                <option value="{{ $a->department_id }}" {{ $selectedAgency === $a->department_id ? 'selected' : '' }}>
                    {{ $a->name }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="filter-btn">Show Results</button>
    <a href="{{ route('dev.learner-results') }}" class="clear-link">Clear</a>
</form>

{{-- ── RESULTS ──────────────────────────────────────────────── --}}
@if(!$selectedCohort)

    <div class="empty">
        <strong>Select a cohort above to get started.</strong>
        Optionally narrow by domain and agency.
    </div>

@elseif($learners->isEmpty())

    <div class="empty">
        <strong>No learners found.</strong>
        The selected cohort{{ $selectedAgency ? ' + agency' : '' }} has no learners.
    </div>

@elseif($courseGroups->isEmpty())

    <div class="empty">
        <strong>No quiz or assessment data found.</strong>
        These learners have no Quiz Lesson or Module Assessment records in
        {{ $selectedDomainId ? 'the selected domain' : 'any domain' }}.
    </div>

@else

    @php
        // Per-column totals
        $colPass = []; $colFail = []; $colIP = []; $colNS = [];
        foreach ($courseGroups as $cg) {
            foreach ($cg->modules as $mod) {
                $k = $cg->course_code . '|' . $mod;
                $colPass[$k] = $colFail[$k] = $colIP[$k] = $colNS[$k] = 0;
            }
        }
        $totalPass = $totalFail = $totalIP = $totalNS = 0;
        foreach ($learners as $lr) {
            foreach ($lr->cells as $k => $cell) {
                match($cell['status']) {
                    'passed'      => [$colPass[$k]++, $totalPass++],
                    'failed'      => [$colFail[$k]++, $totalFail++],
                    'in_progress' => [$colIP[$k]++,   $totalIP++],
                    default       => [$colNS[$k]++,   $totalNS++],
                };
            }
        }
        $totalCells = $totalPass + $totalFail + $totalIP + $totalNS;

        $abbr = fn(string $t): string => preg_match('/Quiz Lesson\s+(\d+)/i', $t, $m)
            ? 'QL' . $m[1]
            : (str_contains($t, 'Module Assessment') ? 'MA' : strtoupper(substr($t, 0, 4)));
    @endphp

    {{-- ── TOOLBAR ──────────────────────────────────────────── --}}
    <div class="toolbar">
        <input type="text" class="search-input" id="nameSearch" placeholder="Search name…" oninput="filterName(this.value)">
        <span class="stat-tag">{{ $totalLearners }} learners</span>
        <span class="stat-tag passed">✓ {{ $totalPass }} passed</span>
        <span class="stat-tag in-progress">● {{ $totalIP }} in progress</span>
        <span class="stat-tag failed">✗ {{ $totalFail }} failed</span>
        <span class="stat-tag not-started">— {{ $totalNS }} not started</span>
        <button class="export-btn" onclick="exportCsv()">⬇ Export CSV</button>
    </div>

    {{-- ── TABLE ────────────────────────────────────────────── --}}
    <div class="table-wrap">
        <div class="table-scroll">
            <table id="lr-table">
                <thead>
                    {{-- Row 1: course code groups --}}
                    <tr class="grp">
                        <th class="hdr-blank sname" colspan="1"></th>
                        <th class="hdr-blank sdept" colspan="1"></th>
                        @foreach($courseGroups as $cgIdx => $cg)
                            <th colspan="{{ $cg->modules->count() }}"
                                style="{{ $cgIdx < $courseGroups->count() - 1 ? 'border-right:2px solid #c7d7f5' : '' }}">
                                {{ $cg->course_code }}
                            </th>
                        @endforeach
                    </tr>

                    {{-- Row 2: module column headers --}}
                    <tr class="mod">
                        <th class="hdr-name sname">Student Name</th>
                        <th class="hdr-dept sdept">Agency</th>
                        @foreach($courseGroups as $cg)
                            @foreach($cg->modules as $mi => $mod)
                                @php
                                    $isLast = $mi === $cg->modules->count() - 1;
                                    $k = $cg->course_code . '|' . $mod;
                                    $pct = $totalLearners > 0
                                        ? round($colPass[$k] / $totalLearners * 100) : 0;
                                @endphp
                                <th class="{{ $isLast ? 'last-grp' : '' }}" title="{{ $mod }}">
                                    {{ $abbr($mod) }}
                                    <div style="font-size:9px;color:#1d9e75;font-weight:400;margin-top:1px">{{ $pct }}%</div>
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $lr)
                        @php
                            $hasRedundancy = collect($lr->cells)->contains(fn($c) => $c['attempts'] > 1);
                        @endphp
                        <tr data-name="{{ strtolower($lr->full_name) }}">
                            <td class="sname">
                                <div class="learner-name">{{ $lr->full_name }}</div>
                                @if($hasRedundancy)
                                    <div style="font-size:9px;color:#f59e0b">⚠ multiple attempts</div>
                                @endif
                            </td>
                            <td class="sdept">{{ $lr->dept_name }}</td>

                            @foreach($courseGroups as $cg)
                                @foreach($cg->modules as $mi => $mod)
                                    @php
                                        $isLast = $mi === $cg->modules->count() - 1;
                                        $k      = $cg->course_code . '|' . $mod;
                                        $cell   = $lr->cells[$k] ?? ['status' => 'not_started', 'attempts' => 0];
                                        $st     = $cell['status'];
                                        $att    = $cell['attempts'];
                                    @endphp
                                    <td class="{{ $isLast ? 'last-grp' : '' }}">
                                        <span class="s-{{ $st }}" title="{{ ucfirst(str_replace('_',' ',$st)) }}">
                                            {{ $st === 'passed' ? '✓' : ($st === 'failed' ? '✗' : ($st === 'in_progress' ? '●' : '—')) }}
                                        </span>
                                        @if($att > 1)
                                            <span class="dup-tag" title="{{ $att }} attempts">×{{ $att }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <td class="sname">Pass rate</td>
                        <td class="sdept"></td>
                        @foreach($courseGroups as $cg)
                            @foreach($cg->modules as $mi => $mod)
                                @php
                                    $isLast = $mi === $cg->modules->count() - 1;
                                    $k      = $cg->course_code . '|' . $mod;
                                    $pct    = $totalLearners > 0
                                        ? round($colPass[$k] / $totalLearners * 100) : 0;
                                    $color  = $pct >= 50 ? '#065f46' : ($pct >= 30 ? '#92400e' : '#991b1b');
                                @endphp
                                <td class="{{ $isLast ? 'last-grp' : '' }}"
                                    style="color:{{ $color }}">{{ $pct }}%</td>
                            @endforeach
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

@endif

<script>
function filterName(q) {
    q = q.toLowerCase().trim();
    let shown = 0;
    document.querySelectorAll('#lr-table tbody tr').forEach(tr => {
        const match = !q || tr.dataset.name.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) shown++;
    });
}

function exportCsv() {
    const table = document.getElementById('lr-table');
    if (!table) return;
    const rows = [];
    table.querySelectorAll('thead tr').forEach(tr => {
        const cells = [];
        tr.querySelectorAll('th').forEach(th => {
            const span = parseInt(th.getAttribute('colspan') || '1');
            cells.push((th.innerText || '').replace(/\n/g, ' ').trim());
            for (let i = 1; i < span; i++) cells.push('');
        });
        rows.push(cells);
    });
    table.querySelectorAll('tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        const cells = [];
        tr.querySelectorAll('td').forEach(td => {
            const span = td.querySelector('.s-passed, .s-failed, .s-in_progress, .s-not_started');
            const tag  = td.querySelector('.dup-tag');
            let val = span ? span.getAttribute('title') ?? span.innerText.trim() : td.innerText.trim();
            if (tag) val += ' ' + tag.innerText.trim();
            cells.push('"' + val.replace(/"/g, '""') + '"');
        });
        rows.push(cells);
    });
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = Object.assign(document.createElement('a'), { href: url, download: 'learner_results.csv' });
    a.click();
    URL.revokeObjectURL(url);
}
</script>

</body>
</html>
