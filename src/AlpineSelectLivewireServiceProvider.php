<?php

namespace Uluumbch\AlpineSelectLivewire;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AlpineSelectLivewireServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/alpine-select.php', 'alpine-select'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load views from package
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'alpine-select');

        // Load translations from package
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'alpine-select');

        // Register Blade components
        Blade::component('alpine-select::components.default', 'alpine-select::default');
        Blade::component('alpine-select::components.multiple', 'alpine-select::multiple');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/alpine-select'),
        ], 'alpine-select-views');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/alpine-select.php' => config_path('alpine-select.php'),
        ], 'alpine-select-config');

        // Publish translations
        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/alpine-select'),
        ], 'alpine-select-lang');
    }
}
