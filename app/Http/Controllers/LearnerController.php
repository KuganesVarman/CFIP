<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LearnerController extends Controller
{
    public function dashboard(Request $request)
    {
        $user          = Auth::user();
        $ispringUserId = $user->user_id;
        $includeLessons = $request->boolean('include_lessons');

        $hasData = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->exists();

        // Overall progress — Quiz Lessons & Module Assessments (+ Lessons when toggled)
        $overallProgress = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->avg('progress');
        $overallProgress = round($overallProgress ?? 0, 1);

        $status = match(true) {
            $overallProgress >= 80 => 'Completed',
            $overallProgress >= 30 => 'On Track',
            $overallProgress > 0   => 'At Risk',
            default                => 'Not Started',
        };
        $statusColor = match($status) {
            'Completed' => '#1d9e75',
            'On Track'  => '#f59e0b',
            'At Risk'   => '#e24b4a',
            default     => '#9ca3af',
        };

        $modulesCompleted = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->whereIn('completion_status', ['passed', 'complete', 'completed'])
            ->count();

        $totalModules = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->count();

        $lastActiveRaw = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->whereNotNull('access_date')
            ->max('access_date');
        $lastActive = $lastActiveRaw
            ? Carbon::parse($lastActiveRaw)->format('d M Y')
            : null;

        // Domain breakdown
        $allDomains    = DB::table('domains')->orderBy('order')->get();
        $domainBreakdown = $allDomains->map(function ($domain) use ($ispringUserId, $includeLessons) {
            $courseIds = DB::table('domain_courses')
                ->where('domain_id', $domain->id)
                ->pluck('course_id')
                ->toArray();

            $avgProgress   = 0;
            $countPassed   = 0;
            $countTotal    = 0;

            if (!empty($courseIds)) {
                $avgProgress = DB::table('learner_module_results')
                    ->where('user_id', $ispringUserId)
                    ->whereIn('course_id', $courseIds)
                    ->where(function ($q) use ($includeLessons) {
                        $q->where('module_title', 'like', '%Quiz Lesson%')
                          ->orWhere('module_title', 'like', '%Module Assessment%');
                        if ($includeLessons) {
                            $q->orWhere('module_title', 'like', 'Lesson%');
                        }
                    })
                    ->avg('progress');
                $avgProgress = round($avgProgress ?? 0, 1);

                $countPassed = DB::table('learner_module_results')
                    ->where('user_id', $ispringUserId)
                    ->whereIn('course_id', $courseIds)
                    ->where(function ($q) use ($includeLessons) {
                        $q->where('module_title', 'like', '%Quiz Lesson%')
                          ->orWhere('module_title', 'like', '%Module Assessment%');
                        if ($includeLessons) {
                            $q->orWhere('module_title', 'like', 'Lesson%');
                        }
                    })
                    ->whereIn('completion_status', ['passed', 'complete', 'completed'])
                    ->count();

                $countTotal = DB::table('learner_module_results')
                    ->where('user_id', $ispringUserId)
                    ->whereIn('course_id', $courseIds)
                    ->where(function ($q) use ($includeLessons) {
                        $q->where('module_title', 'like', '%Quiz Lesson%')
                          ->orWhere('module_title', 'like', '%Module Assessment%');
                        if ($includeLessons) {
                            $q->orWhere('module_title', 'like', 'Lesson%');
                        }
                    })
                    ->count();
            }

            $domainStatus = match(true) {
                $avgProgress >= 80 => 'Completed',
                $avgProgress >= 30 => 'On Track',
                $avgProgress > 0   => 'At Risk',
                default            => 'Not Started',
            };
            $domainStatusColor = match($domainStatus) {
                'Completed' => '#1d9e75',
                'On Track'  => '#f59e0b',
                'At Risk'   => '#e24b4a',
                default     => '#9ca3af',
            };

            return (object) [
                'id'               => $domain->id,
                'name'             => $domain->name,
                'code'             => $domain->code,
                'slug'             => str_replace('_', '-', $domain->code),
                'avg_progress'     => $avgProgress,
                'count_passed'     => $countPassed,
                'count_not_started'=> max(0, $countTotal - $countPassed),
                'count_total'      => $countTotal,
                'status'           => $domainStatus,
                'status_color'     => $domainStatusColor,
            ];
        });

        // Next recommended module — first incomplete quiz/assessment/lesson
        $nextModule = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->whereNotIn('completion_status', ['passed', 'complete', 'completed'])
            ->orderByRaw('access_date IS NULL DESC')
            ->orderBy('access_date', 'asc')
            ->first();

        // Recent activity
        $recentActivity = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->whereIn('completion_status', ['passed', 'complete', 'completed'])
            ->whereNotNull('completion_date')
            ->orderBy('completion_date', 'desc')
            ->limit(5)
            ->select('module_title', 'progress', 'completion_date', 'completion_status')
            ->get();

        // Programme overview breakdown
        $passedCount = $modulesCompleted;
        $inProgressCount = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->where('completion_status', 'in_progress')
            ->count();
        $failedCount = DB::table('learner_module_results')
            ->where('user_id', $ispringUserId)
            ->where(function ($q) use ($includeLessons) {
                $q->where('module_title', 'like', '%Quiz Lesson%')
                  ->orWhere('module_title', 'like', '%Module Assessment%');
                if ($includeLessons) {
                    $q->orWhere('module_title', 'like', 'Lesson%');
                }
            })
            ->where('completion_status', 'failed')
            ->count();
        $notStartedCount = max(0, $totalModules - $passedCount - $inProgressCount - $failedCount);

        // Department name for display
        $departmentName = null;
        if ($user->department_id) {
            $dept = DB::table('departments')
                ->where('department_id', $user->department_id)
                ->first();
            $departmentName = $dept ? $dept->name : null;
        }

        return view('learner.dashboard', compact(
            'user', 'hasData', 'overallProgress', 'status', 'statusColor',
            'modulesCompleted', 'totalModules',
            'lastActive', 'domainBreakdown',
            'nextModule', 'recentActivity',
            'passedCount', 'inProgressCount', 'failedCount', 'notStartedCount',
            'departmentName', 'includeLessons'
        ));
    }

    public function modules(Request $request)
    {
        $user          = Auth::user();
        $ispringUserId = $user->user_id;
        $selectedDomain = $request->get('domain', 'all');
        $includeLessons = $request->boolean('include_lessons');

        $allDomains = DB::table('domains')->orderBy('order')->get();

        $domainModules = $allDomains->map(function ($domain) use ($ispringUserId, $selectedDomain, $includeLessons) {
            $slug = str_replace('_', '-', $domain->code);
            if ($selectedDomain !== 'all' && $slug !== $selectedDomain) {
                return null;
            }

            $courses = DB::table('domain_courses')
                ->where('domain_id', $domain->id)
                ->get(['course_id', 'course_code']);

            $modules      = [];
            $totalPassed  = 0;
            $totalMods    = 0;

            foreach ($courses as $course) {
                $rows = DB::table('learner_module_results')
                    ->where('user_id', $ispringUserId)
                    ->where('course_id', $course->course_id)
                    ->where(function ($q) use ($includeLessons) {
                        $q->where('module_title', 'like', '%Quiz Lesson%')
                          ->orWhere('module_title', 'like', '%Module Assessment%');
                        if ($includeLessons) {
                            $q->orWhere('module_title', 'like', 'Lesson%');
                        }
                    })
                    ->orderByRaw('access_date IS NULL ASC')
                    ->orderBy('access_date', 'asc')
                    ->get(['module_title', 'completion_status', 'progress', 'access_date', 'completion_date']);

                foreach ($rows as $row) {
                    $effective = $this->resolveStatus($row->completion_status);
                    $isLesson  = str_starts_with($row->module_title ?? '', 'Lesson');
                    $modules[] = (object) [
                        'module_title'     => $row->module_title,
                        'course_code'      => $course->course_code,
                        'completion_status'=> $row->completion_status,
                        'effective_status' => $effective,
                        'progress'         => $row->progress,
                        'access_date'      => $row->access_date,
                        'completion_date'  => $row->completion_date,
                        'type'             => $isLesson ? 'Lesson' : ((stripos($row->module_title, 'assessment') !== false) ? 'Assessment' : 'Quiz'),
                    ];
                    if ($effective === 'passed') {
                        $totalPassed++;
                    }
                    $totalMods++;
                }
            }

            $avgProgress = $totalMods > 0
                ? round(collect($modules)->avg('progress'), 1)
                : 0;

            return (object) [
                'id'           => $domain->id,
                'name'         => $domain->name,
                'code'         => $domain->code,
                'slug'         => $slug,
                'modules'      => $modules,
                'total_passed' => $totalPassed,
                'total_modules'=> $totalMods,
                'avg_progress' => $avgProgress,
            ];
        })->filter()->values();

        return view('learner.modules', compact('domainModules', 'selectedDomain', 'allDomains', 'includeLessons'));
    }

    public function badges(Request $request)
    {
        $user          = Auth::user();
        $ispringUserId = $user->user_id;
        $learnerName   = $user->name;
        $includeLessons = $request->boolean('include_lessons');

        $domains = [
            ['slug' => 'foundation',     'name' => 'Foundation Domain',              'color' => '#1a4fa8', 'code' => 'foundation'],
            ['slug' => 'legal-ethics',   'name' => 'Legal & Ethics Domain',           'color' => '#22c7b8', 'code' => 'legal_ethics'],
            ['slug' => 'crime-inv',      'name' => 'Crime Investigation Domain',      'color' => '#f7b84f', 'code' => 'crime_inv'],
            ['slug' => 'soft-skills',    'name' => 'Soft Skill Competencies Domain',  'color' => '#7f77dd', 'code' => 'soft_skills'],
            ['slug' => 'inv-techniques', 'name' => 'Investigation Techniques Domain', 'color' => '#d85a30', 'code' => 'inv_techniques'],
        ];

        foreach ($domains as &$domain) {
            $domainId = DB::table('domains')->where('code', $domain['code'])->value('id');

            $courseIds = $domainId
                ? DB::table('domain_courses')->where('domain_id', $domainId)->pluck('course_id')->toArray()
                : [];

            $base = empty($courseIds)
                ? null
                : DB::table('learner_module_results')
                    ->where('user_id', $ispringUserId)
                    ->whereIn('course_id', $courseIds)
                    ->where(function ($q) use ($includeLessons) {
                        $q->where('module_title', 'like', '%Quiz Lesson%')
                          ->orWhere('module_title', 'like', '%Module Assessment%');
                        if ($includeLessons) {
                            $q->orWhere('module_title', 'like', 'Lesson%');
                        }
                    });

            $totalInDomain  = $base ? (clone $base)->count() : 0;
            $passedInDomain = $base
                ? (clone $base)->whereIn('completion_status', ['passed', 'complete', 'completed'])->count()
                : 0;

            $domain['earned']       = ($totalInDomain > 0 && $passedInDomain >= $totalInDomain);
            $domain['earned_date']  = $domain['earned'] && $base
                ? (clone $base)->max('completion_date')
                : null;
            $domain['progress']     = $totalInDomain > 0
                ? round(($passedInDomain / $totalInDomain) * 100)
                : 0;
            $domain['modules_passed'] = $passedInDomain;
            $domain['modules_total']  = $totalInDomain;
        }
        unset($domain);

        $entryLevelComplete = collect($domains)->every(fn($d) => $d['earned']);
        $certEarnedDate     = $entryLevelComplete
            ? collect($domains)->max('earned_date')
            : null;

        return view('learner.badges', compact(
            'domains', 'entryLevelComplete', 'certEarnedDate', 'learnerName', 'user', 'includeLessons'
        ));
    }

    private function resolveStatus(string $status): string
    {
        return match(strtolower(trim($status ?? ''))) {
            'passed', 'complete', 'completed' => 'passed',
            'in_progress'                     => 'in_progress',
            'failed'                          => 'failed',
            default                           => 'not_started',
        };
    }
}
