@extends('layouts.learner')

@section('title', 'My Modules')
@section('page-title', 'My Modules')

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

{{-- ──── DOMAIN FILTER TABS ─────────────────────────────── --}}
<div class="ld-filter-tabs">
    <a href="{{ route('learner.modules') }}"
       class="ld-tab{{ $selectedDomain === 'all' ? ' active' : '' }}">All Domains</a>
    @foreach ($allDomains as $d)
    <a href="{{ route('learner.modules', ['domain' => str_replace('_', '-', $d->code)]) }}"
       class="ld-tab{{ $selectedDomain === str_replace('_', '-', $d->code) ? ' active' : '' }}">
        {{ $d->name }}
    </a>
    @endforeach
</div>

{{-- ──── ACCORDION LIST ─────────────────────────────────── --}}
@if ($domainModules->isEmpty())
    <div class="ld-card" style="text-align:center;padding:48px 24px;">
        <p style="font-size:14px;color:#9ca3af">No modules found for this domain yet.</p>
    </div>
@else
    @foreach ($domainModules as $index => $domain)
    <div class="ld-accordion{{ $index === 0 ? ' open' : '' }}" id="accordion-{{ $domain->code }}">

        {{-- HEADER --}}
        <div class="ld-accordion-header" onclick="toggleAccordion('accordion-{{ $domain->code }}')">
            @php
                $domainColors = [
                    'foundation'   => '#1a4fa8',
                    'legal_ethics' => '#22c7b8',
                    'crime_inv'    => '#f7b84f',
                    'soft_skills'  => '#7f77dd',
                    'inv_techniques'=> '#d85a30',
                ];
                $dotColor = $domainColors[$domain->code] ?? '#9ca3af';
            @endphp
            <div class="ld-accordion-dot" style="background:{{ $dotColor }}"></div>
            <span class="ld-accordion-name">{{ $domain->name }}</span>
            <span class="ld-accordion-pct">{{ $domain->avg_progress }}% avg</span>
            <span style="font-size:11px;color:#9ca3af;margin-right:8px">
                {{ $domain->total_passed }} / {{ $domain->total_modules }} passed
            </span>
            <svg class="ld-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>

        {{-- BODY --}}
        <div class="ld-accordion-body">
            @if (empty($domain->modules))
                <div style="padding:20px;text-align:center;color:#9ca3af;font-size:12px">
                    No quiz or assessment modules found for this domain.
                </div>
            @else
                <table class="ld-module-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Last Accessed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($domain->modules as $mod)
                        <tr>
                            {{-- MODULE TITLE --}}
                            <td style="max-width:280px">
                                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;max-width:280px"
                                      title="{{ $mod->module_title }}">
                                    {{ $mod->module_title }}
                                </span>
                                <span style="font-size:10px;color:#9ca3af">{{ $mod->course_code }}</span>
                            </td>

                            {{-- TYPE PILL --}}
                            <td>
                                <span class="ld-type-pill {{ $mod->type === 'Assessment' ? 'ld-type-assessment' : 'ld-type-quiz' }}">
                                    {{ $mod->type }}
                                </span>
                            </td>

                            {{-- STATUS PILL --}}
                            <td>
                                @php
                                    $statusLabels = [
                                        'passed'      => 'Passed',
                                        'in_progress' => 'In Progress',
                                        'failed'      => 'Failed',
                                        'not_started' => 'Not Started',
                                    ];
                                @endphp
                                <span class="ld-status-pill ld-status-{{ $mod->effective_status }}">
                                    <span class="ld-status-pill-dot"></span>
                                    {{ $statusLabels[$mod->effective_status] ?? 'Not Started' }}
                                </span>
                            </td>

                            {{-- SCORE --}}
                            <td>
                                @if ($mod->progress >= 70)
                                    <span style="font-weight:700;color:#1d9e75">{{ $mod->progress }}%</span>
                                @elseif ($mod->progress >= 50)
                                    <span style="font-weight:700;color:#f59e0b">{{ $mod->progress }}%</span>
                                @elseif ($mod->progress > 0)
                                    <span style="font-weight:700;color:#e24b4a">{{ $mod->progress }}%</span>
                                @else
                                    <span style="color:#9ca3af">—</span>
                                @endif
                            </td>

                            {{-- LAST ACCESSED --}}
                            <td style="font-size:11px;color:#9ca3af">
                                @if ($mod->access_date)
                                    {{ \Carbon\Carbon::parse($mod->access_date)->format('d M Y') }}
                                @else
                                    Not yet
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="ld-accordion-footer">
                    {{ $domain->total_passed }} of {{ $domain->total_modules }} modules completed in {{ $domain->name }}
                </div>
            @endif
        </div>

    </div>
    @endforeach
@endif

@endsection

@push('scripts')
<script>
function toggleAccordion(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('open');
}
</script>
@endpush
