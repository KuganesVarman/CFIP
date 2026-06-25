<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Structure – CFIP Dev</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            color: #111827;
            padding: 2rem;
        }

        h1 { font-size: 1.4rem; font-weight: 700; color: #1e3a5f; margin-bottom: 0.25rem; }
        .sub { font-size: 0.82rem; color: #6b7280; margin-bottom: 1.5rem; }

        /* ── Summary pills ─────────────────────────────── */
        .summary { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.75rem; }
        .pill-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .pill-label {
            font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #9ca3af;
        }
        .pills { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .pill { padding: 0.25rem 0.7rem; border-radius: 20px; font-size: 0.78rem; font-weight: 500; }
        .pill-blue   { background: #dbeafe; color: #1d4ed8; }
        .pill-green  { background: #d1fae5; color: #065f46; }
        .pill-orange { background: #ffedd5; color: #9a3412; }

        /* ── Search ────────────────────────────────────── */
        .search-bar {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 1.25rem;
        }
        .search-input {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 7px;
            padding: 7px 14px; font-size: 13px; font-family: inherit;
            width: 260px; outline: none; color: #111827;
        }
        .search-input:focus { border-color: #6b7280; }
        .search-hint { font-size: 12px; color: #9ca3af; }

        /* ── Course blocks ─────────────────────────────── */
        .course-block {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 12px; margin-bottom: 1rem; overflow: hidden;
        }
        .course-block[data-hidden="1"] { display: none; }

        .course-header {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.85rem 1.25rem; background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer; user-select: none;
        }
        .course-header:hover { background: #f0f4ff; }

        .course-code {
            font-size: 0.85rem; font-weight: 700;
            background: #4f6ef7; color: #fff;
            padding: 0.2rem 0.6rem; border-radius: 6px;
            min-width: 52px; text-align: center;
        }
        .course-id { font-size: 0.76rem; color: #9ca3af; font-family: monospace; }
        .course-count { margin-left: auto; font-size: 0.78rem; color: #6b7280; }
        .chevron { font-size: 0.75rem; color: #9ca3af; transition: transform 0.2s; }
        .course-block.open .chevron { transform: rotate(180deg); }

        /* ── Module table ──────────────────────────────── */
        .module-table-wrap { padding: 0; }

        table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        thead tr { background: #f9fafb; }
        th {
            padding: 0.55rem 1.25rem; text-align: left;
            font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        td { padding: 0.6rem 1.25rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .module-title { font-weight: 500; color: #111827; }
        .module-id { font-family: monospace; font-size: 0.72rem; color: #9ca3af; }

        .badge {
            display: inline-block; padding: 0.18rem 0.55rem;
            border-radius: 20px; font-size: 0.73rem; font-weight: 500;
        }
        .badge-quiz       { background: #fef3c7; color: #92400e; }
        .badge-assessment { background: #fce7f3; color: #9d174d; }
        .badge-lesson     { background: #d1fae5; color: #065f46; }
        .badge-other      { background: #f3f4f6; color: #6b7280; }

        /* ── Empty state ───────────────────────────────── */
        .empty { padding: 1rem 1.25rem; font-size: 0.82rem; color: #9ca3af; font-style: italic; }
    </style>
</head>
<body>

<h1>📦 Course Structure</h1>
<p class="sub">
    All modules from <code>learner_module_results</code>
    ({{ $courses->sum('module_count') }} distinct modules across {{ $courses->count() }} courses)
</p>

{{-- ── Summary ──────────────────────────────────────────── --}}
@php
    $quizCount       = 0;
    $assessmentCount = 0;
    $lessonCount     = 0;
    $otherCount      = 0;
    foreach ($courses as $course) {
        foreach ($course['modules'] as $mod) {
            $t = ltrim($mod['module_title'] ?? '');
            if (str_starts_with($t, 'Quiz Lesson'))       $quizCount++;
            elseif (str_starts_with($t, 'Module Assessment')) $assessmentCount++;
            elseif (preg_match('/^(Lesson|Slide)/i', $t)) $lessonCount++;
            else                                            $otherCount++;
        }
    }
@endphp
<div class="summary">
    <div class="pill-group">
        <span class="pill-label">Breakdown</span>
        <div class="pills">
            <span class="pill pill-blue">{{ $courses->count() }} courses</span>
            <span class="pill pill-blue">{{ $courses->sum('module_count') }} modules</span>
        </div>
    </div>
    <div class="pill-group">
        <span class="pill-label">By type</span>
        <div class="pills">
            <span class="pill pill-orange">{{ $quizCount }} quiz</span>
            <span class="pill" style="background:#fce7f3;color:#9d174d">{{ $assessmentCount }} assessment</span>
            <span class="pill pill-green">{{ $lessonCount }} lesson/slide</span>
            @if($otherCount > 0)
                <span class="pill" style="background:#f3f4f6;color:#6b7280">{{ $otherCount }} other</span>
            @endif
        </div>
    </div>
</div>

{{-- ── Search ───────────────────────────────────────────── --}}
<div class="search-bar">
    <input type="text" class="search-input" id="courseSearch"
           placeholder="Search course code or module title…"
           oninput="filterCourses(this.value)">
    <span class="search-hint" id="searchHint"></span>
</div>

{{-- ── Course blocks ────────────────────────────────────── --}}
@forelse($courses as $course)
    <div class="course-block" id="block-{{ $loop->index }}"
         data-code="{{ strtolower($course['course_code']) }}">
        <div class="course-header" onclick="toggleBlock({{ $loop->index }})">
            <span class="course-code">{{ $course['course_code'] }}</span>
            <span class="course-id">{{ $course['course_id'] }}</span>
            <span class="course-count">{{ $course['module_count'] }} module(s)</span>
            <span class="chevron">▼</span>
        </div>

        <div class="module-table-wrap" id="body-{{ $loop->index }}" style="display:none">
            @if($course['modules']->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Module Title</th>
                            <th>Type</th>
                            <th>Learners</th>
                            <th>First Seen</th>
                            <th>module_id</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($course['modules'] as $i => $mod)
                            @php
                                $t = ltrim($mod['module_title'] ?? '');
                                if (str_starts_with($t, 'Quiz Lesson'))            { $badge = 'badge-quiz';       $label = 'Quiz'; }
                                elseif (str_starts_with($t, 'Module Assessment'))  { $badge = 'badge-assessment'; $label = 'Assessment'; }
                                elseif (preg_match('/^(Lesson|Slide)/i', $t))     { $badge = 'badge-lesson';     $label = 'Lesson'; }
                                else                                               { $badge = 'badge-other';      $label = 'Other'; }
                            @endphp
                            <tr>
                                <td style="color:#9ca3af;width:36px">{{ $i + 1 }}</td>
                                <td class="module-title">{{ $mod['module_title'] ?? '—' }}</td>
                                <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                                <td style="color:#374151;font-weight:500">{{ $mod['learner_count'] }}</td>
                                <td style="font-size:0.72rem;color:#9ca3af;white-space:nowrap">
                                    {{ $mod['first_seen'] ? \Carbon\Carbon::parse($mod['first_seen'])->format('d M Y') : '—' }}
                                </td>
                                <td class="module-id">{{ $mod['module_id'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="empty">No module data for this course.</p>
            @endif
        </div>
    </div>
@empty
    <p style="color:#9ca3af;font-style:italic">No data found in learner_module_results.</p>
@endforelse

<script>
    function toggleBlock(index) {
        const block = document.getElementById('block-' + index);
        const body  = document.getElementById('body-'  + index);
        const isOpen = block.classList.toggle('open');
        body.style.display = isOpen ? 'block' : 'none';
    }

    // Auto-open first block on load
    document.addEventListener('DOMContentLoaded', () => toggleBlock(0));

    function filterCourses(q) {
        q = q.toLowerCase().trim();
        let shown = 0;
        document.querySelectorAll('.course-block').forEach(block => {
            const code = block.dataset.code ?? '';
            // Also search inside module titles
            const titles = Array.from(block.querySelectorAll('.module-title'))
                .map(el => el.textContent.toLowerCase());
            const match = !q || code.includes(q) || titles.some(t => t.includes(q));
            block.dataset.hidden = match ? '0' : '1';
            block.style.display  = match ? '' : 'none';
            if (match) shown++;
        });
        const hint = document.getElementById('searchHint');
        if (hint) hint.textContent = q ? shown + ' course(s) matched' : '';
    }
</script>

</body>
</html>
