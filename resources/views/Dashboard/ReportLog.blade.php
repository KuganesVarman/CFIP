<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Log | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <style>
        /* ── Search bar ───────────────────────────────────────── */
        .rl-search-wrap {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.55rem 1rem;
            max-width: 340px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        .rl-search-wrap svg {
            width: 16px; height: 16px;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        .rl-search-wrap input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 0.85rem;
            color: var(--text-primary);
            width: 100%;
            font-family: inherit;
        }

        .rl-search-wrap input::placeholder { color: var(--text-muted); }

        /* ── Table card ───────────────────────────────────────── */
        .rl-card {
            background: var(--bg-card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .rl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.855rem;
        }

        .rl-table thead tr {
            border-bottom: 1px solid var(--border);
        }

        .rl-table th {
            padding: 0.85rem 1.2rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
            background: var(--bg-card);
        }

        .rl-table th.sortable {
            cursor: pointer;
            user-select: none;
        }

        .rl-table th.sortable:hover { color: var(--text-primary); }

        .rl-sort-icon {
            display: inline-block;
            margin-left: 4px;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .rl-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }

        .rl-table tbody tr:last-child { border-bottom: none; }
        .rl-table tbody tr:hover { background: rgba(79, 110, 247, 0.04); }

        .rl-table td {
            padding: 0.85rem 1.2rem;
            color: var(--text-primary);
        }

        /* ── Format badges ────────────────────────────────────── */
        .fmt-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 5px;
        }
        .fmt-badge.pdf   { background: #fee2e1; color: #b91c1c; }
        .fmt-badge.excel { background: #dcfce7; color: #15803d; }
        .fmt-badge.other { background: #f3f4f6; color: #6b7280; }

        .rl-status { color: #15803d; font-weight: 600; font-size: 12px; }

        /* ── Summary strip ────────────────────────────────────── */
        .rl-summary {
            display: flex;
            gap: 12px;
            margin-bottom: 10px;
        }
        .rl-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
        }
        .rl-summary-pill.total { background: var(--cfip-blue-light); color: var(--cfip-blue); }
        .rl-summary-pill.pdf   { background: #fee2e1; color: #b91c1c; }
        .rl-summary-pill.excel { background: #dcfce7; color: #15803d; }

        .rl-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* ── Row count chip ───────────────────────────────────── */
        .rl-count {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            padding-left: 2px;
        }
    </style>
</head>
<body>

{{-- INVESTIGATION SYNC OVERLAY --}}
@include('partials.sync-loading')

@include('partials.sidebar')

<div class="main">

    {{-- TOPBAR --}}
    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">Report Log</span>
        </div>
        <div class="topbar-right">
            @include('partials.api-dot')
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="content">

        {{-- Summary strip --}}
        @php
            $totalCount = $logs->count();
            $pdfCount   = $logs->where('format', 'PDF')->count();
            $xlsCount   = $logs->whereIn('format', ['Excel', 'CSV'])->count();
        @endphp
        <div class="rl-summary">
            <span class="rl-summary-pill total">{{ $totalCount }} total</span>
            @if($pdfCount)
                <span class="rl-summary-pill pdf">{{ $pdfCount }} PDF</span>
            @endif
            @if($xlsCount)
                <span class="rl-summary-pill excel">{{ $xlsCount }} Excel / CSV</span>
            @endif
        </div>

        {{-- Search --}}
        <div class="rl-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Search report title" oninput="filterTable()">
        </div>

        {{-- Row count --}}
        <div class="rl-count" id="rowCount">
            {{ $totalCount }} {{ $totalCount === 1 ? 'report' : 'reports' }}
        </div>

        {{-- Table --}}
        <div class="rl-card">
            <table class="rl-table">
                <thead>
                    <tr>
                        <th>Report title</th>
                        <th style="width:110px">Format</th>
                        <th class="sortable" style="width:190px" onclick="toggleSort()" id="dateHeader">
                            Date &amp; Time <span class="rl-sort-icon" id="sortIcon">↓</span>
                        </th>
                        <th style="width:130px">Status</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse($logs as $log)
                    @php
                        $fmt      = strtolower($log->format ?? '');
                        $fmtClass = $fmt === 'pdf' ? 'pdf' : ($fmt === 'excel' || $fmt === 'csv' ? 'excel' : 'other');
                    @endphp
                    <tr data-title="{{ strtolower($log->title) }}" data-ts="{{ $log->created_at->timestamp }}">
                        <td>{{ $log->title }}</td>
                        <td><span class="fmt-badge {{ $fmtClass }}">{{ $log->format }}</span></td>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="rl-status">{{ $log->status }}</td>
                    </tr>
                    @empty
                    <tr id="emptyRow">
                        <td colspan="4" class="rl-empty">No reports have been generated yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>{{-- /content --}}
</div>{{-- /main --}}

<script>
let sortDesc = true;

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#tableBody tr[data-title]');
    let visible = 0;
    rows.forEach(r => {
        const match = !q || r.dataset.title.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('rowCount').textContent = visible + (visible === 1 ? ' report' : ' reports');
}

function toggleSort() {
    sortDesc = !sortDesc;
    document.getElementById('sortIcon').textContent = sortDesc ? '↓' : '↑';

    const tbody = document.getElementById('tableBody');
    const rows = [...tbody.querySelectorAll('tr[data-title]')];
    rows.sort((a, b) => {
        const tsA = parseInt(a.dataset.ts, 10);
        const tsB = parseInt(b.dataset.ts, 10);
        return sortDesc ? tsB - tsA : tsA - tsB;
    });
    rows.forEach(r => tbody.appendChild(r));
}
</script>

@include('partials.api-status')
</body>
</html>
