<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pcUsers = DB::table('users')->where('role','PC')->get();
foreach ($pcUsers as $u) {
    $dept = $u->department_id
        ? DB::table('departments')->where('department_id', $u->department_id)->first()
        : null;
    $learnerCount = $u->department_id
        ? DB::table('users_ispring')->where('role','learner')->where('department_id', $u->department_id)->count()
        : 0;
    printf("%-12s  dept_id: %-40s  dept_name: %-40s  learners_in_ispring: %d\n",
        $u->user_id,
        $u->department_id ?? '(null)',
        $dept->name ?? '(no match in departments)',
        $learnerCount
    );
}
