<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        // grant super-admin role unrestricted access
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super-Admin') ? true : null;
        });

        // gate access to admin area and show link
        Gate::define('system.admin', function ($user): bool {
            return $user->getAllPermissions()
                ->pluck('name')
                ->contains(fn ($permission) => str_starts_with($permission, 'admin.'));
        });

        // gate access to admin functions and show links
        Gate::define('admin.area', function ($user, ?string $area = null): bool {
            $area ??= explode('.', (string) request()->route()?->getName())[1] ?? '';
            return $user->getAllPermissions()
                ->pluck('name')
                ->contains(fn ($permission) => str_starts_with($permission, "admin.{$area}."));
        });

        Gate::define('system.setup', function ($user): bool {
            return $user->getAllPermissions()
                ->pluck('name')
                ->contains(fn ($permission) => str_starts_with($permission, 'setup.'));
        });

        Gate::define('setup.area', function ($user, ?string $area = null): bool {
            $area ??= rtrim(explode('.', (string) request()->route()?->getName())[1] ?? '', 's');
            return $user->getAllPermissions()
                ->pluck('name')
                ->contains(fn ($permission) => str_starts_with($permission, "setup.{$area}."));
        });

        // gate access to admin functions and show links
        Gate::define('user.area', function ($user, ?string $area = null): bool {
            $area ??= rtrim(explode('.', (string) request()->route()?->getName())[1] ?? '', 's');
            return $user->getAllPermissions()
                ->pluck('name')
                ->contains(fn ($permission) => str_starts_with($permission, "user.{$area}."));
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
