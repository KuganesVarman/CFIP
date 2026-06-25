@extends('layouts.learner')

@section('title', 'My Dashboard')
@section('page-title', 'My Dashboard')

@push('topbar-actions')
@php $lessonsActive = $includeLessons ?? false; @endphp
<style>
.lesson-toggle-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;font-size:12px;font-weight:600;font-family:inherit;border-radius:6px;border:1.5px solid var(--border,#e5e7eb);background:var(--bg-card,#fff);color:var(--text-secondary,#6b7280);cursor:pointer;transition:all .15s ease;white-space:nowrap}
.lesson-toggle-btn:hover{border-color:#4f6ef7;color:#4f6ef7}
.lesson-toggle-btn.active{background:#4f6ef7;border-color:#4f6ef7;color:#fff}
</style>
<div style="display:flex;align-items:center;margin-left:auto;margin-right:12px">
    <button type="button" class="lesson-toggle-btn{{ $lessonsActive ? ' active' : '' }}" onclick="toggleLessons()" title="{{ $lessonsActive ? 'Click to exclude lesson results' : 'Click to include lesson results' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        Lessons {{ $lessonsActive ? 'ON' : 'OFF' }}
    </button>
</div>
<script>
function toggleLessons(){var u=new URL(window.location.href);u.searchParams.has('include_lessons')?u.searchParams.delete('include_lessons'):u.searchParams.set('include_lessons','1');window.location.href=u.toString();}
</script>
@endpush

@section('content')

@if (!$hasData)
    <div class="ld-card" style="text-align:center;padding:48px 24px;">
        <svg style="width:48px;height:48px;color:#d1d5db;margin:0 auto 14px;display:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
        <p style="font-size:14px;font-weight:600;color:#374151;margin-bottom:6px">No learning data yet</p>
        <p style="font-size:13px;color:#9ca3af">Your learning data will appear here once you start your first module in iSpring.</p>
    </div>
@else

{{-- ──── SECTION 1: HERO CARD ──────────────────────────────── --}}
<div class="ld-hero">

    {{-- LEFT: Progress ring --}}
    <div class="ld-hero-left">
        @php
            $circ   = 2 * M_PI * 68;
            $filled = round(($overallProgress / 100) * $circ, 2);
        @endphp
        <svg viewBox="0 0 160 160" width="160" height="160">
            {{-- Track --}}
            <circle cx="80" cy="80" r="68" fill="none" stroke="#e5e7eb" stroke-width="12"/>
            {{-- Progress arc --}}
            <circle cx="80" cy="80" r="68" fill="none"
                    stroke="{{ $statusColor }}"
                    stroke-width="12"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $filled }} {{ $circ }}"
                    stroke-dashoffset="-106.8"
                    transform="rotate(-90 80 80)"/>
            {{-- Centre text --}}
            <text x="80" y="76" text-anchor="middle" font-size="22" font-weight="700" fill="#111827" font-family="Poppins, sans-serif">{{ $overallProgress }}%</text>
            <text x="80" y="94" text-anchor="middle" font-size="11" fill="#9ca3af" font-family="Poppins, sans-serif">overall</text>
        </svg>

        {{-- Status badge --}}
        <span class="ld-status-badge"
              style="background:{{ $statusColor }}22; color:{{ $statusColor }}">
            <span style="width:6px;height:6px;border-radius:50%;background:{{ $statusColor }};display:inline-block"></span>
            {{ $status }}
        </span>
    </div>

    {{-- RIGHT: Greeting + stats + next module --}}
    <div class="ld-hero-right">
        <div class="ld-greeting">Welcome back,</div>
        <div class="ld-name">{{ auth()->user()->name }}</div>
        @if ($departmentName)
            <div class="ld-department">{{ $departmentName }} · Learner</div>
        @else
            <div class="ld-department">CFIP Entry Level Programme · Learner</div>
        @endif

        <div class="ld-stats">
            <div class="ld-stat-row">
                <div class="ld-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span class="ld-stat-label">Modules Completed</span>
                <span class="ld-stat-value" style="color:#1a4fa8">{{ $modulesCompleted }} / {{ $totalModules }}</span>
            </div>
            <div class="ld-stat-row">
                <div class="ld-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </div>
                <span class="ld-stat-label">Average Score</span>
                <span class="ld-stat-value" style="color:{{ $statusColor }}">{{ $overallProgress }}%</span>
            </div>
            <div class="ld-stat-row">
                <div class="ld-stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <span class="ld-stat-label">Last Active</span>
                <span class="ld-stat-value" style="color:#374151">{{ $lastActive ?? 'Never' }}</span>
            </div>
        </div>

        <hr class="ld-divider">

        <span class="ld-next-label">Next Recommended Activity</span>
        @if ($nextModule)
            <div class="ld-next-title">{{ $nextModule->module_title }}</div>
            <a href="#" class="ld-continue-btn" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
                Continue in iSpring
            </a>
        @else
            <div class="ld-all-done">🎉 You have completed all available modules!</div>
        @endif
    </div>
</div>

{{-- ──── SECTION 2: DOMAIN CARDS ─────────────────────────── --}}
<div class="ld-domain-grid">
    @foreach ($domainBreakdown as $domain)
    <div class="ld-domain-card">
        <div class="ld-domain-header">
            <span class="ld-domain-name">{{ $domain->name }}</span>
            <span class="ld-domain-pct" style="color:{{ $domain->status_color }}">{{ $domain->avg_progress }}%</span>
        </div>
        <div class="ld-domain-bar-bg">
            <div class="ld-domain-bar-fill"
                 style="width:{{ min(100, $domain->avg_progress) }}%; background:{{ $domain->status_color }}"></div>
        </div>
        <div class="ld-domain-chips">
            <span class="ld-chip" style="color:#1d9e75">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:10px;height:10px"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $domain->count_passed }}
            </span>
            <span class="ld-chip" style="color:#9ca3af">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px"><circle cx="12" cy="12" r="10"/></svg>
                {{ $domain->count_not_started }}
            </span>
        </div>
        <a href="{{ route('learner.modules', ['domain' => $domain->slug]) }}" class="ld-domain-link">View modules →</a>
    </div>
    @endforeach
</div>

{{-- ──── SECTION 3: BOTTOM ROW ──────────────────────────── --}}
<div class="ld-bottom-row">

    {{-- Recent Activity --}}
    <div class="ld-card">
        <div class="ld-card-title">My Recent Activity</div>
        @if ($recentActivity->isEmpty())
            <div class="ld-empty-state">No completed activities yet. Start your first module!</div>
        @else
            <div class="ld-activity-list">
                @foreach ($recentActivity as $item)
                <div class="ld-activity-row">
                    <div class="ld-activity-dot"></div>
                    <div class="ld-activity-body">
                        <div class="ld-activity-title" title="{{ $item->module_title }}">{{ $item->module_title }}</div>
                        <div class="ld-activity-time">{{ \Carbon\Carbon::parse($item->completion_date)->diffForHumans() }}</div>
                    </div>
                    <div class="ld-activity-score">{{ $item->progress }}%</div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Programme Overview --}}
    <div class="ld-card">
        <div class="ld-card-title">Programme Overview</div>

        @php
            $safeTotalMods = max(1, $totalModules);
        @endphp

        <div class="ld-overview-row">
            <div class="ld-overview-dot" style="background:#1d9e75"></div>
            <div class="ld-overview-label">Completed</div>
            <div class="ld-overview-count">{{ $passedCount }}</div>
            <span class="ld-overview-pill" style="background:#d1fae5;color:#065f46">
                {{ $totalModules > 0 ? round(($passedCount / $safeTotalMods) * 100) : 0 }}%
            </span>
        </div>
        <div class="ld-overview-row">
            <div class="ld-overview-dot" style="background:#f59e0b"></div>
            <div class="ld-overview-label">In Progress</div>
            <div class="ld-overview-count">{{ $inProgressCount }}</div>
            <span class="ld-overview-pill" style="background:#fef3c7;color:#92400e">
                {{ $totalModules > 0 ? round(($inProgressCount / $safeTotalMods) * 100) : 0 }}%
            </span>
        </div>
        <div class="ld-overview-row">
            <div class="ld-overview-dot" style="background:#9ca3af"></div>
            <div class="ld-overview-label">Not Started</div>
            <div class="ld-overview-count">{{ $notStartedCount }}</div>
            <span class="ld-overview-pill" style="background:#f3f4f6;color:#6b7280">
                {{ $totalModules > 0 ? round(($notStartedCount / $safeTotalMods) * 100) : 0 }}%
            </span>
        </div>
        @if ($failedCount > 0)
        <div class="ld-overview-row">
            <div class="ld-overview-dot" style="background:#e24b4a"></div>
            <div class="ld-overview-label">Failed</div>
            <div class="ld-overview-count">{{ $failedCount }}</div>
            <span class="ld-overview-pill" style="background:#fee2e2;color:#991b1b">
                {{ $totalModules > 0 ? round(($failedCount / $safeTotalMods) * 100) : 0 }}%
            </span>
        </div>
        @endif

        {{-- Stacked progress bar --}}
        @if ($totalModules > 0)
        <div class="ld-stacked-bar">
            @if ($passedCount > 0)
            <div class="ld-stacked-seg" style="width:{{ ($passedCount / $safeTotalMods) * 100 }}%;background:#1d9e75"></div>
            @endif
            @if ($inProgressCount > 0)
            <div class="ld-stacked-seg" style="width:{{ ($inProgressCount / $safeTotalMods) * 100 }}%;background:#f59e0b"></div>
            @endif
            @if ($notStartedCount > 0)
            <div class="ld-stacked-seg" style="width:{{ ($notStartedCount / $safeTotalMods) * 100 }}%;background:#e5e7eb"></div>
            @endif
            @if ($failedCount > 0)
            <div class="ld-stacked-seg" style="width:{{ ($failedCount / $safeTotalMods) * 100 }}%;background:#e24b4a"></div>
            @endif
        </div>
        @endif
    </div>

</div>

@endif {{-- /hasData --}}

@endsection
