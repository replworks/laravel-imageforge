<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForge;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeAssetForgeUrlTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_asset_returns_a_forge_url(): void
    {
        $this->forceAssetUrl('https://wifinote.test');
        config(['imageforge.service' => 'wifinote']);

        $this->assertSame(
            'https://img.repl.net/wifinote/templates/preview.png?w=320',
            ImageForge::make('templates/preview.png', 320)->asset(),
        );
    }

    public function test_asset_requires_a_service_when_width_is_present(): void
    {
        $this->forceAssetUrl('https://wifinote.test');

        $this->expectExceptionMessage('imageforge.service');

        ImageForge::make('templates/preview.png', 320)->asset();
    }

    public function test_asset_does_not_read_the_configured_disk(): void
    {
        $this->forceAssetUrl('https://wifinote.test');
        config([
            'imageforge.disk' => 'undefined',
            'imageforge.service' => 'wifinote',
        ]);
        Storage::shouldReceive('disk')->never();

        $this->assertSame(
            'https://img.repl.net/wifinote/templates/preview.png?w=320',
            ImageForge::make('templates/preview.png', 320)->asset(),
        );
    }

    private function forceAssetUrl(string $rootUrl): void
    {
        URL::forceRootUrl($rootUrl);
        URL::forceScheme('https');
    }
}
