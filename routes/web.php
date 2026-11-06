<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCheckInController;
use App\Http\Controllers\QuickOpsController;
use App\Http\Controllers\RosterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

// 1. Root & Authentication Routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2. Public Student Attendance Form (No auth required, PRD §2.1 A02)
Route::prefix('attend')->group(function () {
    Route::get('/{token}', [PublicCheckInController::class, 'show'])->name('public.checkin');
    Route::get('/{token}/search', [PublicCheckInController::class, 'searchRoster'])
        ->middleware('throttle:20,1') // PRD §6.2 #2: anti-scraping cap, 20 req/min per IP
        ->name('public.roster-search');
    Route::post('/{token}/submit', [PublicCheckInController::class, 'submit'])
        ->middleware('throttle:10,1')
        ->name('public.checkin.submit');
    Route::get('/{token}/pass/{hash}', [PublicCheckInController::class, 'pass'])->name('public.pass');
});

// 3. Authenticated Staff & Admin Routes
Route::middleware('auth')->group(function () {
    // Organizer Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Activities (parent records that own rosters and sessions)
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/create', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('/', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::post('/{activity}/toggle', [ActivityController::class, 'toggleStatus'])->name('activities.toggle');
    });

    // Sessions Management & Broadcast
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index'])->name('sessions.index');
        Route::get('/create', [SessionController::class, 'create'])->name('sessions.create');
        Route::post('/', [SessionController::class, 'store'])->name('sessions.store');
        Route::post('/{session}/toggle', [SessionController::class, 'toggleStatus'])->name('sessions.toggle')->can('manage', 'session');
        Route::post('/{session}/duplicate', [SessionController::class, 'duplicate'])->name('sessions.duplicate')->can('manage', 'session');
        Route::get('/{session}/broadcast', [SessionController::class, 'broadcast'])->name('sessions.broadcast')->can('manage', 'session');
        Route::get('/{session}/live-stats', [SessionController::class, 'liveStats'])->name('sessions.live-stats')->can('manage', 'session');
        Route::get('/{session}/export', [SessionController::class, 'export'])->name('sessions.export')->can('manage', 'session');

        // Manual record corrections (reason + audit trail required)
        Route::post('/{session}/records/{record}/correct', [AttendanceRecordController::class, 'correct'])->name('sessions.records.correct')->can('manage', 'session');
        Route::delete('/{session}/records/{record}', [AttendanceRecordController::class, 'destroy'])->name('sessions.records.destroy')->can('manage', 'session');
    });

    // Rosters & Eligible Datasets
    Route::prefix('rosters')->group(function () {
        Route::get('/', [RosterController::class, 'index'])->name('rosters.index');
        Route::post('/preview', [RosterController::class, 'preview'])->name('rosters.preview');
        Route::post('/import', [RosterController::class, 'import'])->name('rosters.import');
        Route::get('/template', [RosterController::class, 'downloadTemplate'])->name('rosters.template');
    });

    // Matrix & Reports Grid
    Route::get('/matrix', [MatrixController::class, 'index'])->name('matrix.index');
    Route::get('/matrix/export', [MatrixController::class, 'export'])->name('matrix.export');

    // Quick Operations (Scanner & Walk-In)
    Route::prefix('quick-ops')->group(function () {
        Route::get('/scanner', [QuickOpsController::class, 'scanner'])->name('quick-ops.scanner');
        Route::post('/scanner/scan', [QuickOpsController::class, 'handleScan'])->name('quick-ops.scanner.scan');
        Route::get('/walk-in', [QuickOpsController::class, 'walkIn'])->name('quick-ops.walk-in');
        Route::post('/walk-in', [QuickOpsController::class, 'storeWalkIn'])->name('quick-ops.walk-in.store');
    });

    // Global search (top bar) & Notification inbox
    Route::get('/search', SearchController::class)->middleware('throttle:60,1')->name('search');

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // User Profile & Account Settings
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/sessions/revoke-others', [ProfileController::class, 'logoutOtherDevices'])->name('profile.sessions.revoke-others');
        Route::delete('/sessions/{session}', [ProfileController::class, 'logoutSingleDevice'])->name('profile.sessions.revoke');
    });

    // System Administrator Oversight (PRD §5.2)
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/staff', [AdminController::class, 'staffIndex'])->name('admin.staff.index');
        Route::post('/staff', [AdminController::class, 'storeStaff'])->name('admin.staff.store');
        Route::post('/staff/{user}/toggle', [AdminController::class, 'toggleStaffStatus'])->name('admin.staff.toggle');
        Route::post('/staff/{user}/reset-password', [AdminController::class, 'resetStaffPassword'])->name('admin.staff.reset-password');
        Route::get('/activities', [AdminController::class, 'activitiesIndex'])->name('admin.activities.index');
        Route::get('/audit', [AuditController::class, 'index'])->name('admin.audit.index');
        Route::get('/audit/export', [AuditController::class, 'exportCsv'])->name('admin.audit.export');
    });
});
