<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CourseStructureController extends Controller
{
    public function index()
    {
        // Source: learner_module_results — shows every module that actually exists in the system
        // (the modules table misses courses like SC01 that weren't fully synced).
        $rows = DB::table('learner_module_results as lmr')
            ->leftJoin('domain_courses as dc', 'dc.course_id', '=', 'lmr.course_id')
            ->select(
                'lmr.course_id',
                'dc.course_code',
                'lmr.module_title',
                DB::raw('MIN(lmr.module_id) as module_id'),
                DB::raw('COUNT(DISTINCT lmr.user_id) as learner_count'),
                DB::raw('MIN(lmr.access_date) as first_seen')
            )
            ->groupBy('lmr.course_id', 'lmr.module_title', 'dc.course_code')
            ->orderBy('dc.course_code')
            ->orderBy('lmr.module_title')
            ->get();

        $courses = $rows
            ->groupBy('course_id')
            ->map(function ($moduleRows) {
                $first   = $moduleRows->first();
                $modules = $moduleRows->map(fn($r) => [
                    'module_id'     => $r->module_id,
                    'module_title'  => $r->module_title,
                    'learner_count' => $r->learner_count,
                    'first_seen'    => $r->first_seen,
                ])->values();

                return [
                    'course_id'    => $first->course_id,
                    'course_code'  => $first->course_code ?? '(no code)',
                    'module_count' => $modules->count(),
                    'modules'      => $modules,
                ];
            })
            ->sortBy('course_code')
            ->values();

        return view('dev.course-structure', compact('courses'));
    }

    public function learnerResults(Request $request)
    {
        // ── Dropdowns ──────────────────────────────────────────────
        $cohorts = DB::table('user_group as ug')
            ->join('groups as g', 'g.group_id', '=', 'ug.group_id')
            ->distinct()
            ->select('g.group_id', 'g.name')
            ->orderBy('g.name')
            ->get();

        $agencies = DB::table('departments')
            ->orderBy('name')
            ->get(['department_id', 'name']);

        $domains = DB::table('domains as d')
            ->join('levels as l', 'l.id', '=', 'd.level_id')
            ->select('d.id', 'd.name', 'l.name as level_name')
            ->orderBy('l.order')
            ->orderBy('d.order')
            ->get();

        $selectedCohort   = $request->input('cohort');
        $selectedAgency   = $request->input('agency');
        $selectedDomainId = $request->input('domain_id');

        $courseGroups  = collect();
        $learners      = collect();
        $totalLearners = 0;

        if ($selectedCohort) {

            // ── User scope ─────────────────────────────────────────
            $cohortUserIds = DB::table('user_group')
                ->where('group_id', $selectedCohort)
                ->pluck('user_id')
                ->toArray();

            if ($selectedAgency) {
                $agencyUserIds = DB::table('users_ispring')
                    ->where('department_id', $selectedAgency)
                    ->pluck('user_id')
                    ->toArray();
                $cohortUserIds = array_values(array_intersect($cohortUserIds, $agencyUserIds));
            }

            $totalLearners = count($cohortUserIds);

            // ── Course scope ───────────────────────────────────────
            $domainCourses = DB::table('domain_courses')
                ->when($selectedDomainId, fn($q) => $q->where('domain_id', $selectedDomainId))
                ->orderBy('id')
                ->get(['course_id', 'course_code']);

            $courseIds = $domainCourses->pluck('course_id')->toArray();

            // ── Fetch Quiz Lesson + Module Assessment rows ─────────
            $rows = (empty($cohortUserIds) || empty($courseIds)) ? collect()
                : DB::table('learner_module_results as lmr')
                    ->join('domain_courses as dc', 'dc.course_id', '=', 'lmr.course_id')
                    ->whereIn('lmr.user_id', $cohortUserIds)
                    ->whereIn('lmr.course_id', $courseIds)
                    ->where(function ($q) {
                        $q->where('lmr.module_title', 'LIKE', 'Quiz Lesson%')
                          ->orWhere('lmr.module_title', 'LIKE', 'Module Assessment%');
                    })
                    ->select('lmr.user_id', 'lmr.module_title', 'lmr.completion_status', 'dc.course_code')
                    ->get();

            // ── Course → sorted module list ────────────────────────
            $courseGroups = $domainCourses->map(function ($dc) use ($rows) {
                $modules = $rows
                    ->where('course_code', $dc->course_code)
                    ->pluck('module_title')
                    ->unique()
                    ->sortBy(function ($title) {
                        if (preg_match('/Quiz Lesson\s+(\d+)/i', $title, $m)) return (int) $m[1];
                        return 999; // Module Assessment sorts last
                    })
                    ->values();

                return (object) [
                    'course_code' => $dc->course_code,
                    'modules'     => $modules,
                ];
            })->filter(fn($cg) => $cg->modules->isNotEmpty())->values();

            // ── Pivot: user → "code|title" → {status, attempts} ──
            $priority = ['passed' => 4, 'in_progress' => 3, 'failed' => 2, 'not_started' => 1];
            $pivot    = [];

            foreach ($rows as $row) {
                $uid    = $row->user_id;
                $key    = $row->course_code . '|' . ltrim($row->module_title);
                $raw    = strtolower(trim($row->completion_status ?? ''));
                $status = match (true) {
                    in_array($raw, ['passed', 'complete', 'completed']) => 'passed',
                    $raw === 'in_progress'                              => 'in_progress',
                    $raw === 'failed'                                   => 'failed',
                    default                                             => 'not_started',
                };

                if (!isset($pivot[$uid][$key])) {
                    $pivot[$uid][$key] = ['status' => $status, 'attempts' => 1];
                } else {
                    $pivot[$uid][$key]['attempts']++;
                    if (($priority[$status] ?? 0) > ($priority[$pivot[$uid][$key]['status']] ?? 0)) {
                        $pivot[$uid][$key]['status'] = $status;
                    }
                }
            }

            // ── Learner names from users_ispring ───────────────────
            $userInfos = empty($cohortUserIds) ? collect()
                : DB::table('users_ispring as u')
                    ->leftJoin('departments as d', 'd.department_id', '=', 'u.department_id')
                    ->whereIn('u.user_id', $cohortUserIds)
                    ->select('u.user_id', 'u.fields', 'd.name as dept_name')
                    ->get()
                    ->map(function ($u) {
                        $fields = json_decode($u->fields ?? '{}', true);
                        $fmap   = [];
                        foreach ($fields['field'] ?? [] as $f) {
                            $fmap[$f['name']] = is_array($f['value']) ? '' : (string) $f['value'];
                        }
                        $u->full_name = trim(($fmap['FIRST_NAME'] ?? '') . ' ' . ($fmap['LAST_NAME'] ?? ''))
                            ?: $u->user_id;
                        return $u;
                    })
                    ->keyBy('user_id');

            // ── All cohort users, sorted by name (not-started = no data yet) ──
            $learners = collect($cohortUserIds)
                ->map(function ($uid) use ($userInfos, $pivot, $courseGroups) {
                    $info  = $userInfos[$uid] ?? null;
                    $cells = [];
                    foreach ($courseGroups as $cg) {
                        foreach ($cg->modules as $mod) {
                            $key         = $cg->course_code . '|' . $mod;
                            $cells[$key] = $pivot[$uid][$key] ?? ['status' => 'not_started', 'attempts' => 0];
                        }
                    }
                    return (object) [
                        'user_id'   => $uid,
                        'full_name' => $info->full_name ?? $uid,
                        'dept_name' => $info->dept_name ?? '—',
                        'cells'     => $cells,
                    ];
                })
                ->sortBy('full_name')
                ->values();
        }

        return view('dev.learner-results', compact(
            'cohorts', 'agencies', 'domains',
            'selectedCohort', 'selectedAgency', 'selectedDomainId',
            'courseGroups', 'learners', 'totalLearners',
        ));
    }

    public function dataSummary()
    {
        $lmr = 'learner_module_results';

        // ── Top-level counts ──────────────────────────────────────
        $totalRows          = DB::table($lmr)->count();
        $distinctLearners   = DB::table($lmr)->distinct()->count('user_id');
        $distinctCourses    = DB::table($lmr)->distinct()->count('course_id');
        $distinctEnrollments = DB::table($lmr)->distinct()->count('enrollment_id');

        // ── Completion status breakdown ───────────────────────────
        $statusBreakdown = DB::table($lmr)
            ->select('completion_status', DB::raw('count(*) as total'))
            ->groupBy('completion_status')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($totalRows) {
                $row->pct = $totalRows > 0
                    ? round(($row->total / $totalRows) * 100, 1)
                    : 0;
                return $row;
            });

        // ── Per-course breakdown ──────────────────────────────────
        // Distinct modules per course
        $modulesPerCourse = DB::table($lmr)
            ->select('course_id', DB::raw('count(distinct module_id) as distinct_modules'))
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        // Status counts per course (dynamic pivot)
        $courseStatusRaw = DB::table($lmr)
            ->select('course_id', 'completion_status', DB::raw('count(*) as cnt'))
            ->groupBy('course_id', 'completion_status')
            ->get()
            ->groupBy('course_id');

        // Main per-course stats
        $perCourse = DB::table("$lmr as lmr")
            ->leftJoin('domain_courses as dc', 'dc.course_id', '=', 'lmr.course_id')
            ->select(
                'lmr.course_id',
                DB::raw('MAX(dc.course_code) as course_code'),
                DB::raw('count(*) as total_rows'),
                DB::raw('count(distinct lmr.user_id) as learner_count'),
                DB::raw('count(distinct lmr.enrollment_id) as enrollment_count')
            )
            ->groupBy('lmr.course_id')
            ->orderBy('course_code')
            ->get()
            ->map(function ($row) use ($modulesPerCourse, $courseStatusRaw) {
                $mods = $modulesPerCourse[$row->course_id]->distinct_modules ?? 0;
                $row->distinct_modules = $mods;
                $row->expected_rows    = $row->learner_count * $mods;
                $row->missing_rows     = max(0, $row->expected_rows - $row->total_rows);
                $row->status_counts    = $courseStatusRaw->get($row->course_id, collect());
                return $row;
            });

        // ── Duplicate detection ───────────────────────────────────
        $duplicates = DB::table($lmr)
            ->select('user_id', 'course_item_id', 'enrollment_id', DB::raw('count(*) as dupes'))
            ->groupBy('user_id', 'course_item_id', 'enrollment_id')
            ->having('dupes', '>', 1)
            ->orderByDesc('dupes')
            ->limit(50)
            ->get();

        // ── Rows per learner distribution ─────────────────────────
        $rowsPerLearner = DB::table($lmr)
            ->select('user_id', DB::raw('count(*) as row_count'))
            ->groupBy('user_id')
            ->orderByDesc('row_count')
            ->get();

        $learnerMin    = $rowsPerLearner->min('row_count') ?? 0;
        $learnerMax    = $rowsPerLearner->max('row_count') ?? 0;
        $learnerAvg    = $rowsPerLearner->count() > 0
            ? round($rowsPerLearner->avg('row_count'), 1)
            : 0;
        $topLearners   = $rowsPerLearner->take(10);
        $bottomLearners = $rowsPerLearner->sortBy('row_count')->take(10)->values();

        // ── Data freshness ────────────────────────────────────────
        $freshness = DB::table($lmr)->selectRaw(
            'MIN(access_date) as earliest_access,
             MAX(access_date) as latest_access,
             MIN(created_at)  as earliest_sync,
             MAX(created_at)  as latest_sync'
        )->first();

        // ── Department breakdown ───────────────────────────────────
        // All learners in iSpring, grouped by department
        $allLearnersByDept = DB::table('users_ispring as u')
            ->leftJoin('departments as d', 'd.department_id', '=', 'u.department_id')
            ->where('u.role', 'learner')
            ->select(
                'u.department_id',
                DB::raw('MAX(d.name) as dept_name'),
                DB::raw('count(*) as total_learners')
            )
            ->groupBy('u.department_id')
            ->orderByDesc('total_learners')
            ->get()
            ->keyBy('department_id');

        // Learners who actually have rows in learner_module_results, by department
        $resultsLearnersByDept = DB::table("$lmr as lmr")
            ->leftJoin('users_ispring as u', 'u.user_id', '=', 'lmr.user_id')
            ->leftJoin('departments as d', 'd.department_id', '=', 'u.department_id')
            ->select(
                'u.department_id',
                DB::raw('MAX(d.name) as dept_name'),
                DB::raw('count(distinct lmr.user_id) as learners_with_results'),
                DB::raw('count(*) as result_rows')
            )
            ->groupBy('u.department_id')
            ->orderByDesc('result_rows')
            ->get();

        // Merge: for each department that appears in results, attach iSpring total
        $deptBreakdown = $resultsLearnersByDept->map(function ($row) use ($allLearnersByDept) {
            $ispring = $allLearnersByDept->get($row->department_id);
            $row->total_in_ispring = $ispring->total_learners ?? null;
            $row->dept_name        = $row->dept_name
                ?? $ispring->dept_name
                ?? '(no department)';
            $row->coverage_pct = ($row->total_in_ispring && $row->total_in_ispring > 0)
                ? round(($row->learners_with_results / $row->total_in_ispring) * 100, 1)
                : null;
            return $row;
        });

        // Departments that exist in iSpring but have ZERO rows in results
        $deptIdsWithResults = $resultsLearnersByDept->pluck('department_id');
        $deptsNoResults = $allLearnersByDept->reject(
            fn($d) => $deptIdsWithResults->contains($d->department_id)
        )->values();

        return view('dev.data-summary', compact(
            'totalRows', 'distinctLearners', 'distinctCourses', 'distinctEnrollments',
            'statusBreakdown',
            'perCourse',
            'duplicates',
            'learnerMin', 'learnerMax', 'learnerAvg', 'topLearners', 'bottomLearners',
            'freshness',
            'deptBreakdown', 'deptsNoResults'
        ));
    }
}
