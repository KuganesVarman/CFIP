@php
    $onDashboard  = request()->routeIs('learner.dashboard');
    $onModules    = request()->routeIs('learner.modules');
    $onBadges     = request()->routeIs('learner.badges');
    $onSettings   = request()->routeIs('settings');
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
    <div class="sidebar-logo">
        <img src="{{ asset('image/cfip-logo.png') }}" alt="CFIP Logo" class="sidebar-brand-img">
    </div>

    <div class="nav-section">

        <span class="sidebar-section-label">Overview</span>

        <a href="{{ route('learner.dashboard') }}"
           class="nav-item{{ $onDashboard ? ' active' : '' }}"
           data-label="My Dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            <span class="nav-label">My Dashboard</span>
        </a>

        <span class="sidebar-section-label">My Learning</span>

        <a href="{{ route('learner.modules') }}"
           class="nav-item{{ $onModules ? ' active' : '' }}"
           data-label="My Modules">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
            <span class="nav-label">My Modules</span>
        </a>

        <a href="{{ route('learner.badges') }}"
           class="nav-item{{ $onBadges ? ' active' : '' }}"
           data-label="My Achievements">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="6"/>
                <path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
            <span class="nav-label">My Achievements</span>
        </a>

        <span class="sidebar-section-label">Account</span>

        <a href="{{ route('settings') }}"
           class="nav-item{{ $onSettings ? ' active' : '' }}"
           data-label="Settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            <span class="nav-label">Settings</span>
        </a>

    </div>

    <div class="logout-area">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
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
    var hamburger = document.getElementById('mobileHamburger');
    var backdrop  = document.getElementById('sidebarBackdrop');

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

    hamburger.addEventListener('click', function () {
        if (sidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });

    backdrop.addEventListener('click', closeMobileSidebar);

    sidebar.querySelectorAll('a.nav-item').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });
})();
</script>
