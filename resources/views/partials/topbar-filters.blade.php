@php
    $role          = Auth::user()->role;
    $groups        = \App\Models\Group::orderBy('name')->get();
    $depts         = ($role === 'A') ? \App\Models\Department::orderBy('name')->get() : collect();
    $selGrp        = request('cohort');
    $selDept       = request('agency');
    $lessonsActive = request()->boolean('include_lessons');
@endphp

<div class="topbar-filters">
    <select class="topbar-filter-select" name="cohort" onchange="applyTopbarFilter(this)">
        <option value="">All Cohorts</option>
        @foreach($groups as $group)
            <option value="{{ $group->group_id }}" {{ $selGrp == $group->group_id ? 'selected' : '' }}>
                {{ $group->name }}
            </option>
        @endforeach
    </select>

    @if($role === 'A')
    <select class="topbar-filter-select" name="agency" onchange="applyTopbarFilter(this)">
        <option value="">All Agencies</option>
        @foreach($depts as $dept)
            <option value="{{ $dept->department_id }}" {{ $selDept == $dept->department_id ? 'selected' : '' }}>
                {{ $dept->name }}
            </option>
        @endforeach
    </select>
    @endif

    <button
        type="button"
        class="lesson-toggle-btn{{ $lessonsActive ? ' active' : '' }}"
        onclick="toggleLessons()"
        title="{{ $lessonsActive ? 'Click to exclude lesson results' : 'Click to include lesson results' }}"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;flex-shrink:0">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
        Lessons {{ $lessonsActive ? 'ON' : 'OFF' }}
    </button>
</div>

<style>
.lesson-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    border-radius: 6px;
    border: 1.5px solid var(--border, #e5e7eb);
    background: var(--bg-card, #fff);
    color: var(--text-secondary, #6b7280);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.lesson-toggle-btn:hover {
    border-color: #4f6ef7;
    color: #4f6ef7;
}
.lesson-toggle-btn.active {
    background: #4f6ef7;
    border-color: #4f6ef7;
    color: #fff;
}
</style>

<script>
function applyTopbarFilter(el) {
    var url = new URL(window.location.href);
    if (el.value) {
        url.searchParams.set(el.name, el.value);
    } else {
        url.searchParams.delete(el.name);
    }
    window.location.href = url.toString();
}

function toggleLessons() {
    var url = new URL(window.location.href);
    if (url.searchParams.has('include_lessons')) {
        url.searchParams.delete('include_lessons');
    } else {
        url.searchParams.set('include_lessons', '1');
    }
    window.location.href = url.toString();
}
</script>
