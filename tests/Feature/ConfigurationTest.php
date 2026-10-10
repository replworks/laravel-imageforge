<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ConfigurationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_defaults_are_available_without_publishing_config(): void
    {
        $this->assertSame('https://img.repl.net', config('imageforge.base_url'));
        $this->assertNull(config('imageforge.service'));
        $this->assertNull(config('imageforge.disk'));
    }

    public function test_environment_variables_map_to_configuration_values(): void
    {
        $config = static function (): array {
            putenv('IMAGEFORGE_BASE_URL=https://img.example.test');
            putenv('IMAGEFORGE_SERVICE=wifinote');
            putenv('IMAGEFORGE_DISK=public');

            try {
                return require __DIR__.'/../../config/imageforge.php';
            } finally {
                putenv('IMAGEFORGE_BASE_URL');
                putenv('IMAGEFORGE_SERVICE');
                putenv('IMAGEFORGE_DISK');
            }
        };

        $values = $config();

        $this->assertSame('https://img.example.test', $values['base_url']);
        $this->assertSame('wifinote', $values['service']);
        $this->assertSame('public', $values['disk']);
    }

    public function test_config_can_be_published(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'imageforge-config', '--force' => true]);

        $this->assertFileExists($this->app->configPath('imageforge.php'));
    }

    public function test_service_provider_is_declared_for_package_auto_discovery(): void
    {
        $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertContains(
            ImageForgeServiceProvider::class,
            $composer['extra']['laravel']['providers'],
        );
    }
}
