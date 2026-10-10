<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForge;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeSourceUrlTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.url', 'https://wifinote.test');
    }

    public function test_make_returns_an_image_forge_builder(): void
    {
        $builder = ImageForge::make('templates/preview.png');

        $this->assertInstanceOf(ImageForge::class, $builder);
    }

    public function test_storage_returns_null_for_a_null_path(): void
    {
        Storage::shouldReceive('disk')->never();

        $this->assertNull(ImageForge::make(null, 320)->storage());
    }

    public function test_asset_returns_null_for_an_empty_path(): void
    {
        $this->assertNull(ImageForge::make('', 320)->asset());
    }

    public function test_storage_uses_the_application_default_disk_when_disk_is_null(): void
    {
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->once())
            ->method('url')
            ->with('templates/preview.png')
            ->willReturn('https://wifinote.test/storage/templates/preview.png');

        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->assertSame(
            'https://wifinote.test/storage/templates/preview.png',
            ImageForge::make('templates/preview.png')->storage(),
        );
    }

    public function test_storage_uses_the_configured_disk(): void
    {
        config(['imageforge.disk' => 'public']);

        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->once())
            ->method('url')
            ->with('templates/preview.png')
            ->willReturn('https://cdn.example.test/templates/preview.png');

        Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);

        $this->assertSame(
            'https://cdn.example.test/templates/preview.png',
            ImageForge::make('templates/preview.png')->storage(),
        );
    }

    public function test_asset_returns_the_application_asset_url(): void
    {
        URL::forceRootUrl('https://wifinote.test');
        URL::forceScheme('https');

        $this->assertSame(
            'https://wifinote.test/templates/preview.png',
            ImageForge::make('templates/preview.png')->asset(),
        );
    }

    public function test_storage_propagates_a_host_disk_error(): void
    {
        $exception = new \RuntimeException('undefined disk');
        config(['imageforge.disk' => 'undefined']);
        Storage::shouldReceive('disk')->once()->with('undefined')->andThrow($exception);

        $this->expectExceptionObject($exception);

        ImageForge::make('templates/preview.png')->storage();
    }

    public function test_other_options_do_not_change_a_source_url_when_width_is_absent(): void
    {
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->method('url')->willReturn('https://wifinote.test/storage/templates/preview.png');
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->assertSame(
            'https://wifinote.test/storage/templates/preview.png',
            ImageForge::make('templates/preview.png', h: 240, fit: 'fill', q: 80, f: 'webp')->storage(),
        );
    }
}
