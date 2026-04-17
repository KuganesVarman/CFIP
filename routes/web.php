<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest routes (only accessible when NOT logged in)
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login.page');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Authenticated routes (only accessible when logged in)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/pc/dashboard', [DashboardController::class, 'pcDashboard'])->name('pc.dashboard');
    Route::get('/learner/dashboard', [DashboardController::class, 'learnerDashboard'])->name('learner.dashboard');

    // API refresh endpoint
    Route::get('/api/refresh-data', [DashboardController::class, 'refreshApiData'])->name('api.refresh');
});


Route::get('/debug/course-overview', function () {
    $results = \App\Models\Module::select(
            'course_id',
            \DB::raw('COUNT(module_id) as total_modules')
        )
        ->groupBy('course_id')
        ->get();

    return $results;
});

Route::get('/debug/course-details', function () {
    $courses = \App\Models\Module::select('course_id')->distinct()->get();

    $output = [];

    foreach ($courses as $c) {
        $modules = \App\Models\Module::where('course_id', $c->course_id)
            ->pluck('title');

        $output[] = [
            'course_id' => $c->course_id,
            'modules' => $modules,
        ];
    }

    return $output;
});

Route::get('/debug/ispring', function () {
    $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
        'grant_type' => 'client_credentials',
        'client_id' => config('services.cfip.client_id'),
        'client_secret' => config('services.cfip.client_secret'),
    ]);

    $token = $tokenResp->json('access_token');

    $response = Http::withToken($token)->get(
        config('services.cfip.base_url') . '/learners/modules/results',
        ['page' => 1, 'pageSize' => 500]
    );

    return $response->body();   // do NOT XML parse, return raw
});

Route::get('/debug/ispring-count', function () {
    $tokenResp = Http::asForm()->post(config('services.cfip.token_url'), [
        'grant_type' => 'client_credentials',
        'client_id' => config('services.cfip.client_id'),
        'client_secret' => config('services.cfip.client_secret'),
    ]);

    $token = $tokenResp->json('access_token');

    $response = Http::withToken($token)->get(
        config('services.cfip.base_url') . '/learners/modules/results'
    );

    $xml = simplexml_load_string($response->body());

    return "Total results returned by API: " . count($xml->results->result);
});

Route::get('/debug/learner-modules-grouped', function () {

    $courses = \App\Models\LearnerModuleResult::select('course_id')
        ->distinct()
        ->get();

    $output = [];

    foreach ($courses as $course) {
        // Fetch ALL rows for this course (NOT distinct)
        $modules = \App\Models\LearnerModuleResult::where('course_id', $course->course_id)
            ->orderBy('id')
            ->pluck('module_title');  // keep duplicates, keep full 10,000 rows

        $output[] = [
            'course_id' => $course->course_id,
            'modules'   => $modules,
        ];
    }

    return response()->json($output, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/debug/learner-simple', function () {

    $results = \App\Models\LearnerModuleResult::orderBy('id')
        ->get([
            'user_id',
            'module_title',
            'completion_status',
            'progress'
        ]);

    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});






