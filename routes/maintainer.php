<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Maintainers\MaintainerController;

Route::middleware(['auth', 'role:maintainer'])->prefix('maintainer')->name('maintainer.')->group(function () {
    Route::get('/dashboard', [MaintainerController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [MaintainerController::class, 'profile'])->name('profile');
    Route::get('/notifications', [MaintainerController::class, 'notifications'])->name('notifications');
    Route::get('/tasks', [MaintainerController::class, 'tasks'])->name('task.index');
    Route::get('/tasks/live', [MaintainerController::class, 'liveTasks'])->name('task.live');
    Route::get('/tasks/grid', [MaintainerController::class, 'taskGrid'])->name('task.grid');
    Route::get('/tasks/{task}', [MaintainerController::class, 'showTask'])->name('task.show');
    Route::post('/tasks/{task}/expense-request', [\App\Http\Controllers\Maintainers\TaskExpenseController::class, 'store'])->name('task.expense-request');
    Route::get('/tasks/{task}/accept', [MaintainerController::class, 'acceptForm'])->name('task.accept.form');
    Route::post('/tasks/{task}/accept', [MaintainerController::class, 'acceptTask'])->name('task.accept');
    Route::get('/tasks/{task}/remarks/create', [MaintainerController::class, 'remarkForm'])->name('task.remark.form');
    Route::get('/tasks/{task}/timeline', [MaintainerController::class, 'timeline'])->name('task.timeline');
    Route::get('/tasks/{task}/costs/create', [MaintainerController::class, 'costForm'])->name('task.cost.form');
    Route::post('/tasks/{task}/costs', [MaintainerController::class, 'addCost'])->name('task.cost.store');
    Route::get('/tasks/{task}/inspection', [MaintainerController::class, 'inspectionForm'])->name('task.inspection.form');
    Route::post('/tasks/{task}/inspection', [MaintainerController::class, 'submitInspection'])->name('task.inspection.submit');
    Route::get('/tasks/{task}/complete', [MaintainerController::class, 'completeForm'])->name('task.complete.form');
    Route::post('/tasks/{task}/start', [MaintainerController::class, 'startTask'])->name('task.start');
    Route::post('/tasks/{task}/complete', [MaintainerController::class, 'completeTask'])->name('task.complete');
    Route::post('/tasks/{task}/remarks', [MaintainerController::class, 'addRemark'])->name('task.remark');
});
