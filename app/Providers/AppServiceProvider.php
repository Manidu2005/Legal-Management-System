<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Gate: view-financials — only partners can view financial data
        Gate::define('view-financials', function (User $user): bool {
            return $user->role === 'partner';
        });

        // Gate: manage-users — only partners can manage user accounts
        Gate::define('manage-users', function (User $user): bool {
            return $user->role === 'partner';
        });
    }
}
