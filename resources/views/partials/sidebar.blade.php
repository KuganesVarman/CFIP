@php
    $user       = Auth::user();
    $role       = $user->role;
    $prefix     = ($role === 'A') ? 'admin' : 'pc';
    $onHome     = request()->routeIs($prefix . '.analytics.levels');
    $onAny      = request()->routeIs($prefix . '.analytics.domains')
               || request()->routeIs($prefix . '.analytics.modules');
    $onDomains  = request()->routeIs($prefix . '.analytics.domains');
    $onModules  = request()->routeIs($prefix . '.analytics.modules');
    $onStudents = request()->routeIs($prefix . '.students');
    $onReports  = request()->routeIs($prefix . '.reports');
    $onSettings = request()->routeIs('settings');
    $onUsers    = request()->routeIs('admin.users') && $role === 'A';
    $onAuditLog = request()->routeIs('admin.audit-log') && $role === 'A';

    $roleLabel  = match($role) {
        'A'  => 'Super Admin',
        'PC' => 'Program Coordinator',
        default => $role,
    };

    $nameParts = explode(' ', trim($user->name));
    $initials  = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
@endphp

{{-- Mobile hamburger (only visible on small screens via CSS) --}}
<button class="mobile-hamburger" id="mobileHamburger" aria-label="Toggle navigation">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="sidebar" id="mainSidebar">

    {{-- Logo --}}
    <div class="sidebar-logo">
        <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo" class="sidebar-brand-img">
    </div>

    <div class="nav-section">

        {{-- Collapse toggle at top of nav --}}
        <button class="sidebar-toggle" id="sidebarToggle" title="Collapse sidebar">
            <span class="toggle-label">Collapse</span>
            <svg id="toggleIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>

        {{-- OVERVIEW --}}
        <span class="sidebar-section-label">Overview</span>

        <a href="{{ route($prefix . '.analytics.levels') }}"
           class="nav-item{{ $onHome ? ' active' : '' }}"
           data-label="Home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            <span class="nav-label">Home</span>
        </a>

        {{-- ANALYTICS --}}
        <span class="sidebar-section-label">Analytics</span>

        <span class="nav-item nav-item-parent{{ $onAny ? ' active' : '' }}" data-label="Analytics">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span class="nav-label">Analytics</span>
            <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </span>
        <div class="nav-sub">
            <a href="{{ route($prefix . '.analytics.domains') }}"
               class="nav-item{{ $onDomains ? ' active' : '' }}"
               data-label="Domain">
                <span class="nav-label">Domain</span>
            </a>
            <a href="{{ route($prefix . '.analytics.modules') }}"
               class="nav-item{{ $onModules ? ' active' : '' }}"
               data-label="Module">
                <span class="nav-label">Module</span>
            </a>
            <span class="nav-item nav-item-disabled" data-label="Badges & Certificates">
                <span class="nav-label">Badges &amp; Certificates</span>
            </span>
        </div>

        {{-- MANAGEMENT --}}
        <span class="sidebar-section-label">Management</span>

        <a href="{{ route($prefix . '.students') }}"
           class="nav-item{{ $onStudents ? ' active' : '' }}"
           data-label="Student Progress">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <path d="M20 8v6M23 11h-6"/>
            </svg>
            <span class="nav-label">Student Progress</span>
        </a>

        {{-- REPORTS --}}
        <span class="sidebar-section-label">Reports</span>

        <a href="{{ route($prefix . '.reports') }}"
           class="nav-item{{ $onReports ? ' active' : '' }}"
           data-label="Report Log">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="nav-label">Report Log</span>
        </a>

        {{-- SYSTEM --}}
        <span class="sidebar-section-label">System</span>

        @if($role === 'A')
        <a href="{{ route('admin.users') }}"
           class="nav-item{{ $onUsers ? ' active' : '' }}"
           data-label="User Management">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                <path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>
            <span class="nav-label">User Management</span>
        </a>

        <a href="{{ route('admin.audit-log') }}"
           class="nav-item{{ $onAuditLog ? ' active' : '' }}"
           data-label="Audit Log">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M9 12h6M9 16h4"/>
            </svg>
            <span class="nav-label">Audit Log</span>
        </a>
        @endif

        <a href="{{ route('settings') }}"
           class="nav-item{{ $onSettings ? ' active' : '' }}"
           data-label="Settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            <span class="nav-label">Settings</span>
        </a>

    </div>{{-- /nav-section --}}

    {{-- Profile row --}}
    <div class="sidebar-profile">
        <div class="sidebar-profile-avatar">{{ $initials }}</div>
        <div class="sidebar-profile-info">
            <div class="sidebar-profile-name">{{ $user->name }}</div>
            <div class="sidebar-profile-role">{{ $roleLabel }}</div>
        </div>
    </div>

    {{-- Logout --}}
    <div class="logout-area">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn" data-label="Logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="nav-label">Logout</span>
            </button>
        </form>
    </div>

</div>

<script>
(function () {
    var sidebar   = document.getElementById('mainSidebar');
    var toggle    = document.getElementById('sidebarToggle');
    var icon      = document.getElementById('toggleIcon');
    var hamburger = document.getElementById('mobileHamburger');
    var backdrop  = document.getElementById('sidebarBackdrop');
    var COLLAPSED_KEY = 'cfip_sidebar_collapsed';

    function setCollapsed(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            icon.innerHTML = '<polyline points="9 18 15 12 9 6"/>';
        } else {
            sidebar.classList.remove('collapsed');
            icon.innerHTML = '<polyline points="15 18 9 12 15 6"/>';
        }
        localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0');
    }

    function openMobileSidebar() {
        sidebar.classList.add('mobile-open');
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Restore desktop collapsed state
    if (localStorage.getItem(COLLAPSED_KEY) === '1') {
        setCollapsed(true);
    }

    toggle.addEventListener('click', function () {
        setCollapsed(!sidebar.classList.contains('collapsed'));
    });

    hamburger.addEventListener('click', function () {
        if (sidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });

    backdrop.addEventListener('click', closeMobileSidebar);

    // Close on nav link tap (mobile UX)
    sidebar.querySelectorAll('a.nav-item').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });
})();
</script>
