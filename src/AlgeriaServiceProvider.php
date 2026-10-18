<?php

namespace Djouamaah\Algeria;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AlgeriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/algeria.php', 'algeria');

        $this->app->singleton(Algeria::class, fn ($app) => new Algeria((int) $app['config']->get('algeria.division', 69)));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'algeria');

        $config = $this->app['config'];

        if ($config->get('algeria.routes.enabled')) {
            Route::prefix($config->get('algeria.routes.prefix', 'api/algeria'))
                ->middleware($config->get('algeria.routes.middleware', ['api']))
                ->name($config->get('algeria.routes.name', 'algeria.'))
                ->group(__DIR__.'/../routes/api.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/algeria.php' => config_path('algeria.php')], 'algeria-config');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'algeria-migrations');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/algeria')], 'algeria-lang');

            $this->commands([
                Console\InstallCommand::class,
                Console\SeedCommand::class,
            ]);
        }
    }
}
