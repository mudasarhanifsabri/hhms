<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;        // ✅ correct base class
use Illuminate\Support\Facades\Schema;         // ✅ for defaultStringLength
use Illuminate\Support\Facades\Route;          // ✅ you were using Route
use Illuminate\Pagination\Paginator;
use App\Models\User;
use App\Services\SmsService;                   // (you had this imported)
use App\Support\AppSettings;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use App\Support\ActivityLogger;

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
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());
        Paginator::useBootstrap();
        Event::listen(Login::class, fn (Login $event) => ActivityLogger::write('login_success', $event->user, ['description' => 'Successful login']));
        Event::listen(Failed::class, fn (Failed $event) => ActivityLogger::write('login_failed', $event->user, ['user_email' => $event->credentials['email'] ?? null, 'description' => 'Failed login attempt']));
        Event::listen(Logout::class, fn (Logout $event) => ActivityLogger::write('logout', $event->user, ['description' => 'User logged out']));
    }
}
