<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Agents\AgentController;

Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/dashboard', [AgentController::class, 'dashboard'])->name('dashboard');
});
