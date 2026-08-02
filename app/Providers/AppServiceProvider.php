<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;        // ✅ correct base class
use Illuminate\Support\Facades\Schema;         // ✅ for defaultStringLength
use Illuminate\Support\Facades\Route;          // ✅ you were using Route
use Illuminate\Pagination\Paginator;
use App\Models\User;
use App\Services\SmsService;                   // (you had this imported)
use App\Support\AppSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Fix for "Specified key was too long" on older MySQL
        Schema::defaultStringLength(191);

        // ✅ Your existing stuff
        Route::model('user', User::class);
        Route::bind('user', function ($value) {
            return User::where('uuid', $value)->firstOrFail();
        });

        AppSettings::apply();
        Paginator::useBootstrap();
    }
}
