<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeBladeDirectiveTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_image_forge_asset_directive_renders_an_escaped_asset_forge_url(): void
    {
        URL::forceRootUrl('https://wifinote.test');
        URL::forceScheme('https');
        config(['imageforge.service' => 'wifinote']);

        $rendered = Blade::render(
            "@imageForgeAsset('assets/seo/og.png', w: 1200, h: 630, f: 'jpeg')",
        );

        $this->assertSame(
            'https://img.repl.net/wifinote/assets/seo/og.png?w=1200&amp;h=630&amp;f=jpeg',
            $rendered,
        );
    }

    public function test_image_forge_storage_directive_renders_a_storage_forge_url(): void
    {
        config(['imageforge.service' => 'wifinote']);
        Storage::fake();

        $rendered = Blade::render(
            "@imageForgeStorage('uploads/og.png', 1200, 630)",
        );

        $this->assertStringContainsString(
            '/wifinote/storage/uploads/og.png?w=1200&amp;h=630',
            $rendered,
        );
    }

    public function test_directives_render_empty_for_an_empty_path(): void
    {
        $this->assertSame('', Blade::render("@imageForgeAsset('', w: 1200)"));
        $this->assertSame('', Blade::render('@imageForgeStorage(null, w: 1200)'));
    }

    public function test_directive_propagates_builder_errors(): void
    {
        URL::forceRootUrl('https://wifinote.test');
        URL::forceScheme('https');

        $this->expectExceptionMessage('imageforge.service');

        Blade::render("@imageForgeAsset('assets/seo/og.png', w: 1200)");
    }
}
