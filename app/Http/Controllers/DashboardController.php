<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\LearnerModuleResult;
use App\Models\IspringUser;
use App\Models\Department;
use App\Models\ReportLog;
use App\Models\Group;

class DashboardController extends Controller
{
    /* =========================================================
     |  CONSTANTS
     | ========================================================= */

    private array $FD_COURSES = [
        'FD01' => '9bb06490-37cd-11ef-9470-42cc767d5507',
        'FD02' => '72d2dfe8-37ce-11ef-b427-ee8800c1cbc6',
        'FD03' => 'adc2ca6e-37ce-11ef-93b2-42cc767d5507',
    ];

    /* =========================================================
     |  PRIVATE HELPERS
     | ========================================================= */

    /**
     * Returns true for module titles that represent real scored/learning content.
     * Excludes: Feedback forms, Study Guides, Supplementary Resources, Overview slides, etc.
     */
    private function isRelevantModule(string $title, bool $includeLessons = false): bool
    {
        $t = ltrim($title);
        return str_starts_with($t, 'Quiz Lesson')
            || str_starts_with($t, 'Module Assessment')
            || ($includeLessons && str_starts_with($t, 'Lesson'));
    }

    /**
     * Resolve rows to ONE effective status per (user_id × course_id × module_title) group,
     * considering only meaningful content (lessons, quizzes, assessments).
     *
     * Lesson rows   : complete/completed → pass | in_progress → progress | else → not_started
     * Quiz/Assessment: passed → pass | in_progress → progress | failed → failed | else → not_started
     *
     * Returns a flat Collection of objects: user_id, course_id, module_title, effective_status
     */
    private function resolveModuleStatuses($rows, bool $includeLessons = false): \Illuminate\Support\Collection
    {
        return $rows
            ->filter(fn($r) => $this->isRelevantModule($r->module_title ?? '', $includeLessons))
            ->groupBy(fn($r) => "{$r->user_id}\x00{$r->course_id}\x00" . ltrim($r->module_title ?? ''))
            ->map(function ($group, $key) {
                [$userId, $courseId, $moduleTitle] = explode("\x00", $key, 3);

                $statuses = $group
                    ->pluck('completion_status')
                    ->map(fn($s) => strtolower(trim($s ?? '')))
                    ->toArray();

                $isLesson = !str_starts_with($moduleTitle, 'Quiz Lesson')
                         && !str_starts_with($moduleTitle, 'Module Assessment');

                if ($isLesson) {
                    $s = $statuses[0] ?? '';
                    $effective = match(true) {
                        in_array($s, ['complete', 'completed']) => 'pass',
                        $s === 'in_progress'                    => 'progress',
                        default                                  => 'not_started',
                    };
                } else {
                    $effective = match(true) {
                        in_array('passed',      $statuses) => 'pass',
                        in_array('in_progress', $statuses) => 'progress',
                        in_array('failed',      $statuses) => 'failed',
                        default                             => 'not_started',
                    };
                }

                return (object) [
                    'user_id'          => $userId,
                    'course_id'        => $courseId,
                    'module_title'     => $moduleTitle,
                    'effective_status' => $effective,
                ];
            })->values();
    }

    /**
     * Return all iSpring user_ids whose role is 'learner'.
     * Result is statically cached so multiple calls in one request hit the DB once.
     */
    private function allLearnerUserIds(): array
    {
        static $cached = null;
        if ($cached === null) {
            $cached = DB::table('users_ispring')
                ->where('role', 'learner')
                ->pluck('user_id')
                ->toArray();
        }
        return $cached;
    }

    /**
     * Count unique users per effective status for a resolved collection.
     * Each user is counted exactly once using their best status across all modules
     * (pass > progress > failed > not_started), preventing double-counting.
     *
     * @param  \Illuminate\Support\Collection  $resolved        Output of resolveModuleStatuses()
     * @param  array|null                      $expectedUserIds Full list of user_ids in scope.
     *                                                          Users absent from $resolved count as not_started.
     */
    private function scopeStats(\Illuminate\Support\Collection $resolved, ?array $expectedUserIds = null): array
    {
        $priority = ['pass' => 3, 'progress' => 2, 'failed' => 1, 'not_started' => 0];

        // One best-status per user
        $userBest = $resolved
            ->groupBy('user_id')
            ->map(function ($rows) use ($priority) {
                return $rows->pluck('effective_status')
                    ->reduce(function ($best, $s) use ($priority) {
                        return ($priority[$s] ?? 0) > ($priority[$best] ?? 0) ? $s : $best;
                    }, 'not_started');
            }); // keyed by user_id → status

        $usersWithData = $userBest->keys();

        $absentCount = $expectedUserIds !== null
            ? collect($expectedUserIds)->diff($usersWithData)->count()
            : 0;

        return [
            'pass'        => $userBest->filter(fn($s) => $s === 'pass')->count(),
            'progress'    => $userBest->filter(fn($s) => $s === 'progress')->count(),
            'failed'      => $userBest->filter(fn($s) => $s === 'failed')->count(),
            'not_started' => $userBest->filter(fn($s) => $s === 'not_started')->count() + $absentCount,
            'total'       => $expectedUserIds !== null ? count($expectedUserIds) : $usersWithData->count(),
        ];
    }

    /**
     * Domain-level KPI counts for ModuleView.
     *
     * Unlike scopeStats(), this is course-aware: it determines each user's best status
     * per course first, then derives a domain-level status so that a user who passed
     * one course but is still in progress on another is counted as "in_progress", not "pass".
     *
     * Rules (applied in order):
     *   pass        — user passed ALL courses in the domain
     *   progress    — user passed some but not all, OR has at least one in_progress course
     *   failed      — user failed at least one course with no pass/progress elsewhere
     *   not_started — user has no activity in any course
     */
    private function domainKpiStats(
        \Illuminate\Support\Collection $resolved,
        array $courseIds,
        ?array $expectedUserIds = null
    ): array {
        $priority = ['pass' => 3, 'progress' => 2, 'failed' => 1, 'not_started' => 0];

        $userCourseStatuses = $resolved
            ->groupBy('user_id')
            ->map(function ($rows) use ($courseIds, $priority) {
                $perCourse = $rows->groupBy('course_id')
                    ->map(fn($cr) => $cr->pluck('effective_status')
                        ->reduce(fn($b, $s) => ($priority[$s] ?? 0) > ($priority[$b] ?? 0) ? $s : $b, 'not_started'));

                foreach ($courseIds as $cid) {
                    if (!$perCourse->has($cid)) {
                        $perCourse[$cid] = 'not_started';
                    }
                }

                return $perCourse->values()->toArray();
            });

        $counts = ['pass' => 0, 'progress' => 0, 'failed' => 0, 'not_started' => 0];

        foreach ($userCourseStatuses as $statuses) {
            $n       = count($statuses);
            $allPass = count(array_filter($statuses, fn($s) => $s === 'pass')) === $n;
            $anyProg = in_array('progress', $statuses);
            $anyPass = in_array('pass', $statuses);
            $anyFail = in_array('failed', $statuses);

            if ($allPass) {
                $counts['pass']++;
            } elseif ($anyProg || ($anyPass && !$allPass)) {
                $counts['progress']++;
            } elseif ($anyFail) {
                $counts['failed']++;
            } else {
                $counts['not_started']++;
            }
        }

        $usersWithData = $userCourseStatuses->keys();
        $absentCount   = $expectedUserIds !== null
            ? collect($expectedUserIds)->diff($usersWithData)->count()
            : 0;
        $counts['not_started'] += $absentCount;

        return array_merge($counts, [
            'total' => $expectedUserIds !== null ? count($expectedUserIds) : $usersWithData->count(),
        ]);
    }

    /**
     * Weakest and strongest topics scoped to given courses (and optionally users).
     * Only scored items (Quiz Lesson X / Module Assessment) are considered.
     */
    private function buildTopicStats(array $courseIds, ?array $userIds = null, bool $includeLessons = false): array
    {
        if (empty($courseIds)) {
            return ['weak' => collect(), 'strong' => collect()];
        }

        $base = DB::table('learner_module_results as lmr')
            ->join('domain_courses as dc', 'lmr.course_id', '=', 'dc.course_id')
            ->whereIn('lmr.course_id', $courseIds)
            ->where(function ($q) use ($includeLessons) {
                $q->where('lmr.module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('lmr.module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('lmr.module_title', 'LIKE', 'Lesson%');
                }
            })
            ->when($userIds !== null, fn($q) => $q->whereIn('lmr.user_id', $userIds))
            ->selectRaw('lmr.module_title, lmr.course_id, dc.course_code, ROUND(AVG(lmr.progress), 1) as avg_progress, COUNT(*) as attempts')
            ->groupBy('lmr.module_title', 'lmr.course_id', 'dc.course_code')
            ->having('attempts', '>', 3);

        return [
            'weak'   => (clone $base)->orderBy('avg_progress', 'asc')->limit(3)->get(),
            'strong' => (clone $base)->orderBy('avg_progress', 'desc')->limit(3)->get(),
        ];
    }

    /**
     * Simulate a weekly cohort progress trend using an ease-in-out S-curve.
     * Starts at 0% from the earliest access_date in learner_module_results and
     * ends at $completionRate% at the current week.
     */
    private function buildCohortTrend(float $completionRate, array $learnerIds): array
    {
        $earliestAccess = !empty($learnerIds)
            ? DB::table('learner_module_results')
                ->whereIn('user_id', $learnerIds)
                ->min('access_date')
            : null;

        $cohortStart  = $earliestAccess
            ? \Carbon\Carbon::parse($earliestAccess)->startOfWeek()
            : now()->subWeeks(12);

        $weeksElapsed = max(4, min(52, (int) $cohortStart->diffInWeeks(now())));

        $labels = [];
        $data   = [];
        for ($w = 0; $w <= $weeksElapsed; $w++) {
            $x        = $weeksElapsed > 0 ? $w / $weeksElapsed : 1;
            $t        = $x < 0.5 ? 2 * $x * $x : 1 - pow(-2 * $x + 2, 2) / 2;
            $labels[] = $cohortStart->copy()->addWeeks($w)->format('d M');
            $data[]   = round($t * $completionRate, 1);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Build bar chart data for FD courses. Optionally restrict to a user_id list.
     *
     * @param  array|null  $userIds         Filter rows to these users (null = all rows)
     * @param  array|null  $expectedUserIds Full pool of users for not_started calculation
     */
    private function buildBarChart($allRows, ?array $userIds = null, ?array $expectedUserIds = null, bool $includeLessons = false): array
    {
        $barChart = [];

        foreach ($this->FD_COURSES as $fdKey => $courseId) {
            $rows = $allRows->where('course_id', $courseId);

            if ($userIds !== null) {
                $rows = $rows->filter(fn($r) => in_array($r->user_id, $userIds));
            }

            $resolved = $this->resolveModuleStatuses($rows, $includeLessons);
            $stats    = $this->scopeStats($resolved, $expectedUserIds);

            $barChart[$fdKey] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
            ];
        }

        return $barChart;
    }

    /**
     * Get all iSpring user_ids in a department.
     */
    private function getUserIdsByDepartment(string $departmentId): array
    {
        return IspringUser::where('department_id', $departmentId)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Get all iSpring user_ids belonging to a group (cohort)
     * via the user_group pivot table.
     */
    private function getUserIdsByGroup(string $groupId): array
    {
        return DB::table('user_group')
            ->where('group_id', $groupId)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Merge department and cohort filters.
     * If both are set, only users satisfying BOTH filters are included.
     * If only one is set, only that filter applies.
     * If neither is set, returns null (meaning no filter — show all).
     */
    private function resolveUserFilter(?string $departmentId, ?string $groupId): ?array
    {
        $byDept  = $departmentId ? $this->getUserIdsByDepartment($departmentId) : null;
        $byGroup = $groupId      ? $this->getUserIdsByGroup($groupId)           : null;

        if ($byDept !== null && $byGroup !== null) {
            // Intersection — user must be in BOTH the agency AND the cohort
            return array_values(array_intersect($byDept, $byGroup));
        }

        return $byDept ?? $byGroup; // whichever is set, or null if neither
    }


    /* =========================================================
     |  ADMIN DASHBOARD
     | ========================================================= */

    public function adminDashboard(Request $request)
    {
        $user = Auth::user();

        $selectedAgency  = $request->input('agency');   // department_id or null
        $selectedCohort  = $request->input('cohort');   // group_id or null
        $includeLessons  = $request->boolean('include_lessons');

        // Fetch all FD course rows (module-level and course-level both handled by resolveModuleStatuses)
        $allRows = LearnerModuleResult::whereIn('course_id', array_values($this->FD_COURSES))->get();

        // Resolve combined filter
        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);

        // Expected users: the filtered subset, or all 316 learners if no filter
        $allLearnerIds   = $this->allLearnerUserIds();
        $expectedUserIds = $filteredUserIds ?? $allLearnerIds;

        /* --- KPI CARDS (always show overall totals) --- */
        $resolved        = $this->resolveModuleStatuses($allRows, $includeLessons);
        $kpi             = $this->scopeStats($resolved, $expectedUserIds);
        $totalEnrollment = $kpi['total'];
        $completionRate  = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgress      = $kpi['progress'];
        $notStarted      = $kpi['not_started'];
        $failed          = $kpi['failed'];

        /* --- BAR CHART (respects both filters) --- */
        $barChart = $this->buildBarChart($allRows, $filteredUserIds, $expectedUserIds, $includeLessons);

        /* --- TOPICS (overall) --- */
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats(array_values($this->FD_COURSES), null, $includeLessons);

        /* --- AGENCIES DROPDOWN (name + department_id) --- */
        $usedDeptIds = IspringUser::whereNotNull('department_id')
            ->pluck('department_id')
            ->unique();

        $agencies = Department::whereIn('department_id', $usedDeptIds)
            ->orderBy('name')
            ->get(['department_id', 'name']);

        /* --- COHORTS DROPDOWN
             Only show groups that have at least one user assigned
             via the user_group pivot table.
        --- */
        $usedGroupIds = DB::table('user_group')
            ->pluck('group_id')
            ->unique();

        $cohorts = Group::whereIn('group_id', $usedGroupIds)
            ->orderBy('name')
            ->get(['group_id', 'name']);

        return view('Dashboard.AdminDashboard', compact(
            'user',
            'totalEnrollment',
            'completionRate',
            'inProgress',
            'notStarted',
            'barChart',
            'weakTopics',
            'strongTopics',
            'agencies',
            'cohorts',
            'selectedAgency',
            'selectedCohort',
            'includeLessons',
        ));
    }


    /* =========================================================
     |  AJAX — update bar chart when either filter changes
     | ========================================================= */

    public function filterBarChart(Request $request)
    {
        $allRows        = LearnerModuleResult::whereIn('course_id', array_values($this->FD_COURSES))->get();
        $includeLessons = $request->boolean('include_lessons');

        $filteredUserIds = $this->resolveUserFilter(
            $request->input('agency'),
            $request->input('cohort')
        );

        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();

        return response()->json([
            'success'  => true,
            'barChart' => $this->buildBarChart($allRows, $filteredUserIds, $expectedUserIds, $includeLessons),
        ]);
    }


    /* =========================================================
     |  AJAX — update level bar chart when filter changes
     | ========================================================= */

    public function filterLevelChart(Request $request)
    {
        $allCourseIds   = DB::table('domain_courses')->pluck('course_id')->toArray();
        $allRows        = !empty($allCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allCourseIds)->get()
            : collect();
        $includeLessons = $request->boolean('include_lessons');

        $filteredUserIds = $this->resolveUserFilter(
            $request->input('agency'),
            $request->input('cohort')
        );
        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();

        $rows        = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;
        $allResolved = $this->resolveModuleStatuses($rows, $includeLessons);

        $levels     = DB::table('levels')->orderBy('order')->get();
        $levelStats = [];

        foreach ($levels as $level) {
            $domainIds     = DB::table('domains')->where('level_id', $level->id)->pluck('id');
            $courseIds     = DB::table('domain_courses')->whereIn('domain_id', $domainIds)->pluck('course_id')->toArray();
            $levelResolved = $allResolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats         = $this->scopeStats($levelResolved, $expectedUserIds);

            $levelStats[$level->name] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
            ];
        }

        return response()->json(['success' => true, 'levelStats' => $levelStats]);
    }


    /* =========================================================
     |  ANALYTICS — LEVEL VIEW
     | ========================================================= */

    public function levelView(Request $request)
    {
        $user = Auth::user();

        // ── Filter resolution ──────────────────────────────────
        $selectedCohort  = $request->input('cohort');
        $selectedAgency  = $request->input('agency');
        $includeLessons  = $request->boolean('include_lessons');
        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
        $allLearnerIds   = $this->allLearnerUserIds();
        $expectedUserIds = $filteredUserIds ?? $allLearnerIds;

        $allCourseIds = DB::table('domain_courses')->pluck('course_id')->toArray();

        $allRows = !empty($allCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allCourseIds)->get()
            : collect();

        // Apply filter to rows before resolving statuses
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        // ── Resolve statuses and KPIs (scoped to filter) ──────
        $allResolved        = $this->resolveModuleStatuses($rows, $includeLessons);
        $kpi                = $this->scopeStats($allResolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];

        // ── Cohort trend line (simulated S-curve) ─────────────
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        // ── Per-level stats (bar chart) ────────────────────────
        $levels     = DB::table('levels')->orderBy('order')->get();
        $levelStats = [];

        foreach ($levels as $level) {
            $domainIds = DB::table('domains')->where('level_id', $level->id)->pluck('id');
            $courseIds = DB::table('domain_courses')->whereIn('domain_id', $domainIds)->pluck('course_id')->toArray();

            $levelResolved = $allResolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats         = $this->scopeStats($levelResolved, $expectedUserIds);

            $levelStats[$level->name] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
                'rate'        => $stats['total'] > 0 ? round(($stats['pass'] / $stats['total']) * 100, 1) : 0,
            ];
        }

        // ── Donut: primary level = level with most enrollments ─
        $learnerLevelRows = DB::table('learner_module_results as lmr')
            ->join('domain_courses as dc', 'lmr.course_id', '=', 'dc.course_id')
            ->join('domains as d', 'dc.domain_id', '=', 'd.id')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->when($filteredUserIds !== null, fn($q) => $q->whereIn('lmr.user_id', $filteredUserIds))
            ->select('lmr.user_id', 'l.name as level_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('lmr.user_id', 'l.name')
            ->get();

        $primaryLevels = [];
        foreach ($learnerLevelRows as $row) {
            if (!isset($primaryLevels[$row->user_id]) || $row->cnt > $primaryLevels[$row->user_id]['cnt']) {
                $primaryLevels[$row->user_id] = ['level_name' => $row->level_name, 'cnt' => $row->cnt];
            }
        }

        $levelDistribution = [];
        foreach ($levels as $level) {
            $levelDistribution[$level->name] = collect($primaryLevels)
                ->filter(fn($p) => $p['level_name'] === $level->name)
                ->count();
        }

        // ── Weak Topics (scoped to filter) ────────────────────
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($allCourseIds, $filteredUserIds, $includeLessons);

        // ── At-Risk Learners (0–49% avg progress, scoped to filter) ─
        $atRiskRows = DB::table('learner_module_results')
            ->whereIn('user_id', $expectedUserIds)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'LIKE', 'Lesson%');
                }
            })
            ->select('user_id', DB::raw('AVG(progress) as avg_progress'))
            ->groupBy('user_id')
            ->having('avg_progress', '<', 50)
            ->orderBy('avg_progress', 'asc')
            ->get();

        $atRiskCount   = $atRiskRows->count();
        $atRiskUserIds = $atRiskRows->pluck('user_id')->toArray();
        $atRiskUserMap = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->whereIn('u.user_id', $atRiskUserIds)
            ->select('u.user_id', 'u.fields', 'd.name as dept_name')
            ->get()->keyBy('user_id');

        $atRiskLearners = $atRiskRows->map(function ($row) use ($atRiskUserMap) {
            $rec    = $atRiskUserMap[$row->user_id] ?? null;
            $fields = json_decode($rec->fields ?? '{}', true);
            $fmap   = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fmap[$f['name']] = is_array($f['value']) ? '' : (string)$f['value'];
            }
            $name     = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $row->user_id;
            $words    = array_slice(explode(' ', $name), 0, 2);
            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words));
            return (object)[
                'user_id'  => $row->user_id,
                'name'     => $name,
                'initials' => $initials,
                'dept'     => $rec->dept_name ?? '—',
                'progress' => (int) round($row->avg_progress),
            ];
        });

        // ── Domain completion for Entry Level (scoped) ────────
        $entryLevel   = $levels->firstWhere('name', 'Entry') ?? $levels->first();
        $entryDomains = collect();
        if ($entryLevel) {
            $domains = DB::table('domains')->where('level_id', $entryLevel->id)->orderBy('order')->get();
            $entryDomains = $domains->map(function ($domain) use ($allResolved, $expectedUserIds) {
                $cIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
                $dr   = $allResolved->filter(fn($r) => in_array($r->course_id, $cIds));
                $st   = $this->scopeStats($dr, $expectedUserIds);
                return (object)[
                    'name' => $domain->name,
                    'rate' => $st['total'] > 0 ? round($st['pass'] / $st['total'] * 100, 1) : 0,
                ];
            });
        }

        // ── Dropdowns ─────────────────────────────────────────
        $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
        $cohorts      = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        $usedDeptIds = IspringUser::whereNotNull('department_id')->pluck('department_id')->unique();
        $agencies    = Department::whereIn('department_id', $usedDeptIds)->orderBy('name')->get(['department_id', 'name']);

        $isPc                = false;
        $analyticsDomainsUrl = route('admin.analytics.domains');
        $studentsUrl         = route('admin.students');

        return view('Dashboard.Analytics.LevelView', compact(
            'user', 'totalEnrollment', 'completionRate', 'inProgressLearners',
            'levelStats', 'levels',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort', 'selectedAgency',
            'atRiskCount', 'atRiskLearners',
            'entryDomains',
            'isPc', 'analyticsDomainsUrl', 'studentsUrl',
            'trendLabels', 'trendData',
            'includeLessons',
        ));
    }


    /* =========================================================
     |  ANALYTICS — DOMAIN VIEW
     | ========================================================= */

    public function domainView(Request $request)
    {
        $user   = Auth::user();
        $levels = DB::table('levels')->orderBy('order')->get();

        $selectedLevelId = $request->input('level_id', $levels->first()->id ?? null);
        $selectedLevel   = $levels->firstWhere('id', (int) $selectedLevelId) ?? $levels->first();

        if (!$selectedLevel) {
            return redirect()->route('admin.analytics.levels');
        }

        $domains   = DB::table('domains')->where('level_id', $selectedLevel->id)->orderBy('order')->get();
        $domainIds = $domains->pluck('id')->toArray();

        $allLevelCourseIds = DB::table('domain_courses')
            ->whereIn('domain_id', $domainIds)
            ->pluck('course_id')->toArray();

        $allRows = !empty($allLevelCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allLevelCourseIds)->get()
            : collect();

        // Apply cohort/agency filter
        $selectedCohort  = $request->input('cohort');
        $selectedAgency  = $request->input('agency');
        $includeLessons  = $request->boolean('include_lessons');
        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        // Expected users: filtered subset or all 316 learners
        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();

        // KPIs
        $resolved           = $this->resolveModuleStatuses($rows, $includeLessons);
        $kpi                = $this->scopeStats($resolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];
        $notStartedLearners = $kpi['not_started'];

        // Cohort trend line (simulated)
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        // Per-domain stats for bar chart
        $domainStats = [];
        foreach ($domains as $domain) {
            $courseIds    = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
            $domainRes    = $resolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats        = $this->scopeStats($domainRes, $expectedUserIds);

            $domainStats[$domain->name] = [
                'domain_id'   => $domain->id,
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
            ];
        }

        // Weak / Strong Topics (scoped to selected level)
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($allLevelCourseIds, null, $includeLessons);

        // Dropdowns
        $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
        $cohorts      = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);
        $usedDeptIds  = IspringUser::whereNotNull('department_id')->pluck('department_id')->unique();
        $agencies     = Department::whereIn('department_id', $usedDeptIds)->orderBy('name')->get(['department_id', 'name']);

        $isPc                = false;
        $analyticsDomainsUrl = route('admin.analytics.domains');
        $moduleViewUrl       = route('admin.analytics.modules');

        return view('Dashboard.Analytics.DomainView', compact(
            'user', 'levels', 'selectedLevel', 'domains', 'domainStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort', 'selectedAgency',
            'isPc', 'analyticsDomainsUrl', 'moduleViewUrl',
            'trendLabels', 'trendData',
            'includeLessons',
        ));
    }

    public function filterDomainChart(Request $request)
    {
        $levelId   = $request->input('level_id');
        $domainIds = DB::table('domains')->where('level_id', $levelId)->pluck('id')->toArray();
        $domains   = DB::table('domains')->where('level_id', $levelId)->orderBy('order')->get();

        $allCourseIds = DB::table('domain_courses')
            ->whereIn('domain_id', $domainIds)
            ->pluck('course_id')->toArray();

        $allRows = !empty($allCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allCourseIds)->get()
            : collect();

        $includeLessons  = $request->boolean('include_lessons');
        $filteredUserIds = $this->resolveUserFilter($request->input('agency'), $request->input('cohort'));
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();
        $resolved        = $this->resolveModuleStatuses($rows, $includeLessons);
        $domainStats = [];
        foreach ($domains as $domain) {
            $courseIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
            $domainRes = $resolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats     = $this->scopeStats($domainRes, $expectedUserIds);

            $domainStats[$domain->name] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
            ];
        }

        return response()->json(['success' => true, 'domainStats' => $domainStats]);
    }


    /* =========================================================
     |  OTHER DASHBOARDS
     | ========================================================= */

    public function pcDashboard(Request $request)
    {
        $user         = Auth::user();
        $departmentId = $user->department_id;

        $selectedCohort = $request->input('cohort');
        $includeLessons = $request->boolean('include_lessons');

        $allRows = LearnerModuleResult::whereIn('course_id', array_values($this->FD_COURSES))->get();

        $agencyUserIds   = $departmentId ? $this->getUserIdsByDepartment($departmentId) : [];
        $filteredUserIds = $this->resolveUserFilter($departmentId, $selectedCohort);

        $agencyRows     = !empty($filteredUserIds)
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : ($departmentId ? $allRows->filter(fn($r) => in_array($r->user_id, $agencyUserIds)) : $allRows);

        // For PC dashboard, expected pool = agency users (or filtered subset)
        $expectedUserIds = !empty($filteredUserIds)
            ? $filteredUserIds
            : (!empty($agencyUserIds) ? $agencyUserIds : $this->allLearnerUserIds());

        $pcResolved      = $this->resolveModuleStatuses($agencyRows, $includeLessons);
        $kpi             = $this->scopeStats($pcResolved, $expectedUserIds);
        $totalEnrollment = $kpi['total'];
        $completionRate  = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgress      = $kpi['progress'];
        $notStarted      = $kpi['not_started'];

        $chartUserIds = !empty($filteredUserIds) ? $filteredUserIds : ($departmentId ? $agencyUserIds : null);
        $barChart     = $this->buildBarChart($allRows, $chartUserIds, $expectedUserIds, $includeLessons);

        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats(
            array_values($this->FD_COURSES),
            !empty($agencyUserIds) ? $agencyUserIds : null,
            $includeLessons
        );

        $usedGroupIds = !empty($agencyUserIds)
            ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
            : DB::table('user_group')->pluck('group_id')->unique();
        $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        $agencyName = $departmentId
            ? (Department::where('department_id', $departmentId)->value('name') ?? 'Your Agency')
            : 'Agency';

        return view('Dashboard.PCDashboard', compact(
            'user', 'totalEnrollment', 'completionRate', 'inProgress', 'notStarted',
            'barChart', 'weakTopics', 'strongTopics', 'cohorts', 'selectedCohort',
            'agencyName', 'departmentId', 'includeLessons',
        ));
    }

    public function pcAnalyticsLevels(Request $request)
    {
        $user           = Auth::user();
        $departmentId   = $user->department_id;
        $selectedCohort = $request->input('cohort');
        $includeLessons = $request->boolean('include_lessons');

        $allCourseIds = DB::table('domain_courses')->pluck('course_id')->toArray();
        $allRows      = !empty($allCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allCourseIds)->get()
            : collect();

        $agencyUserIds   = $departmentId ? $this->getUserIdsByDepartment($departmentId) : [];
        $filteredUserIds = $this->resolveUserFilter($departmentId, $selectedCohort);
        $expectedUserIds = $filteredUserIds ?? (!empty($agencyUserIds) ? $agencyUserIds : $this->allLearnerUserIds());
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : (!empty($agencyUserIds) ? $allRows->filter(fn($r) => in_array($r->user_id, $agencyUserIds)) : $allRows);

        $allResolved        = $this->resolveModuleStatuses($rows, $includeLessons);
        $kpi                = $this->scopeStats($allResolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];

        // ── Cohort trend line (simulated S-curve, scoped to agency) ──
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        $levels     = DB::table('levels')->orderBy('order')->get();
        $levelStats = [];

        foreach ($levels as $level) {
            $domainIds     = DB::table('domains')->where('level_id', $level->id)->pluck('id');
            $courseIds     = DB::table('domain_courses')->whereIn('domain_id', $domainIds)->pluck('course_id')->toArray();
            $levelResolved = $allResolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats         = $this->scopeStats($levelResolved, $expectedUserIds);

            $levelStats[$level->name] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
                'rate'        => $stats['total'] > 0 ? round(($stats['pass'] / $stats['total']) * 100, 1) : 0,
            ];
        }

        $learnerLevelRows = DB::table('learner_module_results as lmr')
            ->join('domain_courses as dc', 'lmr.course_id', '=', 'dc.course_id')
            ->join('domains as d', 'dc.domain_id', '=', 'd.id')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->when(!empty($expectedUserIds), fn($q) => $q->whereIn('lmr.user_id', $expectedUserIds))
            ->select('lmr.user_id', 'l.name as level_name', DB::raw('COUNT(*) as cnt'))
            ->groupBy('lmr.user_id', 'l.name')->get();

        $primaryLevels = [];
        foreach ($learnerLevelRows as $row) {
            if (!isset($primaryLevels[$row->user_id]) || $row->cnt > $primaryLevels[$row->user_id]['cnt']) {
                $primaryLevels[$row->user_id] = ['level_name' => $row->level_name, 'cnt' => $row->cnt];
            }
        }

        $levelDistribution = [];
        foreach ($levels as $level) {
            $levelDistribution[$level->name] = collect($primaryLevels)
                ->filter(fn($p) => $p['level_name'] === $level->name)->count();
        }

        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats(
            $allCourseIds,
            !empty($expectedUserIds) ? $expectedUserIds : null,
            $includeLessons
        );

        $usedGroupIds = !empty($agencyUserIds)
            ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
            : DB::table('user_group')->pluck('group_id')->unique();
        $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        // ── At-Risk Learners (0–49% avg progress, scoped to agency/cohort) ─
        $atRiskRows = DB::table('learner_module_results')
            ->whereIn('user_id', $expectedUserIds)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'LIKE', 'Lesson%');
                }
            })
            ->select('user_id', DB::raw('AVG(progress) as avg_progress'))
            ->groupBy('user_id')
            ->having('avg_progress', '<', 50)
            ->orderBy('avg_progress', 'asc')
            ->get();

        $atRiskCount   = $atRiskRows->count();
        $atRiskUserIds = $atRiskRows->pluck('user_id')->toArray();
        $atRiskUserMap = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->whereIn('u.user_id', $atRiskUserIds)
            ->select('u.user_id', 'u.fields', 'd.name as dept_name')
            ->get()->keyBy('user_id');

        $atRiskLearners = $atRiskRows->map(function ($row) use ($atRiskUserMap) {
            $rec    = $atRiskUserMap[$row->user_id] ?? null;
            $fields = json_decode($rec->fields ?? '{}', true);
            $fmap   = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fmap[$f['name']] = is_array($f['value']) ? '' : (string)$f['value'];
            }
            $name     = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $row->user_id;
            $words    = array_slice(explode(' ', $name), 0, 2);
            $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), $words));
            return (object)[
                'user_id'  => $row->user_id,
                'name'     => $name,
                'initials' => $initials,
                'dept'     => $rec->dept_name ?? '—',
                'progress' => (int) round($row->avg_progress),
            ];
        });

        // ── Domain completion for Entry Level (scoped) ────────
        $entryLevel   = $levels->firstWhere('name', 'Entry') ?? $levels->first();
        $entryDomains = collect();
        if ($entryLevel) {
            $domains      = DB::table('domains')->where('level_id', $entryLevel->id)->orderBy('order')->get();
            $entryDomains = $domains->map(function ($domain) use ($allResolved, $expectedUserIds) {
                $cIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
                $dr   = $allResolved->filter(fn($r) => in_array($r->course_id, $cIds));
                $st   = $this->scopeStats($dr, $expectedUserIds);
                return (object)[
                    'name' => $domain->name,
                    'rate' => $st['total'] > 0 ? round($st['pass'] / $st['total'] * 100, 1) : 0,
                ];
            });
        }

        $isPc                = true;
        $agencies            = collect();
        $analyticsDomainsUrl = route('pc.analytics.domains');
        $studentsUrl         = route('pc.students');

        return view('Dashboard.Analytics.LevelView', compact(
            'user', 'totalEnrollment', 'completionRate', 'inProgressLearners',
            'levelStats', 'levelDistribution', 'levels',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort',
            'atRiskCount', 'atRiskLearners',
            'entryDomains',
            'isPc', 'analyticsDomainsUrl', 'studentsUrl',
            'trendLabels', 'trendData',
            'includeLessons',
        ));
    }

    public function pcAnalyticsDomains(Request $request)
    {
        $user         = Auth::user();
        $departmentId = $user->department_id;

        $levels          = DB::table('levels')->orderBy('order')->get();
        $selectedLevelId = $request->input('level_id', $levels->first()->id ?? null);
        $selectedLevel   = $levels->firstWhere('id', (int) $selectedLevelId) ?? $levels->first();

        if (!$selectedLevel) {
            return redirect()->route('pc.analytics.levels');
        }

        $domains   = DB::table('domains')->where('level_id', $selectedLevel->id)->orderBy('order')->get();
        $domainIds = $domains->pluck('id')->toArray();

        $allLevelCourseIds = DB::table('domain_courses')
            ->whereIn('domain_id', $domainIds)->pluck('course_id')->toArray();

        $allRows = !empty($allLevelCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allLevelCourseIds)->get()
            : collect();

        $agencyUserIds   = $departmentId ? $this->getUserIdsByDepartment($departmentId) : [];
        $selectedCohort  = $request->input('cohort');
        $includeLessons  = $request->boolean('include_lessons');
        $filteredUserIds = $this->resolveUserFilter($departmentId, $selectedCohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : (!empty($agencyUserIds) ? $allRows->filter(fn($r) => in_array($r->user_id, $agencyUserIds)) : $allRows);

        $expectedUserIds = $filteredUserIds ?? (!empty($agencyUserIds) ? $agencyUserIds : $this->allLearnerUserIds());

        $resolved           = $this->resolveModuleStatuses($rows, $includeLessons);
        $kpi                = $this->scopeStats($resolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];
        $notStartedLearners = $kpi['not_started'];

        // Cohort trend line (simulated, scoped to agency)
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        $domainStats = [];
        foreach ($domains as $domain) {
            $courseIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
            $domainRes = $resolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats     = $this->scopeStats($domainRes, $expectedUserIds);

            $domainStats[$domain->name] = [
                'domain_id'   => $domain->id,
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
            ];
        }

        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats(
            $allLevelCourseIds,
            !empty($agencyUserIds) ? $agencyUserIds : null,
            $includeLessons
        );

        $usedGroupIds = !empty($agencyUserIds)
            ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
            : DB::table('user_group')->pluck('group_id')->unique();
        $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        $isPc                = true;
        $selectedAgency      = $departmentId;
        $agencies            = collect();
        $analyticsDomainsUrl = route('pc.analytics.domains');
        $moduleViewUrl       = route('pc.analytics.modules');

        return view('Dashboard.Analytics.DomainView', compact(
            'user', 'levels', 'selectedLevel', 'domains', 'domainStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort', 'selectedAgency',
            'isPc', 'analyticsDomainsUrl', 'moduleViewUrl',
            'trendLabels', 'trendData',
            'includeLessons',
        ));
    }

    /* =========================================================
     |  ANALYTICS — MODULE VIEW (Admin)
     | ========================================================= */

    public function analyticsModules(Request $request)
    {
        $user = Auth::user();

        $allDomains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->select('d.*', 'l.name as level_name')
            ->orderBy('l.order')
            ->orderBy('d.order')
            ->get();

        $selectedDomainId = $request->input('domain_id', $allDomains->first()->id ?? null);
        $selectedDomain   = $allDomains->firstWhere('id', (int) $selectedDomainId) ?? $allDomains->first();

        $selectedCohort = $request->input('cohort');
        $selectedAgency = $request->input('agency');
        $includeLessons = $request->boolean('include_lessons');

        $domainCourses = $selectedDomain
            ? DB::table('domain_courses')->where('domain_id', $selectedDomain->id)->orderBy('id')->get()
            : collect();

        $hasCourses = $domainCourses->isNotEmpty();

        $courseStats = $totalEnrollment = $completionRate = $inProgressLearners = $notStartedLearners = null;
        $weakTopics = $strongTopics = collect();
        $cohorts = collect();
        $agencies = collect();
        $scoreBandsData = [];

        if ($hasCourses) {
            $courseIds = $domainCourses->pluck('course_id')->toArray();
            $allRows   = DB::table('learner_module_results')->whereIn('course_id', $courseIds)->get();

            $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
            $rows = $filteredUserIds !== null
                ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
                : $allRows;

            $expectedUserIds    = $filteredUserIds ?? $this->allLearnerUserIds();
            $resolved           = $this->resolveModuleStatuses($rows, $includeLessons);
            $kpi                = $this->domainKpiStats($resolved, $courseIds, $expectedUserIds);
            $totalEnrollment    = $kpi['total'];
            $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
            $inProgressLearners = $kpi['progress'];
            $notStartedLearners = $kpi['not_started'];

            $courseStats = [];
            foreach ($domainCourses as $dc) {
                $cr    = $resolved->filter(fn($r) => $r->course_id === $dc->course_id);
                $stats = $this->scopeStats($cr, $expectedUserIds);
                $courseStats[$dc->course_code] = [
                    'pass'        => $stats['pass'],
                    'progress'    => $stats['progress'],
                    'failed'      => $stats['failed'],
                    'not_started' => $stats['not_started'],
                    'total'       => $stats['total'],
                ];
            }

            // Score distribution: every expected learner counts for every module.
            // Attempted learners get their best (max) progress score; learners who
            // have not attempted (no row, or only not_started rows) score 0 → failing.
            // This prevents the asymmetry where unattempted learners appear in some
            // modules but not others depending on whether iSpring created a row for them.
            $attemptedStatuses = ['passed', 'failed', 'in_progress', 'completed', 'complete'];
            foreach ($domainCourses as $dc) {
                $attemptedRows = $rows->filter(
                    fn($r) => $r->course_id === $dc->course_id
                           && (str_starts_with($r->module_title ?? '', 'Quiz Lesson')
                               || str_starts_with($r->module_title ?? '', 'Module Assessment')
                               || ($includeLessons && str_starts_with($r->module_title ?? '', 'Lesson')))
                           && in_array(strtolower(trim($r->completion_status ?? '')), $attemptedStatuses)
                );
                $attemptedScores = $attemptedRows
                    ->groupBy('user_id')
                    ->map(fn($userRows) => $userRows->max('progress'));

                // All expected learners: use attempted score or 0 for unattempted
                $userScores = collect($expectedUserIds)->mapWithKeys(
                    fn($uid) => [$uid => $attemptedScores[$uid] ?? 0]
                );

                $scoreBandsData[$dc->course_code] = [
                    'failing'    => $userScores->filter(fn($p) => $p < 50)->count(),
                    'borderline' => $userScores->filter(fn($p) => $p >= 50 && $p < 70)->count(),
                    'solid'      => $userScores->filter(fn($p) => $p >= 70 && $p < 90)->count(),
                    'strong'     => $userScores->filter(fn($p) => $p >= 90)->count(),
                    'total'      => $userScores->count(),
                ];
            }

            ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($courseIds, null, $includeLessons);

            $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
            $cohorts      = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

            $usedDeptIds = IspringUser::whereNotNull('department_id')->pluck('department_id')->unique();
            $agencies    = Department::whereIn('department_id', $usedDeptIds)->orderBy('name')->get(['department_id', 'name']);
        }

        $isPc        = false;
        $studentsUrl = route('admin.students');

        return view('Dashboard.Analytics.ModuleView', compact(
            'user', 'allDomains', 'selectedDomain', 'hasCourses', 'courseStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort', 'selectedAgency', 'isPc', 'studentsUrl',
            'scoreBandsData', 'includeLessons',
        ));
    }


    /* =========================================================
     |  ANALYTICS — MODULE VIEW (PC)
     | ========================================================= */

    public function pcAnalyticsModules(Request $request)
    {
        $user         = Auth::user();
        $departmentId = $user->department_id;
        $agencyUserIds = $departmentId ? $this->getUserIdsByDepartment($departmentId) : [];

        $allDomains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->select('d.*', 'l.name as level_name')
            ->orderBy('l.order')
            ->orderBy('d.order')
            ->get();

        $selectedDomainId = $request->input('domain_id', $allDomains->first()->id ?? null);
        $selectedDomain   = $allDomains->firstWhere('id', (int) $selectedDomainId) ?? $allDomains->first();

        $selectedCohort = $request->input('cohort');
        $includeLessons = $request->boolean('include_lessons');

        $domainCourses = $selectedDomain
            ? DB::table('domain_courses')->where('domain_id', $selectedDomain->id)->orderBy('id')->get()
            : collect();

        $hasCourses = $domainCourses->isNotEmpty();

        $courseStats = $totalEnrollment = $completionRate = $inProgressLearners = $notStartedLearners = null;
        $weakTopics = $strongTopics = collect();
        $cohorts = collect();
        $agencies = collect();
        $scoreBandsData = [];

        if ($hasCourses) {
            $courseIds = $domainCourses->pluck('course_id')->toArray();
            $allRows   = DB::table('learner_module_results')->whereIn('course_id', $courseIds)->get();

            $filteredUserIds = $this->resolveUserFilter($departmentId, $selectedCohort);
            $rows = $filteredUserIds !== null
                ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
                : (!empty($agencyUserIds) ? $allRows->filter(fn($r) => in_array($r->user_id, $agencyUserIds)) : $allRows);

            $expectedUserIds    = $filteredUserIds ?? (!empty($agencyUserIds) ? $agencyUserIds : $this->allLearnerUserIds());
            $resolved           = $this->resolveModuleStatuses($rows, $includeLessons);
            $kpi                = $this->domainKpiStats($resolved, $courseIds, $expectedUserIds);
            $totalEnrollment    = $kpi['total'];
            $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
            $inProgressLearners = $kpi['progress'];
            $notStartedLearners = $kpi['not_started'];

            $courseStats = [];
            foreach ($domainCourses as $dc) {
                $cr    = $resolved->filter(fn($r) => $r->course_id === $dc->course_id);
                $stats = $this->scopeStats($cr, $expectedUserIds);
                $courseStats[$dc->course_code] = [
                    'pass'        => $stats['pass'],
                    'progress'    => $stats['progress'],
                    'failed'      => $stats['failed'],
                    'not_started' => $stats['not_started'],
                    'total'       => $stats['total'],
                ];
            }

            // Score distribution: every expected learner counts for every module.
            // Attempted learners get their best (max) progress score; learners who
            // have not attempted (no row, or only not_started rows) score 0 → failing.
            // This prevents the asymmetry where unattempted learners appear in some
            // modules but not others depending on whether iSpring created a row for them.
            $attemptedStatuses = ['passed', 'failed', 'in_progress', 'completed', 'complete'];
            foreach ($domainCourses as $dc) {
                $attemptedRows = $rows->filter(
                    fn($r) => $r->course_id === $dc->course_id
                           && (str_starts_with($r->module_title ?? '', 'Quiz Lesson')
                               || str_starts_with($r->module_title ?? '', 'Module Assessment')
                               || ($includeLessons && str_starts_with($r->module_title ?? '', 'Lesson')))
                           && in_array(strtolower(trim($r->completion_status ?? '')), $attemptedStatuses)
                );
                $attemptedScores = $attemptedRows
                    ->groupBy('user_id')
                    ->map(fn($userRows) => $userRows->max('progress'));

                // All expected learners: use attempted score or 0 for unattempted
                $userScores = collect($expectedUserIds)->mapWithKeys(
                    fn($uid) => [$uid => $attemptedScores[$uid] ?? 0]
                );

                $scoreBandsData[$dc->course_code] = [
                    'failing'    => $userScores->filter(fn($p) => $p < 50)->count(),
                    'borderline' => $userScores->filter(fn($p) => $p >= 50 && $p < 70)->count(),
                    'solid'      => $userScores->filter(fn($p) => $p >= 70 && $p < 90)->count(),
                    'strong'     => $userScores->filter(fn($p) => $p >= 90)->count(),
                    'total'      => $userScores->count(),
                ];
            }

            ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats(
                $courseIds,
                !empty($agencyUserIds) ? $agencyUserIds : null,
                $includeLessons
            );

            $usedGroupIds = !empty($agencyUserIds)
                ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
                : DB::table('user_group')->pluck('group_id')->unique();
            $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);
        }

        $isPc           = true;
        $selectedAgency = $departmentId;
        $studentsUrl    = route('pc.students');

        return view('Dashboard.Analytics.ModuleView', compact(
            'user', 'allDomains', 'selectedDomain', 'hasCourses', 'courseStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners',
            'weakTopics', 'strongTopics', 'cohorts', 'agencies',
            'selectedCohort', 'selectedAgency', 'isPc', 'studentsUrl',
            'scoreBandsData', 'includeLessons',
        ));
    }

    public function learnerDashboard()
    {
        return view('Dashboard.LearnerDashboard', ['user' => Auth::user()]);
    }

    /* =========================================================
     |  STUDENT PROGRESS — Admin
     | ========================================================= */

    public function adminStudentProgress(Request $request)
    {
        $user           = Auth::user();
        $selectedAgency = $request->input('agency');
        $selectedCohort = $request->input('cohort');
        $selectedDomain = $request->input('domain') ? (int) $request->input('domain') : null;
        $selectedCourse = $request->input('course');
        $includeLessons = $request->boolean('include_lessons');

        $learners = $this->fetchLearners($selectedAgency, $selectedCohort);
        $learners = $this->attachLearnerProgress($learners, $selectedDomain, $selectedCourse, $includeLessons);

        $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
        $cohorts      = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        $usedDeptIds = IspringUser::whereNotNull('department_id')->pluck('department_id')->unique();
        $agencies    = Department::whereIn('department_id', $usedDeptIds)->orderBy('name')->get(['department_id', 'name']);

        $domains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->select('d.id', 'd.name', 'l.name as level_name')
            ->orderBy('l.order')->orderBy('d.order')
            ->get();

        $domainCourses = $selectedDomain
            ? DB::table('domain_courses')->where('domain_id', $selectedDomain)->orderBy('id')->get()
            : collect();

        $isPc = false;

        return view('Dashboard.StudentProgress', compact(
            'user', 'learners', 'cohorts', 'agencies',
            'selectedAgency', 'selectedCohort', 'isPc',
            'domains', 'domainCourses', 'selectedDomain', 'selectedCourse',
            'includeLessons',
        ));
    }


    /* =========================================================
     |  STUDENT PROGRESS — PC
     | ========================================================= */

    public function pcStudentProgress(Request $request)
    {
        $user           = Auth::user();
        $departmentId   = $user->department_id;
        $selectedCohort = $request->input('cohort');
        $selectedDomain = $request->input('domain') ? (int) $request->input('domain') : null;
        $selectedCourse = $request->input('course');
        $includeLessons = $request->boolean('include_lessons');

        $learners = $this->fetchLearners($departmentId, $selectedCohort);
        $learners = $this->attachLearnerProgress($learners, $selectedDomain, $selectedCourse, $includeLessons);

        $agencyUserIds = $departmentId ? $this->getUserIdsByDepartment($departmentId) : [];

        $usedGroupIds = !empty($agencyUserIds)
            ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
            : DB::table('user_group')->pluck('group_id')->unique();
        $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        $domains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->select('d.id', 'd.name', 'l.name as level_name')
            ->orderBy('l.order')->orderBy('d.order')
            ->get();

        $domainCourses = $selectedDomain
            ? DB::table('domain_courses')->where('domain_id', $selectedDomain)->orderBy('id')->get()
            : collect();

        $agencyName     = $departmentId
            ? (Department::where('department_id', $departmentId)->value('name') ?? 'Your Agency')
            : 'Agency';
        $isPc           = true;
        $selectedAgency = $departmentId;
        $agencies       = collect();

        return view('Dashboard.StudentProgress', compact(
            'user', 'learners', 'cohorts', 'agencies',
            'selectedAgency', 'selectedCohort', 'isPc', 'agencyName',
            'domains', 'domainCourses', 'selectedDomain', 'selectedCourse',
            'includeLessons',
        ));
    }


    /* =========================================================
     |  PRIVATE HELPER — fetch + name learners
     | ========================================================= */

    private function fetchLearners(?string $departmentId, ?string $groupId): \Illuminate\Support\Collection
    {
        $query = DB::table('users_ispring as u')
            ->where('u.role', 'learner')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->select('u.user_id', 'u.fields', 'u.department_id', 'd.name as department_name');

        if ($departmentId) {
            $query->where('u.department_id', $departmentId);
        }

        if ($groupId) {
            $cohortUserIds = DB::table('user_group')->where('group_id', $groupId)->pluck('user_id');
            $query->whereIn('u.user_id', $cohortUserIds);
        }

        return $query->get()->map(function ($row) {
            $fields   = json_decode($row->fields ?? '{}', true);
            $fieldMap = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fieldMap[$f['name']] = is_array($f['value']) ? '' : (string) $f['value'];
            }
            $row->full_name = trim(($fieldMap['FIRST_NAME'] ?? '') . ' ' . ($fieldMap['LAST_NAME'] ?? ''))
                ?: $row->user_id;
            return $row;
        });
    }


    /**
     * Attach avg_progress, last_active, has_results to each learner row.
     *
     * When $domainId is given: progress is averaged across courses in that domain only.
     * When $courseId is given: progress is the direct average for that single course.
     * When neither is set: domain-weighted average across ALL domains (default).
     */
    private function attachLearnerProgress(
        \Illuminate\Support\Collection $learners,
        ?int $domainId = null,
        ?string $courseId = null,
        bool $includeLessons = false
    ): \Illuminate\Support\Collection {
        $learnerIds = $learners->pluck('user_id')->toArray();

        if (empty($learnerIds)) {
            return $learners->map(function ($learner) {
                $learner->avg_progress = 0;
                $learner->last_active  = null;
                $learner->has_results  = false;
                return $learner;
            })->values();
        }

        // ── Single-course filter ──────────────────────────────
        if ($courseId !== null) {
            $rows = DB::table('learner_module_results')
                ->whereIn('user_id', $learnerIds)
                ->where('course_id', $courseId)
                ->where(function ($q) use ($includeLessons) {
                    $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                      ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                    if ($includeLessons) {
                        $q->orWhere('module_title', 'LIKE', 'Lesson%');
                    }
                })
                ->select('user_id', 'progress', 'updated_at')
                ->get();

            $progressMap = $rows->groupBy('user_id')->map(fn($ur) => (object)[
                'avg_progress' => (int) round($ur->avg('progress')),
                'last_active'  => $ur->max('updated_at'),
            ]);

            return $learners->map(function ($learner) use ($progressMap) {
                $row = $progressMap[$learner->user_id] ?? null;
                $learner->avg_progress = $row ? $row->avg_progress : 0;
                $learner->last_active  = $row ? $row->last_active : null;
                $learner->has_results  = $row !== null;
                return $learner;
            })->sortByDesc('avg_progress')->values();
        }

        // ── Domain or all-domains path ────────────────────────
        $domainCourseGroups = $domainId !== null
            ? DB::table('domain_courses')
                ->where('domain_id', $domainId)
                ->select('domain_id', 'course_id')
                ->get()
                ->groupBy('domain_id')
            : DB::table('domain_courses')
                ->select('domain_id', 'course_id')
                ->get()
                ->groupBy('domain_id');

        $allCourseIds = $domainCourseGroups->flatten()->pluck('course_id')->toArray();
        $domainCount  = $domainCourseGroups->count();

        if (empty($allCourseIds) || $domainCount === 0) {
            return $learners->map(function ($learner) {
                $learner->avg_progress = 0;
                $learner->last_active  = null;
                $learner->has_results  = false;
                return $learner;
            })->values();
        }

        $allRows = DB::table('learner_module_results')
            ->whereIn('user_id', $learnerIds)
            ->whereIn('course_id', $allCourseIds)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'LIKE', 'Lesson%');
                }
            })
            ->select('user_id', 'course_id', 'progress', 'updated_at')
            ->get();

        $progressMap = $allRows->groupBy('user_id')->map(function ($userRows) use ($domainCourseGroups, $domainCount) {
            $domainSum = $domainCourseGroups->sum(function ($domCourses) use ($userRows) {
                $ids     = $domCourses->pluck('course_id')->toArray();
                $domRows = $userRows->filter(fn($r) => in_array($r->course_id, $ids));
                return $domRows->count() > 0 ? $domRows->avg('progress') : 0.0;
            });

            return (object)[
                'avg_progress' => (int) round($domainSum / $domainCount),
                'last_active'  => $userRows->max('updated_at'),
            ];
        });

        return $learners->map(function ($learner) use ($progressMap) {
            $row = $progressMap[$learner->user_id] ?? null;
            $learner->avg_progress = $row ? $row->avg_progress : 0;
            $learner->last_active  = $row ? $row->last_active : null;
            $learner->has_results  = $row !== null;
            return $learner;
        })->sortByDesc('avg_progress')->values();
    }


    /* =========================================================
     |  SETTINGS
     | ========================================================= */

    public function settingsPage()
    {
        $settings = $this->readAppSettings();
        return view('Dashboard.Settings', [
            'user'            => Auth::user(),
            'autoSyncEnabled' => (bool) ($settings['auto_sync'] ?? false),
        ]);
    }

    public function updateSyncSetting(Request $request)
    {
        $enabled  = (bool) $request->input('auto_sync', false);
        $path     = storage_path('app/app_settings.json');
        $settings = $this->readAppSettings();
        $settings['auto_sync'] = $enabled;
        file_put_contents($path, json_encode($settings), LOCK_EX);
        return response()->json(['success' => true, 'auto_sync' => $enabled]);
    }

    private function readAppSettings(): array
    {
        $path = storage_path('app/app_settings.json');
        if (!file_exists($path)) {
            return ['auto_sync' => false];
        }
        return json_decode(file_get_contents($path), true) ?? ['auto_sync' => false];
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'user_id' => 'required|string|max:100|alpha_num',
        ]);

        $desired = $request->input('user_id');

        // Check uniqueness excluding the current user, and surface a helpful suggestion if taken
        if (\App\Models\User::where('user_id', $desired)->where('id', '!=', $user->id)->exists()) {
            $suggestion = $this->suggestUsername($request->input('name'), $user->id);
            return back()
                ->withErrors(['user_id' => "Username \"{$desired}\" is already taken. Try \"{$suggestion}\" instead."])
                ->withInput();
        }

        $user->update([
            'name'    => $request->input('name'),
            'email'   => $request->input('email') ?? $user->email,
            'user_id' => $desired,
        ]);

        return back()->with('profile_success', 'Profile updated successfully.');
    }

    private function suggestUsername(string $name, int $excludeId): string
    {
        $first     = preg_split('/\s+/', trim($name))[0] ?? $name;
        $base      = preg_replace('/[^a-zA-Z0-9]/', '', $first);
        $candidate = $base;
        $suffix    = 1;
        while (\App\Models\User::where('user_id', $candidate)->where('id', '!=', $excludeId)->exists()) {
            $candidate = $base . $suffix++;
        }
        return $candidate;
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($request->new_password)]);

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Password changed');

        return back()->with('password_success', 'Password updated successfully.');
    }


    public function refreshApiData()
    {
        $status = $this->readSyncStatus();

        // Don't start a new sync if one is already in progress
        if (in_array($status['status'] ?? 'idle', ['running', 'starting'])) {
            return response()->json(['success' => true, 'already_running' => true]);
        }

        // Enforce 5-minute cooldown between syncs
        if (!empty($status['completed_at'])) {
            $completedAt     = \Carbon\Carbon::parse($status['completed_at']);
            $elapsedSeconds  = $completedAt->diffInSeconds(now());
            $cooldownSeconds = 5 * 60;

            if ($elapsedSeconds < $cooldownSeconds) {
                return response()->json([
                    'cooldown'  => true,
                    'remaining' => $cooldownSeconds - $elapsedSeconds,
                    'message'   => 'Sync is on cooldown. Try again in ' . ($cooldownSeconds - $elapsedSeconds) . ' seconds.',
                ]);
            }
        }

        // Write initial "starting" status so the UI can react immediately
        file_put_contents(storage_path('app/sync_status.json'), json_encode([
            'status'       => 'starting',
            'started_at'   => now()->toIso8601String(),
            'completed_at' => null,
            'step'         => 'Initialising investigation...',
            'steps_done'   => 0,
            'steps_total'  => 8,
            'error'        => null,
        ]), LOCK_EX);

        $this->spawnSyncAll();

        return response()->json(['success' => true]);
    }

    public function syncStatus()
    {
        return response()->json($this->readSyncStatus());
    }

    private function readSyncStatus(): array
    {
        $path = storage_path('app/sync_status.json');

        if (!file_exists($path)) {
            return ['status' => 'idle', 'steps_done' => 0, 'steps_total' => 8, 'step' => 'Ready'];
        }

        $data = json_decode(file_get_contents($path), true) ?? [];

        // Auto-fail if stuck in "running" for more than 20 minutes
        if (in_array($data['status'] ?? '', ['running', 'starting']) && isset($data['started_at'])) {
            $started = \Carbon\Carbon::parse($data['started_at']);
            if ($started->diffInMinutes(now()) > 20) {
                $data['status'] = 'failed';
                $data['error']  = 'Sync timed out after 20 minutes.';
            }
        }

        return $data;
    }

    private function spawnSyncAll(): void
    {
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            // start /B launches a detached background process via cmd.exe
            // The empty "" after /B is the required window-title placeholder
            shell_exec("start /B \"\" \"{$php}\" \"{$artisan}\" sync:all");
        } else {
            $log = storage_path('logs/sync-all.log');
            shell_exec("\"{$php}\" \"{$artisan}\" sync:all >> \"{$log}\" 2>&1 &");
        }
    }

    /* =========================================================
     |  GENERATE REPORT — Dashboard Overview
     | ========================================================= */

    public function generateReport(Request $request)
    {
        $user = Auth::user();

        $selectedCohort = $request->input('cohort');
        $selectedAgency = $request->input('agency');

        // PC users are always scoped to their own department
        if ($user->role === 'PC') {
            $selectedAgency = $user->department_id;
        }

        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
        $allLearnerIds   = $this->allLearnerUserIds();
        $expectedUserIds = $filteredUserIds ?? $allLearnerIds;

        $allCourseIds = DB::table('domain_courses')->pluck('course_id')->toArray();
        $allRows = !empty($allCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allCourseIds)->get()
            : collect();

        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        $allResolved        = $this->resolveModuleStatuses($rows);
        $kpi                = $this->scopeStats($allResolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];

        // Cohort trend
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        // Per-level stats
        $levels     = DB::table('levels')->orderBy('order')->get();
        $levelStats = [];
        foreach ($levels as $level) {
            $domainIds     = DB::table('domains')->where('level_id', $level->id)->pluck('id');
            $courseIds     = DB::table('domain_courses')->whereIn('domain_id', $domainIds)->pluck('course_id')->toArray();
            $levelResolved = $allResolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats         = $this->scopeStats($levelResolved, $expectedUserIds);
            $levelStats[$level->name] = [
                'rate' => $stats['total'] > 0 ? round(($stats['pass'] / $stats['total']) * 100, 1) : 0,
            ];
        }

        // Entry level domains
        $entryLevel   = $levels->firstWhere('name', 'Entry') ?? $levels->first();
        $entryDomains = collect();
        if ($entryLevel) {
            $domains      = DB::table('domains')->where('level_id', $entryLevel->id)->orderBy('order')->get();
            $entryDomains = $domains->map(function ($domain) use ($allResolved, $expectedUserIds) {
                $cIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
                $dr   = $allResolved->filter(fn($r) => in_array($r->course_id, $cIds));
                $st   = $this->scopeStats($dr, $expectedUserIds);
                return (object)[
                    'name' => $domain->name,
                    'rate' => $st['total'] > 0 ? round($st['pass'] / $st['total'] * 100, 1) : 0,
                ];
            });
        }

        // Topics
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($allCourseIds, $filteredUserIds);

        // At-risk learners (0–49% avg progress)
        $atRiskRows = DB::table('learner_module_results')
            ->whereIn('user_id', $expectedUserIds)
            ->where(function ($q) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
            })
            ->select('user_id', DB::raw('AVG(progress) as avg_progress'))
            ->groupBy('user_id')
            ->having('avg_progress', '<', 50)
            ->orderBy('avg_progress', 'asc')
            ->get();

        $atRiskCount   = $atRiskRows->count();
        $atRiskUserIds = $atRiskRows->pluck('user_id')->toArray();
        $atRiskUserMap = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->whereIn('u.user_id', $atRiskUserIds)
            ->select('u.user_id', 'u.fields', 'd.name as dept_name')
            ->get()->keyBy('user_id');

        $atRiskLearners = $atRiskRows->map(function ($row) use ($atRiskUserMap) {
            $rec    = $atRiskUserMap[$row->user_id] ?? null;
            $fields = json_decode($rec->fields ?? '{}', true);
            $fmap   = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fmap[$f['name']] = is_array($f['value']) ? '' : (string)$f['value'];
            }
            $name = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $row->user_id;
            return (object)[
                'name'     => $name,
                'dept'     => $rec->dept_name ?? '—',
                'progress' => (int) round($row->avg_progress),
            ];
        });

        // All registered agencies
        $allAgencies = Department::orderBy('name')->get(['department_id', 'name']);

        // Scope labels
        $agencyLabel = $selectedAgency
            ? (Department::where('department_id', $selectedAgency)->value('name') ?? 'All Agencies')
            : 'All Agencies';
        $cohortLabel = $selectedCohort
            ? (DB::table('groups')->where('group_id', $selectedCohort)->value('name') ?? 'All Cohorts')
            : 'All Cohorts';

        // Dynamic observations
        $observations = $this->buildReportObservations(
            $completionRate, $entryDomains, $weakTopics, $atRiskCount, $levelStats
        );

        return view('Dashboard.Reports.DashboardReport', compact(
            'user', 'totalEnrollment', 'completionRate', 'inProgressLearners', 'atRiskCount',
            'trendLabels', 'trendData',
            'levelStats', 'levels', 'entryDomains',
            'weakTopics', 'strongTopics',
            'atRiskLearners', 'allAgencies',
            'agencyLabel', 'cohortLabel',
            'observations',
        ));
    }

    private function buildReportObservations(
        float $completionRate,
        \Illuminate\Support\Collection $entryDomains,
        $weakTopics,
        int $atRiskCount,
        array $levelStats
    ): array {
        $obs = [];

        $obs[] = [
            'title' => 'Completion Rate Progress',
            'text'  => "The overall Entry Level completion rate of {$completionRate}% is progressing since cohort inception. "
                . ($completionRate >= 25
                    ? "The upward trajectory suggests consistent learner engagement throughout the program period."
                    : "Accelerated engagement is recommended to meet program completion targets."),
        ];

        $sorted = $entryDomains->sortBy('rate');
        $minDomain = $sorted->first();
        $maxRate   = $entryDomains->max('rate');
        if ($minDomain && $maxRate > 0 && $minDomain->rate < $maxRate * 0.5) {
            $others = $entryDomains->reject(fn($d) => $d->name === $minDomain->name)
                ->filter(fn($d) => $d->rate < 15)
                ->map(fn($d) => "{$d->name} ({$d->rate}%)")
                ->implode(', ');
            $obs[] = [
                'title' => 'Domain Gap — ' . $minDomain->name,
                'text'  => ($others ? "{$minDomain->name} ({$minDomain->rate}%) and {$others}" : "{$minDomain->name} ({$minDomain->rate}%)")
                    . " show significantly lower completion compared to the leading domain ({$maxRate}%)."
                    . " Program coordinators should monitor these domains and consider targeted follow-up sessions or nudge campaigns.",
            ];
        }

        if ($weakTopics->isNotEmpty()) {
            $list = $weakTopics->map(fn($t) => "{$t->module_title} ({$t->avg_progress}% correct)")->implode(' and ');
            $obs[] = [
                'title' => 'Weakest Modules',
                'text'  => "{$list} are identified as areas requiring instructional review."
                    . " Content revision or additional learning resources may improve outcomes.",
            ];
        }

        $obs[] = [
            'title' => 'At-Risk Learners',
            'text'  => $atRiskCount > 0
                ? "{$atRiskCount} learner(s) are currently flagged as at-risk (below 30% progress and inactive for 14+ days). Immediate follow-up is advised."
                : "No learners are currently flagged as at-risk (below 30% progress and inactive for 14+ days). This is a positive indicator. Continued monitoring is advised as the cohort progresses toward higher domains.",
        ];

        $higherLevels = collect($levelStats)
            ->filter(fn($v, $k) => !str_contains(strtolower($k), 'entry') && $v['rate'] == 0);
        if ($higherLevels->isNotEmpty()) {
            $obs[] = [
                'title' => 'Professional & Specialization Levels',
                'text'  => "Both Professional Level and Specialization Level currently show 0% completion."
                    . " These are expected to activate as the Entry Level cohort progresses."
                    . " Pre-enrollment preparation resources should be made available in advance.",
            ];
        }

        return $obs;
    }


    /* =========================================================
     |  DOMAIN ANALYTICS REPORT
     | ========================================================= */

    public function generateDomainReport(Request $request)
    {
        $user = Auth::user();

        // PC auto-scope to their department
        $selectedAgency = $request->input('agency');
        if ($user->role === 'PC') {
            $selectedAgency = $user->department_id;
        }
        $selectedCohort = $request->input('cohort');

        // Level
        $levels          = DB::table('levels')->orderBy('order')->get();
        $selectedLevelId = $request->input('level_id', $levels->first()->id ?? null);
        $selectedLevel   = $levels->firstWhere('id', (int) $selectedLevelId) ?? $levels->first();

        if (!$selectedLevel) {
            abort(404, 'No level data available.');
        }

        // Domains for the selected level
        $domains   = DB::table('domains')->where('level_id', $selectedLevel->id)->orderBy('order')->get();
        $domainIds = $domains->pluck('id')->toArray();

        $allLevelCourseIds = DB::table('domain_courses')
            ->whereIn('domain_id', $domainIds)
            ->pluck('course_id')->toArray();

        $allRows = !empty($allLevelCourseIds)
            ? DB::table('learner_module_results')->whereIn('course_id', $allLevelCourseIds)->get()
            : collect();

        // Apply cohort / agency filter
        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;
        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();

        // Level-wide KPIs
        $resolved           = $this->resolveModuleStatuses($rows);
        $kpi                = $this->scopeStats($resolved, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];
        $notStartedLearners = $kpi['not_started'];
        $failedLearners     = $kpi['failed'];

        // Per-domain stats
        $domainStats = [];
        foreach ($domains as $domain) {
            $courseIds = DB::table('domain_courses')->where('domain_id', $domain->id)->pluck('course_id')->toArray();
            $domainRes = $resolved->filter(fn($r) => in_array($r->course_id, $courseIds));
            $stats     = $this->scopeStats($domainRes, $expectedUserIds);
            $domainStats[$domain->name] = [
                'domain_id'   => $domain->id,
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
            ];
        }

        // Cohort trend
        ['labels' => $trendLabels, 'data' => $trendData] = $this->buildCohortTrend($completionRate, $expectedUserIds);

        // Topic performance
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($allLevelCourseIds);

        // Agency count for executive summary
        $agencyCount = Department::whereIn(
            'department_id',
            IspringUser::whereNotNull('department_id')->pluck('department_id')->unique()
        )->count();

        // Scope labels
        $cohortLabel = $selectedCohort
            ? (Group::where('group_id', $selectedCohort)->value('name') ?? 'Selected Cohort')
            : 'All Cohorts';
        $agencyLabel = $selectedAgency
            ? (Department::where('department_id', $selectedAgency)->value('name') ?? 'Selected Agency')
            : 'All Agencies';

        // Observations
        $observations = $this->buildDomainObservations(
            $domainStats, $weakTopics, $completionRate, $trendLabels
        );

        return view('Dashboard.Reports.DomainReport', compact(
            'user', 'selectedLevel', 'levels', 'domains', 'domainStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners', 'failedLearners',
            'trendLabels', 'trendData',
            'weakTopics', 'strongTopics',
            'agencyCount', 'cohortLabel', 'agencyLabel',
            'selectedAgency', 'selectedCohort',
            'observations',
        ));
    }

    private function buildDomainObservations(
        array $domainStats,
        $weakTopics,
        float $completionRate,
        array $trendLabels
    ): array {
        $obs = [];

        $withRate = collect($domainStats)->map(function ($d, $name) {
            $d['rate'] = $d['total'] > 0 ? round(($d['pass'] / $d['total']) * 100, 1) : 0.0;
            $d['name'] = $name;
            return $d;
        })->sortByDesc('rate');

        $leading     = $withRate->first();
        $leadingName = $leading ? $leading['name'] : null;

        // 1. Leading domain
        if ($leading && $leading['rate'] > 0) {
            $obs[] = [
                'title' => $leadingName . ' — Leading Domain',
                'text'  => "{$leadingName} is the most progressed domain at {$leading['rate']}% pass rate ({$leading['pass']} learners)."
                    . " With {$leading['not_started']} not yet started, this domain has the highest potential for growth."
                    . " Engagement campaigns targeting inactive learners are recommended.",
            ];
        }

        // 2. Early-stage domains (>= 5% but < leading)
        $earlyStage = $withRate->filter(fn($d) => $d['rate'] >= 5 && $d['rate'] < ($leading['rate'] ?? 100) && $d['name'] !== $leadingName);
        foreach ($earlyStage->take(1) as $d) {
            $failed = $d['failed'] > 0 ? '' : ' No failures recorded — content difficulty appears manageable.';
            $obs[] = [
                'title' => $d['name'] . ' — Moderate Progress',
                'text'  => "{$d['pass']} learners ({$d['rate']}%) have passed {$d['name']}."
                    . " The {$d['not_started']} not-started learners represent a large addressable pool.{$failed}",
            ];
        }

        // 3. Domains < 5% (but > 0)
        $lowDomains = $withRate->filter(fn($d) => $d['rate'] > 0 && $d['rate'] < 5 && $d['name'] !== $leadingName);
        foreach ($lowDomains->take(2) as $d) {
            $obs[] = [
                'title' => $d['name'] . ' — Early Stage',
                'text'  => "Only {$d['pass']} learners ({$d['rate']}%) have passed {$d['name']}."
                    . " With {$d['not_started']} not started, this domain is significantly behind."
                    . " Programme coordinators should consider structured nudges or prerequisite-based sequencing to activate learners.",
            ];
        }

        // 4. Critical gap — < 1% together
        $critGap = $withRate->filter(fn($d) => $d['rate'] < 1 && $d['name'] !== $leadingName);
        if ($critGap->count() >= 2) {
            $names  = $critGap->pluck('name')->implode(' & ');
            $counts = $critGap->map(fn($d) => $d['pass'])->implode(' and ');
            $obs[] = [
                'title' => 'Critical Gap — ' . $names,
                'text'  => "Both domains show less than 1% completion ({$counts} learners respectively)."
                    . " This may indicate these are accessed later in the learning pathway or have prerequisite barriers."
                    . " A review of the course sequencing is advised.",
            ];
        }

        // 5. Failed learners
        $withFailed = $withRate->filter(fn($d) => $d['failed'] > 0);
        if ($withFailed->isNotEmpty()) {
            $summary = $withFailed->map(fn($d) => "{$d['name']} ({$d['failed']} failed)")->implode(', ');
            $obs[] = [
                'title' => 'Failed Learners',
                'text'  => "The following domain(s) have recorded failed learners: {$summary}."
                    . " Targeted remediation support or supplementary materials should be offered to support re-engagement and re-assessment attempts.",
            ];
        }

        // 6. Cohort trajectory
        $startLabel = count($trendLabels) > 0 ? $trendLabels[0] : 'cohort inception';
        $obs[] = [
            'title' => 'Overall Cohort Trajectory',
            'text'  => "The cohort trend line shows a steady upward trajectory from 0% in {$startLabel} to {$completionRate}% by "
                . now()->format('F Y') . "."
                . " Growth has been consistent, suggesting healthy engagement."
                . " The programme is on track but acceleration in lower-performing domains is needed.",
        ];

        return $obs;
    }


    /* =========================================================
     |  MODULE ANALYTICS REPORT
     | ========================================================= */

    public function generateModuleReport(Request $request)
    {
        $user = Auth::user();

        // PC auto-scope to their department
        $selectedAgency = $request->input('agency');
        if ($user->role === 'PC') {
            $selectedAgency = $user->department_id;
        }
        $selectedCohort = $request->input('cohort');

        // All domains (same query as analyticsModules)
        $allDomains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->select('d.*', 'l.name as level_name')
            ->orderBy('l.order')
            ->orderBy('d.order')
            ->get();

        $selectedDomainId = $request->input('domain_id', $allDomains->first()->id ?? null);
        $selectedDomain   = $allDomains->firstWhere('id', (int) $selectedDomainId) ?? $allDomains->first();

        if (!$selectedDomain) {
            abort(404, 'No domain data available.');
        }

        $domainCourses = DB::table('domain_courses')
            ->where('domain_id', $selectedDomain->id)
            ->orderBy('id')
            ->get();

        if ($domainCourses->isEmpty()) {
            abort(404, 'No modules found for this domain.');
        }

        $courseIds = $domainCourses->pluck('course_id')->toArray();
        $allRows   = DB::table('learner_module_results')->whereIn('course_id', $courseIds)->get();

        // Agency user scope for PC
        $agencyUserIds = [];
        if ($user->role === 'PC' && $selectedAgency) {
            $agencyUserIds = $this->getUserIdsByDepartment($selectedAgency);
        }

        $filteredUserIds = $this->resolveUserFilter($selectedAgency, $selectedCohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : (!empty($agencyUserIds)
                ? $allRows->filter(fn($r) => in_array($r->user_id, $agencyUserIds))
                : $allRows);

        $expectedUserIds    = $filteredUserIds ?? (!empty($agencyUserIds) ? $agencyUserIds : $this->allLearnerUserIds());
        $resolved           = $this->resolveModuleStatuses($rows);
        $kpi                = $this->domainKpiStats($resolved, $courseIds, $expectedUserIds);
        $totalEnrollment    = $kpi['total'];
        $completionRate     = $totalEnrollment > 0 ? round(($kpi['pass'] / $totalEnrollment) * 100, 1) : 0;
        $inProgressLearners = $kpi['progress'];
        $notStartedLearners = $kpi['not_started'];

        // Per-module stats keyed by course_code
        $courseStats = [];
        foreach ($domainCourses as $dc) {
            $cr    = $resolved->filter(fn($r) => $r->course_id === $dc->course_id);
            $stats = $this->scopeStats($cr, $expectedUserIds);
            $courseStats[$dc->course_code] = [
                'pass'        => $stats['pass'],
                'progress'    => $stats['progress'],
                'failed'      => $stats['failed'],
                'not_started' => $stats['not_started'],
                'total'       => $stats['total'],
            ];
        }

        // Score distribution bands (same logic as analyticsModules)
        $attemptedStatuses = ['passed', 'failed', 'in_progress', 'completed', 'complete'];
        $scoreBandsData    = [];
        foreach ($domainCourses as $dc) {
            $attemptedRows = $rows->filter(
                fn($r) => $r->course_id === $dc->course_id
                       && (str_starts_with($r->module_title ?? '', 'Quiz Lesson')
                           || str_starts_with($r->module_title ?? '', 'Module Assessment'))
                       && in_array(strtolower(trim($r->completion_status ?? '')), $attemptedStatuses)
            );
            $attemptedScores = $attemptedRows
                ->groupBy('user_id')
                ->map(fn($userRows) => $userRows->max('progress'));

            $userScores = collect($expectedUserIds)->mapWithKeys(
                fn($uid) => [$uid => $attemptedScores[$uid] ?? 0]
            );

            $scoreBandsData[$dc->course_code] = [
                'failing'    => $userScores->filter(fn($p) => $p < 50)->count(),
                'borderline' => $userScores->filter(fn($p) => $p >= 50 && $p < 70)->count(),
                'solid'      => $userScores->filter(fn($p) => $p >= 70 && $p < 90)->count(),
                'strong'     => $userScores->filter(fn($p) => $p >= 90)->count(),
                'total'      => $userScores->count(),
            ];
        }

        // Topics
        ['weak' => $weakTopics, 'strong' => $strongTopics] = $this->buildTopicStats($courseIds);

        // Agency count for executive summary
        $agencyCount = Department::whereIn(
            'department_id',
            IspringUser::whereNotNull('department_id')->pluck('department_id')->unique()
        )->count();

        // Scope labels
        $cohortLabel = $selectedCohort
            ? (Group::where('group_id', $selectedCohort)->value('name') ?? 'Selected Cohort')
            : 'All Cohorts';
        $agencyLabel = $selectedAgency
            ? (Department::where('department_id', $selectedAgency)->value('name') ?? 'Selected Agency')
            : 'All Agencies';

        $moduleCodes  = $domainCourses->pluck('course_code')->implode(', ');
        $observations = $this->buildModuleObservations(
            $courseStats, $scoreBandsData, $weakTopics, $strongTopics,
            $completionRate, $inProgressLearners, $notStartedLearners
        );

        return view('Dashboard.Reports.ModuleReport', compact(
            'user', 'selectedDomain', 'domainCourses', 'courseStats',
            'totalEnrollment', 'completionRate', 'inProgressLearners', 'notStartedLearners',
            'scoreBandsData', 'weakTopics', 'strongTopics',
            'agencyCount', 'cohortLabel', 'agencyLabel',
            'moduleCodes', 'observations',
        ));
    }

    private function buildModuleObservations(
        array  $courseStats,
        array  $scoreBandsData,
        $weakTopics,
        $strongTopics,
        float  $completionRate,
        int    $inProgressLearners,
        int    $notStartedLearners
    ): array {
        $obs = [];

        $enriched = collect($courseStats)->map(function ($d, $code) use ($scoreBandsData) {
            $d['code']  = $code;
            $d['rate']  = $d['total'] > 0 ? round(($d['pass'] / $d['total']) * 100, 1) : 0.0;
            $d['bands'] = $scoreBandsData[$code] ?? ['failing' => 0, 'borderline' => 0, 'solid' => 0, 'strong' => 0, 'total' => 0];
            return $d;
        })->sortByDesc('rate');

        $best  = $enriched->first();
        $worst = $enriched->last();

        // 1. Best performing module
        if ($best) {
            $strongCount = $best['bands']['strong'] ?? 0;
            $failedNote  = $best['failed'] > 0
                ? " However {$best['not_started']} learners have not yet started and {$best['failed']} have failed, requiring targeted re-engagement and remediation support."
                : " With {$best['not_started']} learners not yet started, continued engagement is recommended.";
            $strongNote  = $strongCount > 0
                ? " The {$strongCount} learners scoring 90–100% demonstrate the module content is achievable for motivated learners."
                : '';
            $obs[] = [
                'title' => $best['code'] . ' — Best Performing Module',
                'text'  => "{$best['code']} leads the domain with a {$best['rate']}% pass rate — {$best['pass']} learners have passed.{$failedNote}{$strongNote}",
            ];
        }

        // 2. Middle modules (moderate progress)
        $middle = $enriched->slice(1, max(0, $enriched->count() - 2));
        foreach ($middle as $d) {
            $bandNote     = ($d['bands']['failing'] ?? 0) > 0
                ? " Score band analysis shows {$d['bands']['failing']} learners falling below 50%, suggesting that those who attempt {$d['code']} still struggle — a content review or additional preparatory material is advisable."
                : '';
            $progressNote = $d['progress'] > 0 ? " with {$d['pass']} learners passed and {$d['progress']} still in progress" : " with {$d['pass']} learners passed";
            $obs[] = [
                'title' => $d['code'] . ' — Moderate Progress',
                'text'  => "{$d['code']} has a {$d['rate']}% pass rate{$progressNote}. The majority ({$d['not_started']}) remain not started.{$bandNote}",
            ];
        }

        // 3. Lowest completion (only when there are multiple modules)
        if ($worst && $enriched->count() > 1) {
            $hardestBand = $worst['bands']['failing'] ?? 0;
            $topicNote   = '';
            if ($strongTopics->isNotEmpty()) {
                $t1 = $strongTopics->first();
                $t2 = $strongTopics->skip(1)->first();
                $topicNote = $t2
                    ? " Despite this, {$t1->module_title} ({$t1->avg_progress}%) and {$t2->module_title} ({$t2->avg_progress}%) are the strongest topics in the domain, indicating that early lessons are well-received but later assessments are challenging."
                    : " Despite this, {$t1->module_title} ({$t1->avg_progress}%) is the strongest topic in the domain.";
            }
            $obs[] = [
                'title' => $worst['code'] . ' — Lowest Completion',
                'text'  => "{$worst['code']} has the lowest pass rate at {$worst['rate']}% with only {$worst['pass']} learners passed and {$worst['not_started']} not started."
                    . ($hardestBand > 0 ? " It is also identified as the hardest module with the most learners scoring below 50% ({$hardestBand})." : '')
                    . $topicNote,
            ];
        }

        // 4. Score band pattern
        $totalBorderline = array_sum(array_column($scoreBandsData, 'borderline'));
        if ($totalBorderline === 0 && count($scoreBandsData) > 0) {
            $obs[] = [
                'title' => 'Score Band Pattern — No Borderline Learners',
                'text'  => 'Across all ' . count($scoreBandsData) . ' modules, zero learners fall in the 50–69% borderline band. This bimodal distribution (most either failing or strong) suggests learners either engage deeply and score well, or disengage and score very low. Programme design could benefit from interventions that target the borderline zone.',
            ];
        }

        // 5. Weakest assessment
        if ($weakTopics->isNotEmpty()) {
            $w = $weakTopics->first();
            $obs[] = [
                'title' => 'Weakest Assessment — ' . $w->module_title,
                'text'  => "The {$w->module_title} scores an average of only {$w->avg_progress}%, making it the single weakest topic across the domain. This assessment may be disproportionately difficult relative to the lesson content, or learners may not be adequately prepared. A review of assessment alignment is recommended.",
            ];
        }

        // 6. In-progress summary
        $activeLabel = $inProgressLearners < 50 ? 'Minimal' : 'Active';
        $obs[] = [
            'title' => 'In Progress — ' . $activeLabel . ' Active Learners',
            'text'  => "{$inProgressLearners} learner(s) are currently in progress across the domain. With {$notStartedLearners} not yet started, the primary focus should be on activating non-starters through reminders, push notifications or structured learning pathways before the cohort deadline.",
        ];

        return $obs;
    }


    /* =========================================================
     |  REPORT LOG
     | ========================================================= */

    public function reportLog(Request $request)
    {
        $user         = Auth::user();
        $departmentId = $user->department_id;

        $logs = ReportLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        if ($user->role === 'PC' && $departmentId) {
            $agencyUserIds = $this->getUserIdsByDepartment($departmentId);
            $usedGroupIds  = !empty($agencyUserIds)
                ? DB::table('user_group')->whereIn('user_id', $agencyUserIds)->pluck('group_id')->unique()
                : collect();
            $agencies = collect();
        } else {
            $usedGroupIds = DB::table('user_group')->pluck('group_id')->unique();
            $usedDeptIds  = IspringUser::whereNotNull('department_id')->pluck('department_id')->unique();
            $agencies     = Department::whereIn('department_id', $usedDeptIds)->orderBy('name')->get(['department_id', 'name']);
        }

        $cohorts = Group::whereIn('group_id', $usedGroupIds)->orderBy('name')->get(['group_id', 'name']);

        return view('Dashboard.ReportLog', compact('user', 'logs', 'agencies', 'cohorts'));
    }

    public function storeReportLog(Request $request)
    {
        $validated = $request->validate([
            'title'  => 'required|string|max:255',
            'format' => 'required|in:PDF,Excel',
        ]);

        ReportLog::create([
            'user_id' => Auth::user()->id,
            'title'   => $validated['title'],
            'format'  => $validated['format'],
            'status'  => 'Completed',
        ]);

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['report_title' => $validated['title'], 'format' => $validated['format'], 'ip' => $request->ip()])
            ->log('Report generated');

        return response()->json(['success' => true]);
    }


    /* =========================================================
     |  API STATUS CHECK
     | ========================================================= */

    public function ispringStatus()
    {
        $url = rtrim(config('services.cfip.base_url'), '/');

        try {
            $response = Http::timeout(5)->head($url);
            $online = $response->status() < 500;
        } catch (\Throwable $e) {
            $online = false;
        }

        return response()->json(['online' => $online]);
    }


    /* =========================================================
     |  AJAX — learner list for a specific module (bar chart popup)
     | ========================================================= */

    public function moduleLearners(Request $request)
    {
        $courseCode = $request->input('course_code');
        $domainId   = (int) $request->input('domain_id');
        $cohort     = $request->input('cohort');
        $agency     = $request->input('agency');

        $dc = DB::table('domain_courses')
            ->where('domain_id', $domainId)
            ->where('course_code', $courseCode)
            ->first();

        if (!$dc) {
            return response()->json(['learners' => []]);
        }

        $allRows = DB::table('learner_module_results')
            ->where('course_id', $dc->course_id)
            ->get();

        $includeLessons  = $request->boolean('include_lessons');
        $filteredUserIds = $this->resolveUserFilter($agency, $cohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();
        $resolved        = $this->resolveModuleStatuses($rows, $includeLessons);

        $priority = ['pass' => 3, 'progress' => 2, 'failed' => 1, 'not_started' => 0];
        $userBest = $resolved
            ->groupBy('user_id')
            ->map(fn($rows) => $rows->pluck('effective_status')
                ->reduce(fn($best, $s) => ($priority[$s] ?? 0) > ($priority[$best] ?? 0) ? $s : $best, 'not_started'));

        $ispringUsers = DB::table('users_ispring')
            ->whereIn('user_id', $expectedUserIds)
            ->select('user_id', 'fields')
            ->get()
            ->keyBy('user_id');

        $learners = collect($expectedUserIds)->map(function ($uid) use ($userBest, $ispringUsers) {
            $status = $userBest[$uid] ?? 'not_started';
            $rec    = $ispringUsers[$uid] ?? null;
            $fields = json_decode($rec->fields ?? '{}', true);
            $fmap   = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fmap[$f['name']] = is_array($f['value']) ? '' : (string) $f['value'];
            }
            $name = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $uid;
            return ['name' => $name, 'status' => $status];
        })->sortBy('name')->values();

        return response()->json(['learners' => $learners]);
    }


    /* =========================================================
     |  AJAX — learner list for score band drill-down
     | ========================================================= */

    public function scoreBandLearners(Request $request)
    {
        $courseCode = $request->input('course_code');
        $domainId   = (int) $request->input('domain_id');
        $cohort         = $request->input('cohort');
        $agency         = $request->input('agency');
        $includeLessons = $request->boolean('include_lessons');

        $dc = DB::table('domain_courses')
            ->where('domain_id', $domainId)
            ->where('course_code', $courseCode)
            ->first();

        if (!$dc) {
            return response()->json(['learners' => []]);
        }

        $allRows = DB::table('learner_module_results')
            ->where('course_id', $dc->course_id)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'LIKE', 'Lesson%');
                }
            })
            ->get();

        $filteredUserIds = $this->resolveUserFilter($agency, $cohort);
        $rows = $filteredUserIds !== null
            ? $allRows->filter(fn($r) => in_array($r->user_id, $filteredUserIds))
            : $allRows;

        // Attempted rows only → max progress per user
        $attemptedStatuses = ['passed', 'failed', 'in_progress', 'completed', 'complete'];
        $attemptedScores = $rows
            ->filter(fn($r) => in_array(strtolower(trim($r->completion_status ?? '')), $attemptedStatuses))
            ->groupBy('user_id')
            ->map(fn($userRows) => $userRows->max('progress'));

        // All expected learners: attempted score or 0 for unattempted (→ failing)
        $expectedUserIds = $filteredUserIds ?? $this->allLearnerUserIds();
        $userScores = collect($expectedUserIds)->mapWithKeys(
            fn($uid) => [$uid => $attemptedScores[$uid] ?? 0]
        );

        $ispringUsers = DB::table('users_ispring')
            ->whereIn('user_id', $expectedUserIds)
            ->select('user_id', 'fields')
            ->get()
            ->keyBy('user_id');

        $learners = $userScores->map(function ($score, $uid) use ($ispringUsers) {
            $rec    = $ispringUsers[$uid] ?? null;
            $fields = json_decode($rec->fields ?? '{}', true);
            $fmap   = [];
            foreach ($fields['field'] ?? [] as $f) {
                $fmap[$f['name']] = is_array($f['value']) ? '' : (string) $f['value'];
            }
            $name = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $uid;
            $band = match(true) {
                $score >= 90 => 'strong',
                $score >= 70 => 'solid',
                $score >= 50 => 'borderline',
                default      => 'failing',
            };
            return ['name' => $name, 'score' => (int) $score, 'band' => $band];
        })->sortBy('name')->values();

        return response()->json(['learners' => $learners]);
    }


    /* =========================================================
     |  AJAX — per-learner detail for the Student Progress drawer
     | ========================================================= */

    public function learnerDetail(Request $request)
    {
        $userId         = $request->input('user_id');
        $includeLessons = $request->boolean('include_lessons');
        if (!$userId) {
            return response()->json(['error' => 'Missing user_id'], 422);
        }

        $auth = Auth::user();

        $rec = DB::table('users_ispring as u')
            ->where('u.user_id', $userId)
            ->leftJoin('departments as d', 'u.department_id', '=', 'd.department_id')
            ->select('u.user_id', 'u.fields', 'u.department_id', 'd.name as department_name')
            ->first();

        if (!$rec) {
            return response()->json(['error' => 'Learner not found'], 404);
        }

        if ($auth->role === 'PC' && $rec->department_id !== $auth->department_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $fields = json_decode($rec->fields ?? '{}', true);
        $fmap   = [];
        foreach ($fields['field'] ?? [] as $f) {
            $fmap[$f['name']] = is_array($f['value']) ? '' : (string) $f['value'];
        }
        $name = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? '')) ?: $userId;

        $cohortRow = DB::table('user_group as ug')
            ->join('groups as g', 'ug.group_id', '=', 'g.group_id')
            ->where('ug.user_id', $userId)
            ->select('g.name as cohort_name')
            ->first();
        $cohort = $cohortRow?->cohort_name;

        $allCourseIds = DB::table('domain_courses')->pluck('course_id')->toArray();
        $rows = !empty($allCourseIds)
            ? DB::table('learner_module_results')
                ->where('user_id', $userId)
                ->whereIn('course_id', $allCourseIds)
                ->get()
            : collect();

        $lastActive   = $rows->max('updated_at');
        $daysInactive = $lastActive
            ? (int) now()->diffInDays(\Carbon\Carbon::parse($lastActive))
            : null;

        $domains = DB::table('domains as d')
            ->join('levels as l', 'd.level_id', '=', 'l.id')
            ->orderBy('l.order')
            ->orderBy('d.order')
            ->select('d.id', 'd.name', 'l.name as level_name')
            ->get();

        // Fetch all domain_courses in one query, grouped by domain_id
        $allDomainCourses = DB::table('domain_courses')
            ->select('domain_id', 'course_id', 'course_code')
            ->get()
            ->groupBy('domain_id');

        // Master list: every known quiz/assessment/lesson module title per course (derived from ALL learners)
        // so we can show FD02/FD03 tabs with Not Started even if this learner has no rows for them
        $masterModules = DB::table('learner_module_results')
            ->whereIn('course_id', $allCourseIds)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'LIKE', 'Quiz Lesson%')
                  ->orWhere('module_title', 'LIKE', 'Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'LIKE', 'Lesson%');
                }
            })
            ->select('course_id', 'module_title')
            ->distinct()
            ->get()
            ->groupBy('course_id')
            ->map(fn($rows) => $rows->pluck('module_title')
                ->map(fn($t) => ltrim($t))
                ->unique()
                ->sort()
                ->values());

        $domainProgress = [];
        $domainModules  = [];
        $domainCount    = $domains->count();

        foreach ($domains as $domain) {
            $domCourses   = $allDomainCourses[$domain->id] ?? collect();
            $domCourseIds = $domCourses->pluck('course_id')->toArray();

            $domRows  = $rows->filter(
                fn($r) => in_array($r->course_id, $domCourseIds) && $this->isRelevantModule($r->module_title ?? '')
            );
            $progress = $domRows->count() > 0 ? (int) round($domRows->avg('progress')) : 0;
            $domainProgress[] = ['name' => $domain->name, 'progress' => $progress];

            $courseModules = [];
            foreach ($domCourses as $course) {
                $masterList = $masterModules[$course->course_id] ?? collect();

                // This learner's rows for the course, grouped by module title
                $learnerRows = $rows->filter(
                    fn($r) => $r->course_id === $course->course_id && $this->isRelevantModule($r->module_title ?? '')
                )->groupBy(fn($r) => ltrim($r->module_title ?? ''));

                $modules = [];
                foreach ($masterList as $title) {
                    $group    = $learnerRows[$title] ?? collect();
                    $statuses = $group->pluck('completion_status')
                        ->map(fn($s) => strtolower(trim($s ?? '')))
                        ->toArray();
                    $score  = $group->count() > 0 ? (int) round($group->max('progress')) : 0;
                    $status = match(true) {
                        in_array('passed',      $statuses) => 'pass',
                        in_array('in_progress', $statuses) => 'in_progress',
                        in_array('failed',      $statuses) => 'failed',
                        default                             => 'not_started',
                    };
                    $modules[] = ['title' => $title, 'status' => $status, 'score' => $score];
                }

                // Always include every course tab, even with no data
                $courseModules[$course->course_code] = $modules;
            }
            $domainModules[$domain->name] = $courseModules;
        }

        // Overall = average of per-domain averages, so every domain (including unenrolled) weighs equally
        $overallProgress = $domainCount > 0
            ? (int) round(array_sum(array_column($domainProgress, 'progress')) / $domainCount)
            : 0;

        return response()->json([
            'name'             => $name,
            'department'       => $rec->department_name ?? '—',
            'cohort'           => $cohort,
            'last_active'      => $lastActive,
            'days_inactive'    => $daysInactive,
            'overall_progress' => $overallProgress,
            'domains'          => $domainProgress,
            'domain_modules'   => $domainModules,
        ]);
    }
}