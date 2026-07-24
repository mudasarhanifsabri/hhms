<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GuestPortalController;
use App\Http\Controllers\OwnerDocumentSigningController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Auth;

// ========== Web Routes ==========
Route::get('/', function () {
    return Auth::check() ? to_route('dashboard') : to_route('login');
});

// ========== Authentication Routes ==========
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// Redirect to correct dashboard after login
Route::middleware(['auth'])->get('/dashboard', function () {
    return match (Auth::user()->role) {
        'admin'      => to_route('admin.dashboard'),
        'tenant'     => to_route('tenant.dashboard'),
        'landlord'   => to_route('landlord.dashboard'),
        'agent'      => to_route('agent.dashboard'),
        'maintainer' => to_route('maintainer.dashboard'),
        default      => abort(403, 'Unauthorized'),
    };
})->name('dashboard');

Route::get('/owner-documents/{token}', [OwnerDocumentSigningController::class, 'show'])->name('owner-documents.show');
Route::post('/owner-documents/{token}/sign', [OwnerDocumentSigningController::class, 'sign'])->name('owner-documents.sign');
Route::get('/owner-documents/{token}/pdf', [OwnerDocumentSigningController::class, 'pdf'])->name('owner-documents.pdf');
Route::get('/guest/bookings/{reference}', [GuestPortalController::class, 'show'])->name('guest.booking.show');
Route::get('/guest/bookings/{reference}/invoice', [GuestPortalController::class, 'invoice'])->name('guest.booking.invoice');
Route::get('/guest/bookings/{reference}/confirmation', [GuestPortalController::class, 'confirmation'])->name('guest.booking.confirmation');

// Include separate route files
require __DIR__.'/admin.php';
require __DIR__.'/tenant.php';
require __DIR__.'/landlord.php';
require __DIR__.'/agent.php';
require __DIR__.'/maintainer.php';

// ========== Profile Routes ==========
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Authentication Routes
require __DIR__.'/auth.php';
