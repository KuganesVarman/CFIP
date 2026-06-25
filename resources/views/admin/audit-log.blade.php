<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log | CFIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard-admin.css') }}">
    <script>if(localStorage.darkMode==='on')document.documentElement.classList.add('dark-mode')</script>
    <style>
        .al-filter-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .al-filter-row select,
        .al-filter-row input[type="date"] {
            padding: 0.5rem 0.8rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-card);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.85rem;
        }

        .al-filter-btn {
            padding: 0.5rem 1rem;
            background: #1a4fa8;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .al-clear-btn {
            padding: 0.5rem 0.8rem;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
        }

        .al-card {
            background: var(--bg-card);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .al-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.855rem;
        }

        .al-table thead tr {
            border-bottom: 1px solid var(--border);
        }

        .al-table th {
            padding: 0.85rem 1.2rem;
            text-align: left;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            white-space: nowrap;
            background: var(--bg-card);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .al-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
        }

        .al-table tbody tr:last-child { border-bottom: none; }
        .al-table tbody tr:hover { background: rgba(79, 110, 247, 0.04); }

        .al-table td {
            padding: 0.85rem 1.2rem;
            color: var(--text-primary);
            vertical-align: top;
        }

        .al-action-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .al-action-badge.green  { background: #d1fae5; color: #065f46; }
        .al-action-badge.red    { background: #fee2e2; color: #991b1b; }
        .al-action-badge.blue   { background: #dbeafe; color: #1e40af; }
        .al-action-badge.grey   { background: #f3f4f6; color: #4b5563; }
        .al-action-badge.orange { background: #fef3c7; color: #92400e; }

        .al-details {
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: var(--text-secondary);
            word-break: break-all;
            max-width: 280px;
        }

        .al-empty {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .al-pagination {
            padding: 1rem 1.2rem;
            border-top: 1px solid var(--border);
        }

        .al-pagination nav { display: flex; justify-content: flex-end; }
    </style>
</head>
<body>

@include('partials.sidebar')

<div class="main">

    <div class="topbar">
        <div class="page-title-wrap">
            <span class="page-title">Audit Log</span>
        </div>
        <div class="topbar-right">
            @php $user = Auth::user(); @endphp
            <div class="user-chip">
                <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <span>{{ $user->name }}</span>
            </div>
        </div>
    </div>

    <div class="content">

        {{-- Filter row --}}
        <form method="GET" action="{{ route('admin.audit-log') }}" class="al-filter-row">
            <select name="action">
                <option value="">All Actions</option>
                @foreach([
                    'User logged in',
                    'Failed login attempt',
                    'User logged out',
                    'User role changed',
                    'User deleted',
                    'Report generated',
                    'Password changed',
                    'Two-factor authentication disabled',
                    'Two-factor authentication passed',
                    'Failed 2FA attempt',
                ] as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                        {{ $action }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="from" value="{{ request('from') }}" title="From date">
            <input type="date" name="to"   value="{{ request('to') }}"   title="To date">

            <button type="submit" class="al-filter-btn">Apply</button>
            <a href="{{ route('admin.audit-log') }}" class="al-clear-btn">Clear</a>
        </form>

        {{-- Table --}}
        <div class="al-card">
            <table class="al-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $desc = $log->description;
                            $badgeClass = match(true) {
                                in_array($desc, ['User logged in', 'Password changed', 'Two-factor authentication passed']) => 'green',
                                in_array($desc, ['Failed login attempt', 'User deleted', 'Failed 2FA attempt'])              => 'red',
                                in_array($desc, ['Report generated'])                                                        => 'blue',
                                in_array($desc, ['User role changed'])                                                       => 'orange',
                                default                                                                                      => 'grey',
                            };

                            $props = $log->properties ?? collect();
                            $ip    = $props['ip'] ?? '—';

                            // Build a human-readable details string
                            $details = $props->except(['ip', 'user_agent'])->toArray();
                        @endphp
                        <tr>
                            <td style="white-space:nowrap;color:var(--text-secondary);font-size:0.82rem">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </td>
                            <td style="white-space:nowrap">
                                {{ $log->causer?->name ?? 'System' }}
                            </td>
                            <td>
                                <span class="al-action-badge {{ $badgeClass }}">{{ $desc }}</span>
                            </td>
                            <td style="white-space:nowrap;font-family:monospace;font-size:0.82rem">
                                {{ $ip }}
                            </td>
                            <td>
                                @if(!empty($details))
                                    <span class="al-details">{{ json_encode($details, JSON_UNESCAPED_SLASHES) }}</span>
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="al-empty">No audit log entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($logs->hasPages())
                <div class="al-pagination">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @endif
        </div>

    </div>{{-- /content --}}
</div>{{-- /main --}}

</body>
</html>
