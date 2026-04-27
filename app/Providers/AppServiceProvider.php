<?php

namespace App\Providers;

use App\Services\CloudinaryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CloudinaryService::class, function () {
            return new CloudinaryService;
        });
        $this->app->alias(CloudinaryService::class, 'cloudinary');
    }

    public function boot(): void
    {
        // Observe DigitalKeys to sync stock
        \App\Models\DigitalKey::observe(\App\Observers\DigitalKeyObserver::class);

        // Strict model enforcement (prevent N+1 queries)
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        // NOTE: Authorization is handled by Spatie Laravel Permission.
        // Gates and policies are auto-registered via the HasRoles trait on User model.
        // Use: $user->hasPermissionTo('users.edit')
        //       $user->hasRole('admin')
        //       @can('users.edit') in Blade / page.props.can in Vue
    }
}
