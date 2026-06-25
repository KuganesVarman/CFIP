<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseStructureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnerController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login.page');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Auth-only routes that must be reachable even when must_change_password = true
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/change-password',  [AuthController::class, 'showChangePassword'])->name('change.password');
    Route::post('/change-password', [AuthController::class, 'submitChangePassword'])->name('change.password.submit');
});

// All other authenticated routes — blocked until password is changed
Route::middleware(['auth', 'force-pwd-change'])->group(function () {

    // Admin-only routes
    Route::middleware('role:A')->group(function () {
        Route::post('/api/settings/sync', [DashboardController::class, 'updateSyncSetting'])->name('api.settings.sync');
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/admin/analytics/levels', [DashboardController::class, 'levelView'])->name('admin.analytics.levels');
        Route::get('/admin/analytics/domains', [DashboardController::class, 'domainView'])->name('admin.analytics.domains');
        Route::get('/admin/analytics/modules', [DashboardController::class, 'analyticsModules'])->name('admin.analytics.modules');
        Route::get('/admin/students', [DashboardController::class, 'adminStudentProgress'])->name('admin.students');
        Route::get('/admin/reports', [DashboardController::class, 'reportLog'])->name('admin.reports');
        Route::get('/admin/reports/generate', [DashboardController::class, 'generateReport'])->name('admin.reports.generate');
        Route::get('/admin/reports/domain', [DashboardController::class, 'generateDomainReport'])->name('admin.reports.domain.generate');
        Route::get('/admin/reports/module', [DashboardController::class, 'generateModuleReport'])->name('admin.reports.module.generate');

        // Audit Log
        Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log');

        // User Management
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
        Route::post('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role');
        Route::post('/admin/users/{user}/invite', [AdminUserController::class, 'sendInvitation'])->name('admin.users.invite');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        Route::get('/api/cohort-learners', [AdminUserController::class, 'cohortLearners'])->name('admin.cohort.learners');
        Route::post('/admin/users/bulk-invite', [AdminUserController::class, 'bulkInvite'])->name('admin.users.bulk-invite');
        Route::post('/admin/users/sync-emails', [AdminUserController::class, 'syncEmailsFromIspring'])->name('admin.users.sync-emails');
    });

    // Program Coordinator routes
    Route::middleware('role:PC')->group(function () {
        Route::get('/pc/dashboard', [DashboardController::class, 'pcDashboard'])->name('pc.dashboard');
        Route::get('/pc/analytics/levels', [DashboardController::class, 'pcAnalyticsLevels'])->name('pc.analytics.levels');
        Route::get('/pc/analytics/domains', [DashboardController::class, 'pcAnalyticsDomains'])->name('pc.analytics.domains');
        Route::get('/pc/analytics/modules', [DashboardController::class, 'pcAnalyticsModules'])->name('pc.analytics.modules');
        Route::get('/pc/students', [DashboardController::class, 'pcStudentProgress'])->name('pc.students');
        Route::get('/pc/reports', [DashboardController::class, 'reportLog'])->name('pc.reports');
        Route::get('/pc/reports/generate', [DashboardController::class, 'generateReport'])->name('pc.reports.generate');
        Route::get('/pc/reports/domain', [DashboardController::class, 'generateDomainReport'])->name('pc.reports.domain.generate');
        Route::get('/pc/reports/module', [DashboardController::class, 'generateModuleReport'])->name('pc.reports.module.generate');
    });

    // Learner routes
    Route::middleware('role:L')->group(function () {
        Route::get('/learner/dashboard', [LearnerController::class, 'dashboard'])->name('learner.dashboard');
        Route::get('/learner/modules',   [LearnerController::class, 'modules'])->name('learner.modules');
        Route::get('/learner/badges',    [LearnerController::class, 'badges'])->name('learner.badges');
    });

    // API status + sync — all authenticated roles
    Route::get('/api/ispring-status', [DashboardController::class, 'ispringStatus'])->name('api.ispring.status');
    Route::get('/api/refresh-data',   [DashboardController::class, 'refreshApiData'])->name('api.refresh');
    Route::get('/api/sync-status',    [DashboardController::class, 'syncStatus'])->name('api.sync.status');

    // AJAX — accessible by Admin and PC only
    Route::middleware('role:A,PC')->group(function () {
        Route::get('/api/filter-bar-chart',    [DashboardController::class, 'filterBarChart'])->name('api.filter.barchart');
        Route::get('/api/filter-domain-chart', [DashboardController::class, 'filterDomainChart'])->name('api.filter.domainchart');
        Route::get('/api/filter-level-chart',  [DashboardController::class, 'filterLevelChart'])->name('api.filter.levelchart');
        Route::get('/api/module-learners',     [DashboardController::class, 'moduleLearners'])->name('api.module.learners');
        Route::get('/api/score-band-learners', [DashboardController::class, 'scoreBandLearners'])->name('api.score.band.learners');
        Route::get('/api/learner-detail',      [DashboardController::class, 'learnerDetail'])->name('api.learner.detail');
        Route::post('/api/report-log',         [DashboardController::class, 'storeReportLog'])->name('api.report.log');
    });

    // Settings — all authenticated roles
    Route::get('/settings', [DashboardController::class, 'settingsPage'])->name('settings');
    Route::post('/settings/profile', [DashboardController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [DashboardController::class, 'changePassword'])->name('settings.password');

    // Dev tools — local environment only, Super Admin only
    if (app()->environment('local')) {
        Route::middleware('role:A')->group(function () {
            Route::get('/dev/course-structure', [CourseStructureController::class, 'index'])->name('dev.course-structure');
            Route::get('/dev/data-summary', [CourseStructureController::class, 'dataSummary'])->name('dev.data-summary');
            Route::get('/dev/learner-results', [CourseStructureController::class, 'learnerResults'])->name('dev.learner-results');
        });
    }
});
