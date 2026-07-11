<?php

use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\ClassPromotionController;
use App\Http\Controllers\Admin\CourseClassAssignmentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationPeriodController;
use App\Http\Controllers\Admin\EvaluationQuestionController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Kaprodi\DashboardController as KaprodiDashboardController;
use App\Http\Controllers\Lecturer\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\EvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRoute());
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
    Route::resource('evaluation-periods', EvaluationPeriodController::class)->except('show');
    Route::post('evaluation-periods/{evaluation_period}/open', [EvaluationPeriodController::class, 'open'])->name('evaluation-periods.open');
    Route::post('evaluation-periods/{evaluation_period}/close', [EvaluationPeriodController::class, 'close'])->name('evaluation-periods.close');
    Route::resource('course-class-assignments', CourseClassAssignmentController::class)->except('show');
    Route::get('class-promotion', [ClassPromotionController::class, 'index'])->name('class-promotion.index');
    Route::post('class-promotion', [ClassPromotionController::class, 'run'])->name('class-promotion.run');
});

Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('assignments/{assignment}', [DashboardController::class, 'show'])->name('assignments.show');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', fn () => redirect()->route('student.evaluations.index'))->name('dashboard');
    Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/{assignment}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('evaluations/{assignment}', [EvaluationController::class, 'store'])->name('evaluations.store');
});

Route::middleware(['auth', 'role:kaprodi'])->prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::get('/dashboard', [KaprodiDashboardController::class, 'index'])->name('dashboard');
    Route::get('assignments/{assignment}', [KaprodiDashboardController::class, 'show'])->name('assignments.show');
});

require __DIR__.'/auth.php';
