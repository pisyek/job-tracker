<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\JobApplication;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->scopeTrackerBindings();
    }

    /**
     * Resolve tracker records through the authenticated user so another user's
     * records respond with a 404 instead of leaking.
     */
    protected function scopeTrackerBindings(): void
    {
        Route::bind('company', function (string $value): Company {
            $user = auth()->user();

            abort_if($user === null, 404);

            return $user->companies()->findOrFail($value);
        });

        Route::bind('application', function (string $value): JobApplication {
            $user = auth()->user();

            abort_if($user === null, 404);

            return $user->jobApplications()->findOrFail($value);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
