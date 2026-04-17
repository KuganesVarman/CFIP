<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - CFIP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            height: 100vh;
            background: #f5f7fa;
        }

        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, #8b9dc3 0%, #6b7fa3 100%);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
        }

        .logo {
            padding: 0 24px 32px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .nav-menu {
            flex: 1;
            padding: 0 16px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 4px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            text-decoration: none;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-item.dropdown-toggle::after {
            content: '▼';
            position: absolute;
            right: 16px;
            font-size: 10px;
            transition: transform 0.2s;
        }

        .nav-item.dropdown-toggle.open::after {
            transform: rotate(-180deg);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            opacity: 0.9;
        }

        .dropdown-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            padding-left: 32px;
        }

        .dropdown-menu.show {
            max-height: 300px;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin-bottom: 2px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            text-decoration: none;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .logout-form {
            width: 100%;
        }

        .logout {
            padding: 0 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            color: #f0ad4e;
            font-size: 14px;
            font-weight: 500;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
        }

        .logout:hover {
            color: #ec971f;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .header {
            background: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .welcome-text {
            font-size: 20px;
            color: #1f2937;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            position: relative;
        }

        .icon-btn:hover {
            background: #f3f4f6;
        }

        .icon-btn.refreshing {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .user-profile:hover {
            background: #f3f4f6;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #6b7fa3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-name {
            font-size: 14px;
            color: #1f2937;
        }

        .content-area {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .content-area h2 {
            margin-bottom: 24px;
            color: #1f2937;
            font-size: 24px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
            margin-top: 32px;
        }

        .data-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            margin-bottom: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f9fafb;
        }

        th {
            text-align: left;
            padding: 16px;
            font-weight: 600;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 16px;
            font-size: 14px;
            color: #6b7280;
            border-bottom: 1px solid #f3f4f6;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            background: white;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 14px 20px;
        }

        .cohort-button {
            padding: 6px 14px;
            background: #6b7fa3;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-size: 13px;
            margin-right: 6px;
        }

        .cohort-button:hover {
            background: #5a6c8a;
        }

        svg {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div style="width: 40px; height: 40px; margin: 0 auto 16px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#6b7fa3" stroke-width="2" class="icon-btn refreshing">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"></path>
                </svg>
            </div>
            <div>Refreshing data...</div>
        </div>
    </div>

    <div class="sidebar">
        <div class="logo">
            <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo">
        </div>

        <div class="nav-menu">
            <div class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                </svg>
                Home
            </div>

            <div class="nav-item dropdown-toggle" onclick="toggleDropdown(this)">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Analytics
            </div>

            <div class="dropdown-menu" id="analyticsDropdown">
                <a href="#" class="dropdown-item">Level</a>
                <a href="#" class="dropdown-item">Domain</a>
                <a href="#" class="dropdown-item">Module</a>
                <a href="#" class="dropdown-item">Badges & Certificates</a>
            </div>

            <div class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <path d="M20 8v6M23 11h-6"></path>
                </svg>
                Student Progress
            </div>

            <div class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Report Log
            </div>

            <div class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"></path>
                </svg>
                Setting
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="logout">
                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </button>
        </form>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome-text">Welcome Back, {{ $user->name }}</div>

            <div class="header-actions">
                <button class="icon-btn" id="refreshBtn" onclick="refreshData()" title="Refresh Data">
                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"></path>
                    </svg>
                </button>

                <div class="status-indicator"></div>

                <div class="user-profile">
                    <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    <span class="user-name">{{ $user->name }}</span>
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
        </div>

        <div class="content-area">
    <h2>Dashboard Overview</h2>

    {{-- KPI CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Enrollment</div>
            <div class="stat-value">{{ $totalEnrollment }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Completion Rate</div>
            <div class="stat-value">{{ number_format($completionRate, 2) }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">In Progress Learners</div>
            <div class="stat-value">{{ $inProgress }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Not Started Learners</div>
            <div class="stat-value">{{ $notStarted }}</div>
        </div>
    </div>

    {{-- FILTERS --}}
    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:24px;">

        {{-- Cohort Dropdown --}}
        <div class="filter-card">
            <div style="font-size:14px; font-weight:600; margin-bottom:6px;">Cohort:</div>
            <select style="padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; min-width:200px;">
                <option value="1">Cohort 1</option>
                <option value="2">Cohort 2</option>
                <option value="3">Cohort 3</option>
            </select>
        </div>

        {{-- Agencies --}}
        <div class="filter-card">
            <div style="font-size:14px; font-weight:600; margin-bottom:6px;">Agencies:</div>
            <select style="padding:6px 10px; border-radius:6px; border:1px solid #d1d5db; min-width:220px;">
                @foreach($agencies as $agency)
                    <option value="{{ $agency }}">{{ $agency }}</option>
                @endforeach
            </select>
        </div>

    </div>

    {{-- CHARTS --}}
    <div style="display:flex; gap:24px; flex-wrap:wrap; margin-top:10px;">
        <div style="background:white; border-radius:14px; border:1px solid #e5e7eb; padding:24px; min-width:520px; flex:1;">
            <h5 style="margin-bottom:16px; font-size:17px;">Foundation Module Progress</h5>
            <canvas id="fdBarChart" width="480" height="260"></canvas>
        </div>

        <div style="background:white; border-radius:14px; border:1px solid #e5e7eb; padding:24px; min-width:300px;">
            <h5 style="margin-bottom:16px; font-size:17px;">Foundation Module Enrollment</h5>
            <canvas id="fdPieChart" width="240" height="240"></canvas>
        </div>
    </div>
</div>

    </div>

    <script>
        // Toggle dropdown menu
        function toggleDropdown(element) {
            const dropdown = document.getElementById('analyticsDropdown');
            element.classList.toggle('open');
            dropdown.classList.toggle('show');
        }

        // Refresh data function with AJAX (kept from your original code)
        async function refreshData() {
            const refreshBtn = document.getElementById('refreshBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');

            refreshBtn.classList.add('refreshing');
            loadingOverlay.classList.add('active');

            try {
                const response = await fetch('{{ route("api.refresh") }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to refresh data. Please try again.');
                }
            } catch (error) {
                console.error('Refresh error:', error);
                alert('Network error. Please check your connection.');
            } finally {
                refreshBtn.classList.remove('refreshing');
                loadingOverlay.classList.remove('active');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Ensure fallback values exist
    const barData = {!! json_encode($barChart ?? [
    'FD01' => ['pass' => 0, 'progress' => 0, 'failed' => 0],
    'FD02' => ['pass' => 0, 'progress' => 0, 'failed' => 0],
    'FD03' => ['pass' => 0, 'progress' => 0, 'failed' => 0],
]) !!};

    const fdEnrollment = @json($fdEnrollment ?? [
        'FD01' => 0, 'FD02' => 0, 'FD03' => 0
    ]);

    // BAR CHART
    new Chart(document.getElementById('fdBarChart'), {
        type: 'bar',
        data: {
            labels: ['FD01', 'FD02', 'FD03'],
            datasets: [
                {
                    label: 'Pass',
                    data: [barData.FD01.pass, barData.FD02.pass, barData.FD03.pass],
                    backgroundColor: '#52b788'
                },
                {
                    label: 'In Progress',
                    data: [barData.FD01.progress, barData.FD02.progress, barData.FD03.progress],
                    backgroundColor: '#4895ef'
                },
                {
                    label: 'Failed / Not Started',
                    data: [barData.FD01.failed, barData.FD02.failed, barData.FD03.failed],
                    backgroundColor: '#f28482'
                }
            ]
        },
        options: { responsive: false }
    });

    // PIE CHART
    new Chart(document.getElementById('fdPieChart'), {
        type: 'pie',
        data: {
            labels: ['FD01', 'FD02', 'FD03'],
            datasets: [{
                data: [
                    fdEnrollment.FD01,
                    fdEnrollment.FD02,
                    fdEnrollment.FD03
                ],
                backgroundColor: ['#4895ef','#f6bd60','#7e6bc4']
            }]
        },
        options: { responsive: false }
    });
</script>
</body>
</html>
