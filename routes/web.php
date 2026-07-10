<?php

use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EvaluationPeriodController;
use App\Http\Controllers\Admin\EvaluationQuestionController;
use App\Http\Controllers\Admin\LecturerController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudyProgramController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::resource('study-programs', StudyProgramController::class)->except('show');
    Route::resource('courses', CourseController::class)->except('show');
    Route::resource('evaluation-questions', EvaluationQuestionController::class)->except('show');
    Route::resource('class-groups', ClassGroupController::class)->except('show');
    Route::resource('lecturers', LecturerController::class)->except('show');
    Route::resource('students', StudentController::class)->except('show');
    Route::resource('evaluation-periods', EvaluationPeriodController::class)->except('show');
    Route::post('evaluation-periods/{evaluation_period}/open', [EvaluationPeriodController::class, 'open'])->name('evaluation-periods.open');
    Route::post('evaluation-periods/{evaluation_period}/close', [EvaluationPeriodController::class, 'close'])->name('evaluation-periods.close');
});

Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::view('/dashboard', 'lecturer.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::view('/dashboard', 'student.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:kaprodi'])->prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::view('/dashboard', 'kaprodi.dashboard')->name('dashboard');
});

require __DIR__.'/auth.php';
