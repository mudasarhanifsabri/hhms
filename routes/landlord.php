<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landlords\LandlordController;

Route::middleware(['auth', 'role:landlord'])->prefix('landlord')->name('landlord.')->group(function () {
    Route::get('/dashboard', [LandlordController::class, 'dashboard'])->name('dashboard');
    Route::get('/app', [LandlordController::class, 'app'])->name('app');
    Route::get('/statement/pdf', [LandlordController::class, 'statementPdf'])->name('statement.pdf');
    Route::post('/notifications/read', [LandlordController::class, 'readNotifications'])->name('notifications.read');
    Route::get('/expenses/{expense}/approval', [LandlordController::class, 'expenseApproval'])->name('expenses.approval.show');
    Route::post('/expenses/{expense}/approval', [LandlordController::class, 'decideExpense'])->name('expenses.approval.decide');
    Route::get('/expenses/{expense}/document/{kind}', [LandlordController::class, 'expenseDocument'])->where('kind', 'quotation|invoice')->name('expenses.document');
});
