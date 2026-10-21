<?php

use App\Http\Controllers\Field\FieldDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('field/api')->group(function () {
    Route::get('/bootstrap', [FieldDataController::class, 'bootstrap']);

    Route::middleware('role:maintainer')->group(function () {
        Route::get('/tasks', [FieldDataController::class, 'tasks']);
        Route::get('/tasks/{task}', [FieldDataController::class, 'task']);
        Route::get('/tasks/{task}/inspection', [FieldDataController::class, 'inspection']);
        Route::get('/inspection-options', [FieldDataController::class, 'maintainerInspectionOptions']);
    });

    Route::middleware(['role:admin', 'admin.permission'])->name('admin.inspection.field.')->group(function () {
        Route::get('/inspections', [FieldDataController::class, 'inspections'])->name('index');
        Route::get('/inspections/{inspection}', [FieldDataController::class, 'review'])->name('show');
        Route::get('/options', [FieldDataController::class, 'options'])->name('options');
    });
});
