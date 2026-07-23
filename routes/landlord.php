<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landlords\LandlordController;

Route::middleware(['auth', 'role:landlord'])->prefix('landlord')->name('landlord.')->group(function () {
    Route::get('/dashboard', [LandlordController::class, 'dashboard'])->name('dashboard');
});
