<?php

namespace App\Providers;

use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class GeminiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/gemini.php', 'gemini'
        );

        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService(
                config('gemini.api_key'),
                config('gemini.api_url'),
                config('gemini.api_version'),
                config('gemini.default_model'),
                config('gemini.safety_settings'),
                config('gemini.generation_config')
            );
        });

        // Register a shorthand alias
        $this->app->alias(GeminiService::class, 'gemini');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/gemini.php' => config_path('gemini.php'),
        ], 'gemini-config');
    }
}