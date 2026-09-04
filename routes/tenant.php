<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenants\TenantController;

Route::middleware(['auth', 'role:tenant', \App\Http\Middleware\CompleteTenantProfile::class])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/profile', [TenantController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [TenantController::class, 'updateProfile'])->name('profile.update');
    Route::get('/dashboard', [TenantController::class, 'dashboard'])->name('dashboard');
    Route::get('/maintenance', [\App\Http\Controllers\Tenants\MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [\App\Http\Controllers\Tenants\MaintenanceController::class, 'store'])->middleware('throttle:10,1')->name('maintenance.store');
    Route::get('/bookings/{booking}', [TenantController::class, 'booking'])->name('booking.show');
    Route::post('/bookings/{booking}/inspections/{type}/start', [TenantController::class, 'startInspection'])->name('inspection.start');
    Route::post('/inspections/{inspection}/draft', [\App\Http\Controllers\Maintainers\InspectionDraftController::class, 'tenantSave'])->name('inspection.draft');
    Route::post('/inspections/{inspection}/photo', [\App\Http\Controllers\Maintainers\InspectionDraftController::class, 'tenantPhoto'])->name('inspection.photo');
    Route::get('/inspections/{inspection}/areas', [TenantController::class, 'areas'])->name('inspection.areas');
    Route::post('/inspections/{inspection}/areas', [TenantController::class, 'storeAreas'])->name('inspection.areas.store');
    Route::get('/inspections/{inspection}/inspect/{area}', [TenantController::class, 'inspectArea'])->name('inspection.inspect');
    Route::post('/inspections/{inspection}/inspect/{area}', [TenantController::class, 'storeArea'])->name('inspection.inspect.store');
    Route::get('/inspections/{inspection}/review', [TenantController::class, 'reviewInspection'])->name('inspection.review');
    Route::get('/inspections/{inspection}/notes', [TenantController::class, 'notes'])->name('inspection.notes');
    Route::post('/inspections/{inspection}/submit', [TenantController::class, 'submitInspection'])->name('inspection.submit');
    Route::get('/inspections/{inspection}/submitted', [TenantController::class, 'submitted'])->name('inspection.submitted');
});
