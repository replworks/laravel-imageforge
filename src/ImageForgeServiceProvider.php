<?php

namespace Replworks\ImageForge;

use Illuminate\Support\ServiceProvider;

class ImageForgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/imageforge.php', 'imageforge');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/imageforge.php' => config_path('imageforge.php'),
        ], 'imageforge-config');
    }
}
