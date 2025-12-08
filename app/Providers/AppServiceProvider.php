<?php

namespace App\Providers;

use App\Models\Role;
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
        // Auto-seed Role_1 (Role Manager) if it doesn't exist
        // This ensures the required role exists when the application launches
        try {
            Role::firstOrCreate(
                ['number' => 1],
                ['name' => 'Role Manager']
            );

            // Ensure at least one role manager exists
            // If only one user exists, they automatically become a role manager
            // If no role managers exist, the oldest user becomes a role manager
            \App\Models\User::ensureRoleManagerExists();
        } catch (\Exception $e) {
            // Silently fail if database tables don't exist yet (during migrations)
            // This prevents errors during initial setup
        }
    }
}
