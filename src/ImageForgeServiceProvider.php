<?php

namespace Replworks\ImageForge;

use Illuminate\Support\Facades\Blade;
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

        Blade::directive('imageForgeAsset', static function ($expression): string {
            return "<?php echo e(\\Replworks\\ImageForge\\ImageForge::make({$expression})->asset()); ?>";
        });

        Blade::directive('imageForgeStorage', static function ($expression): string {
            return "<?php echo e(\\Replworks\\ImageForge\\ImageForge::make({$expression})->storage()); ?>";
        });

        Blade::directive('imageForgeAssetRaw', static function ($expression): string {
            return "<?php echo \\Replworks\\ImageForge\\ImageForge::make({$expression})->asset(); ?>";
        });

        Blade::directive('imageForgeStorageRaw', static function ($expression): string {
            return "<?php echo \\Replworks\\ImageForge\\ImageForge::make({$expression})->storage(); ?>";
        });
    }
}
