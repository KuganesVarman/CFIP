<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyLearnerResultsSeeder extends Seeder
{
    private const COHORT1 = '882cc8c8-f523-11ef-9da4-8a172cc6eedc'; // Cohort_1 2025       → target 142
    private const COHORT2 = 'df9e30f1-20e2-11f1-ac64-f698745f9782'; // Cohort_2 April2026  → target 147

    // Courses to seed (CI03 and CI05 intentionally excluded — no modules yet)
    private array $courses = [
        'FD01' => ['id' => '9bb06490-37cd-11ef-9470-42cc767d5507', 'lessons' => 5, 'quizzes' => 5, 'pass_rate' => 0.88],
        'FD02' => ['id' => '72d2dfe8-37ce-11ef-b427-ee8800c1cbc6', 'lessons' => 6, 'quizzes' => 6, 'pass_rate' => 0.85],
        'FD03' => ['id' => 'adc2ca6e-37ce-11ef-93b2-42cc767d5507', 'lessons' => 5, 'quizzes' => 5, 'pass_rate' => 0.82],
        'LE01' => ['id' => '68e89a60-2cef-11f0-80b8-9a5697ae9b4b', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.85],
        'LE02' => ['id' => '8b44ad9c-2cef-11f0-a355-92304f079e28', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.83],
        'LE03' => ['id' => '9ca1af5e-2cef-11f0-8068-9a5697ae9b4b', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.80],
        'LE04' => ['id' => 'ad166442-2cef-11f0-a704-92304f079e28', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.78],
        'CI01' => ['id' => 'f0476e60-381b-11ef-b07d-42cc767d5507', 'lessons' => 5, 'quizzes' => 5, 'pass_rate' => 0.55],
        'CI02' => ['id' => '1c81e51e-381c-11ef-8e5c-42cc767d5507', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.48],
        'SC01' => ['id' => '8dd95c38-2cf4-11f0-bae0-92304f079e28', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.89],
        'SC02' => ['id' => 'c3e7d606-2cf4-11f0-93f4-92304f079e28', 'lessons' => 4, 'quizzes' => 4, 'pass_rate' => 0.87],
        'IT01' => ['id' => 'cc319084-5758-11f0-b5d5-de63bab8aef0', 'lessons' => 5, 'quizzes' => 5, 'pass_rate' => 0.83],
    ];

    public function run(): void
    {
        // ── 1. Wipe only C1 / C2 cohort assignments and all results ───
        DB::table('user_group')->whereIn('group_id', [self::COHORT1, self::COHORT2])->delete();
        DB::table('learner_module_results')->truncate();

        $cfipDeptId = DB::table('departments')->where('name', 'CFIP Academy')->value('department_id');

        // ── 2. Group all learners by department ────────────────────────
        $byDept = DB::table('users_ispring')
            ->where('role', 'learner')
            ->get(['user_id', 'department_id'])
            ->groupBy('department_id');

        $c1Users = [];
        $c2Users = [];

        foreach ($byDept as $deptId => $members) {
            $users = $members->pluck('user_id')->shuffle()->values()->all();
            $n     = count($users);

            if ($n === 0) continue;

            // CFIP Academy: exactly 2 in C1, at least 1 in C2
            if ($deptId === $cfipDeptId) {
                $c1Picks = array_slice($users, 0, 2);
                $c2Picks = array_slice($users, 2, 2);
                if (empty($c2Picks)) $c2Picks = [$users[0]]; // overlap if too small
            }
            // Single-learner dept: same person in both cohorts so neither has 0
            elseif ($n === 1) {
                $c1Picks = $users;
                $c2Picks = $users;
            }
            // Small dept (2–3): 1 in each, no overlap needed
            elseif ($n <= 3) {
                $c1Picks = [$users[0]];
                $c2Picks = [$users[1 % $n]]; // next user, or same if only 2
                // If exactly 2 they're already different; if 3, users[1]
            }
            // Normal dept: proportional split, min 1 each
            else {
                $c1n = max(1, (int) round($n * 142 / 317));
                $c2n = max(1, (int) round($n * 147 / 317));

                // Clamp so they don't overlap
                $c1n = min($c1n, $n - 1);
                $c2n = min($c2n, $n - $c1n);
                if ($c2n < 1) { $c2n = 1; } // guarantee min 1

                $c1Picks = array_slice($users, 0, $c1n);
                $c2Picks = array_slice($users, $c1n, $c2n);
            }

            foreach ($c1Picks as $uid) { $c1Users[$uid] = $uid; }
            foreach ($c2Picks as $uid) { $c2Users[$uid] = $uid; }
        }

        $c1Users = array_values($c1Users);
        $c2Users = array_values($c2Users);

        $this->command->info(sprintf(
            'Cohort 1: %d users | Cohort 2: %d users',
            count($c1Users), count($c2Users)
        ));

        // ── 3. Persist cohort assignments ──────────────────────────────
        $now = now();
        $this->insertCohort(self::COHORT1, $c1Users, $now);
        $this->insertCohort(self::COHORT2, $c2Users, $now);

        // ── 4. Generate results for everyone in either cohort ──────────
        $allActive = array_values(array_unique(array_merge($c1Users, $c2Users)));
        shuffle($allActive);
        $totalActive = count($allActive);

        // Only 2 users ever show as "in_progress" — pick them upfront.
        // The rest are pass or not_started (no rows).
        $inProgressUsers = array_slice($allActive, 0, 2);
        $inProgressSet   = array_flip($inProgressUsers);

        $rows = [];

        foreach ($allActive as $userId) {
            $isInProgress = isset($inProgressSet[$userId]);

            foreach ($this->courses as $code => $course) {
                $courseId = $course['id'];

                if ($isInProgress) {
                    // These 2 users are mid-way through every course
                    $outcome = 'in_progress';
                } else {
                    // Everyone else: pass or not_started
                    $roll    = mt_rand(0, 999) / 1000;
                    $outcome = ($roll < $course['pass_rate']) ? 'pass' : 'not_started';
                }

                if ($outcome === 'not_started') continue; // no rows

                $enrollmentId = $this->uid("enroll:{$userId}:{$code}");

                // Lessons
                for ($i = 1; $i <= $course['lessons']; $i++) {
                    $title = "Lesson {$i}.1";
                    if ($outcome === 'pass') {
                        $status   = 'complete';
                        $progress = 100;
                    } elseif ($i <= (int) ceil($course['lessons'] / 2)) {
                        // First half: done
                        $status   = 'complete';
                        $progress = 100;
                    } else {
                        $status   = 'in_progress';
                        $progress = mt_rand(10, 65);
                    }
                    $rows[] = $this->row($code, "lesson{$i}", $userId, $courseId, $this->uid("mod:{$code}:lesson{$i}"), $title, $enrollmentId, $status, $progress, $now);
                }

                // Quizzes
                for ($i = 1; $i <= $course['quizzes']; $i++) {
                    $title = "Quiz Lesson {$i}";
                    if ($outcome === 'pass') {
                        $status   = 'passed';
                        $progress = mt_rand(72, 100);
                    } elseif ($i <= (int) ceil($course['quizzes'] / 2)) {
                        $status   = 'passed';
                        $progress = mt_rand(65, 95);
                    } else {
                        $status   = mt_rand(0, 1) ? 'failed' : 'in_progress';
                        $progress = mt_rand(25, 64);
                    }
                    $rows[] = $this->row($code, "quiz{$i}", $userId, $courseId, $this->uid("mod:{$code}:quiz{$i}"), $title, $enrollmentId, $status, $progress, $now);
                }

                // Module Assessment
                if ($outcome === 'pass') {
                    $aStatus   = 'passed';
                    $aProgress = mt_rand(70, 100);
                } else {
                    $aStatus   = mt_rand(0, 2) === 0 ? 'failed' : 'in_progress';
                    $aProgress = mt_rand(15, 60);
                }
                $rows[] = $this->row($code, 'assessment', $userId, $courseId, $this->uid("mod:{$code}:assessment"), 'Module Assessment', $enrollmentId, $aStatus, $aProgress, $now);
            }
        }

        // ── 5. Upsert in chunks ────────────────────────────────────────
        $total = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('learner_module_results')->upsert(
                $chunk,
                ['course_item_id', 'user_id', 'enrollment_id'],
                [
                    'course_id', 'module_id', 'module_title',
                    'access_date', 'completion_date',
                    'time_spent', 'completion_status', 'progress',
                    'is_overdue', 'views_count', 'updated_at',
                ]
            );
            $total += count($chunk);
        }

        $dbRows  = DB::table('learner_module_results')->count();
        $dbUsers = DB::table('learner_module_results')->distinct('user_id')->count('user_id');

        $this->command->info("Done! Rows generated: {$total} | DB rows: {$dbRows} | Users with data: {$dbUsers}");
        $this->command->info("In-progress users: " . implode(', ', $inProgressUsers));

        // ── Agency coverage report ─────────────────────────────────────
        $this->command->info("\nAgency coverage:");
        $depts = DB::table('departments')
            ->whereIn('department_id', DB::table('users_ispring')->where('role','learner')->whereNotNull('department_id')->pluck('department_id'))
            ->orderBy('name')
            ->get(['department_id','name']);

        $c1Set = array_flip($c1Users);
        $c2Set = array_flip($c2Users);

        foreach ($depts as $d) {
            $learners = DB::table('users_ispring')->where('role','learner')->where('department_id',$d->department_id)->pluck('user_id');
            $inC1 = $learners->filter(fn($id) => isset($c1Set[$id]))->count();
            $inC2 = $learners->filter(fn($id) => isset($c2Set[$id]))->count();
            $total2 = $learners->count();
            $this->command->line(sprintf("  %-50s total:%-3d C1:%-3d C2:%-3d", substr($d->name,0,50), $total2, $inC1, $inC2));
        }
    }

    private function insertCohort(string $groupId, array $userIds, $now): void
    {
        $rows = array_map(fn($uid) => [
            'user_id'    => $uid,
            'group_id'   => $groupId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $userIds);

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_group')->upsert($chunk, ['user_id', 'group_id'], ['updated_at']);
        }
    }

    private function row(
        string $code, string $moduleKey,
        string $userId, string $courseId, string $modId,
        string $title, string $enrollmentId,
        string $status, int $progress, $now
    ): array {
        $isDone = in_array($status, ['complete', 'completed', 'passed']);
        return [
            'course_item_id'    => $this->uid("item:{$code}:{$moduleKey}:{$userId}"),
            'user_id'           => $userId,
            'course_id'         => $courseId,
            'module_id'         => $modId,
            'module_title'      => $title,
            'enrollment_id'     => $enrollmentId,
            'access_date'       => now()->subDays(mt_rand(10, 180))->format('Y-m-d H:i:s'),
            'completion_date'   => $isDone ? now()->subDays(mt_rand(1, 60))->format('Y-m-d H:i:s') : null,
            'time_spent'        => mt_rand(180, 4800),
            'completion_status' => $status,
            'progress'          => $progress,
            'is_overdue'        => 0,
            'views_count'       => mt_rand(1, 4),
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    // Deterministic UUID from a string seed
    private function uid(string $seed): string
    {
        $h = md5($seed);
        return sprintf('%08s-%04s-4%03s-%04x-%12s',
            substr($h, 0, 8), substr($h, 8, 4), substr($h, 13, 3),
            hexdec(substr($h, 16, 4)) & 0x3fff | 0x8000,
            substr($h, 20, 12)
        );
    }
}
