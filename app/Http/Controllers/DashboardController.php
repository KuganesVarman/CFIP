<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LearnerModuleResult;
use App\Models\IspringUser;

class DashboardController extends Controller
{
    /* -------------------------------------------------
     |  CONSTANTS
     |--------------------------------------------------*/

    private $FD_COURSES = [
        'FD01' => '9bb06490-37cd-11ef-9470-42cc767d5507',
        'FD02' => '72d2dfe8-37ce-11ef-b427-ee8800c1cbc6',
        'FD03' => 'adc2ca6e-37ce-11ef-93b2-42cc767d5507',
    ];

    private $FD_QUIZZES = [
        'FD01' => [
            "Quiz Lesson 1",
            "Quiz Lesson 3",
            "Quiz Lesson 4",
            "Quiz Lesson 5",
            "Quiz Lesson 2",
            "Module Assessment",
        ],
        'FD02' => [
            "Quiz Lesson 1",
            "Quiz Lesson 2",
            "Quiz Lesson 3",
            "Quiz Lesson 6",
            "Quiz Lesson 5",
            "Quiz Lesson 4",
            "Module Assessment",
        ],
        'FD03' => [
            "Quiz Lesson 1",
            "Quiz Lesson 2",
            "Quiz Lesson 3",
            "Quiz Lesson 4",
            "Quiz Lesson 5",
            "Quiz Lesson 6",
            "Module Assessment",
        ],
    ];

    private $STATUS_PASS     = ['accepted', 'passed', 'complete'];
    private $STATUS_PROGRESS = ['in_progress', 'pending', 'incomplete'];
    private $STATUS_FAIL     = ['not_started', 'failed', 'declined'];


    /* -------------------------------------------------
     |  HELPERS
     |--------------------------------------------------*/

    private function quizRows($courseId)
    {
        $fdKey = array_search($courseId, $this->FD_COURSES);

        if (!$fdKey) {
            return collect([]);
        }

        return LearnerModuleResult::where('course_id', $courseId)
            ->whereIn('module_title', $this->FD_QUIZZES[$fdKey]);
    }


    /* -------------------------------------------------
     |  ADMIN DASHBOARD
     |--------------------------------------------------*/

    public function adminDashboard()
    {
        $user = Auth::user();

        /* -----------------------------
         *  Get users per FD course
         * ----------------------------*/

        $fd01_users = $this->quizRows($this->FD_COURSES['FD01'])
            ->pluck('user_id')->unique();

        $fd02_users = $this->quizRows($this->FD_COURSES['FD02'])
            ->pluck('user_id')->unique();

        $fd03_users = $this->quizRows($this->FD_COURSES['FD03'])
            ->pluck('user_id')->unique();

        /* -----------------------------
         *  1. TOTAL ENROLLMENT
         *  Users who joined FD01 + FD02 + FD03
         * ----------------------------*/

        $totalEnrollment = $fd01_users
            ->intersect($fd02_users)
            ->intersect($fd03_users)
            ->count();


        /* -----------------------------
         *  Collect all quiz rows (FD01+FD02+FD03)
         * ----------------------------*/

        $allRows = LearnerModuleResult::whereIn('course_id', $this->FD_COURSES)
            ->where(function ($q) {
                foreach ($this->FD_QUIZZES as $list) {
                    $q->orWhereIn('module_title', $list);
                }
            })
            ->get();

        $totalRows = $allRows->count();


        /* -----------------------------
         *  2. COMPLETION RATE
         * ----------------------------*/

        $completed = $allRows->whereIn('completion_status', $this->STATUS_PASS)->count();
        $completionRate = $totalRows > 0 ? round(($completed / $totalRows) * 100, 2) : 0;


        /* -----------------------------
         *  3. IN PROGRESS LEARNERS
         * ----------------------------*/

        $inProgress = $allRows
            ->whereIn('completion_status', $this->STATUS_PROGRESS)
            ->pluck('user_id')
            ->unique()
            ->count();


        /* -----------------------------
         *  4. NOT STARTED / FAILED LEARNERS
         * ----------------------------*/

        $notStarted = $allRows
            ->whereIn('completion_status', $this->STATUS_FAIL)
            ->pluck('user_id')
            ->unique()
            ->count();


        /* -----------------------------
         *  5. BAR CHART DATA
         * ----------------------------*/

        $barChart = [];

        foreach ($this->FD_COURSES as $fd => $courseId) {

            $rows = $this->quizRows($courseId)->get();

            $barChart[$fd] = [
                'pass'     => $rows->whereIn('completion_status', $this->STATUS_PASS)->unique('user_id')->count(),
                'progress' => $rows->whereIn('completion_status', $this->STATUS_PROGRESS)->unique('user_id')->count(),
                'failed'   => $rows->whereIn('completion_status', $this->STATUS_FAIL)->unique('user_id')->count(),
            ];
        }


        /* -----------------------------
         *  GET AGENCIES FOR FILTER DROPDOWN
         * ----------------------------*/

        $agencyIds = IspringUser::whereNotNull('department_id')
            ->pluck('department_id')
            ->unique()
            ->values();


        return view('dashboard.AdminDashboard', [
            'user'            => $user,
            'totalEnrollment' => $totalEnrollment,
            'completionRate'  => $completionRate,
            'inProgress'      => $inProgress,
            'notStarted'      => $notStarted,
            'barChart'        => $barChart,
            'agencies'        => $agencyIds,
        ]);
    }


    /* -------------------------------------------------
     |  PC DASHBOARD
     |--------------------------------------------------*/

    public function pcDashboard()
    {
        return view('dashboard.PCDashboard', [
            'user' => Auth::user()
        ]);
    }


    /* -------------------------------------------------
     |  LEARNER DASHBOARD
     |--------------------------------------------------*/

    public function learnerDashboard()
    {
        return view('dashboard.LearnerDashboard', [
            'user' => Auth::user()
        ]);
    }
}
