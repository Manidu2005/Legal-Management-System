<?php

namespace App\Providers;

use App\Models\LegalCase;
use App\Models\User;
use App\Observers\LegalCaseObserver;
use App\Policies\CaseAccessPolicy;
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
        // Register observers
        LegalCase::observe(LegalCaseObserver::class);

        // Policy: case access — governs who can view a given case (see CaseAccessPolicy)
        Gate::policy(LegalCase::class, CaseAccessPolicy::class);

        // Gate: view-financials — only partners and associates can view financial data
        Gate::define('view-financials', function (User $user): bool {
            return in_array($user->role, ['partner', 'associate']);
        });

        // Gate: manage-users — only partners can manage user accounts
        Gate::define('manage-users', function (User $user): bool {
            return $user->role === 'partner';
        });
    }
}
