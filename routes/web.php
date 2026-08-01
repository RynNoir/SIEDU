<?php

use App\Http\Controllers\Admin\AlumniController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\CourseClassAssignmentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationPeriodController;
use App\Http\Controllers\Admin\EvaluationQuestionController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\SemesterPromotionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Kaprodi\DashboardController as KaprodiDashboardController;
use App\Http\Controllers\Lecturer\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\EvaluationController;
use Illuminate\Support\Facades\Route;

// Tamu melihat landing page; yang sudah masuk langsung dilempar ke dashboardnya
// masing-masing supaya tidak perlu melewati halaman promosi tiap membuka root.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : view('landing');
})->name('landing');

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRoute());
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('password/change', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('study-programs', StudyProgramController::class)->except('show');
    Route::resource('courses', CourseController::class)->except('show');
    Route::resource('evaluation-questions', EvaluationQuestionController::class)->except('show');
    Route::resource('class-groups', ClassGroupController::class)->except('show');
    Route::resource('lecturers', LecturerController::class)->except('show');
    Route::resource('students', StudentController::class)->except('show');
    Route::get('alumni', [AlumniController::class, 'index'])->name('alumni.index');
    Route::resource('evaluation-periods', EvaluationPeriodController::class)->except('show');
    Route::post('evaluation-periods/{evaluation_period}/open', [EvaluationPeriodController::class, 'open'])->name('evaluation-periods.open');
    Route::post('evaluation-periods/{evaluation_period}/close', [EvaluationPeriodController::class, 'close'])->name('evaluation-periods.close');
    Route::resource('course-class-assignments', CourseClassAssignmentController::class)->except('show');
    Route::get('semester-promotion', [SemesterPromotionController::class, 'index'])->name('semester-promotion.index');
    Route::get('semester-promotion/preview', [SemesterPromotionController::class, 'preview'])->name('semester-promotion.preview');
    Route::post('semester-promotion', [SemesterPromotionController::class, 'run'])->name('semester-promotion.run');
});

Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('assignments/{assignment}', [DashboardController::class, 'show'])->name('assignments.show');
    Route::get('assignments/{assignment}/export', [DashboardController::class, 'exportAssignment'])->name('assignments.export');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('student.evaluations.index'))->name('dashboard');
    Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/{assignment}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('evaluations/{assignment}', [EvaluationController::class, 'store'])->name('evaluations.store');
});

Route::middleware(['auth', 'role:kaprodi'])->prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::get('/dashboard', [KaprodiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [KaprodiDashboardController::class, 'export'])->name('dashboard.export');
    Route::get('assignments/{assignment}', [KaprodiDashboardController::class, 'show'])->name('assignments.show');
    Route::get('assignments/{assignment}/export', [KaprodiDashboardController::class, 'exportAssignment'])->name('assignments.export');
});

require __DIR__.'/auth.php';
