<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForge;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeStorageForgeUrlTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_storage_returns_a_forge_url_with_the_service_and_origin_path(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320',
            ImageForge::make('templates/preview.png', 320)->storage(),
        );
    }

    public function test_zero_width_is_present_in_the_forge_url(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=0',
            ImageForge::make('templates/preview.png', 0)->storage(),
        );
    }

    public function test_all_options_are_rendered_in_the_declared_order(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320&h=240&fit=fill&q=80&f=webp',
            ImageForge::make('templates/preview.png', 320, 240, 'fill', 80, 'webp')->storage(),
        );
    }

    public function test_null_options_are_omitted_from_the_query(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320&q=80',
            ImageForge::make('templates/preview.png', 320, q: 80)->storage(),
        );
    }

    public function test_option_values_are_not_validated_or_encoded(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320&fit=invalid&f=raw&x',
            ImageForge::make('templates/preview.png', 320, fit: 'invalid', f: 'raw&x')->storage(),
        );
    }

    public function test_source_url_query_and_fragment_are_not_copied(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $this->mockStorageUrl('https://cdn.example.test/storage/preview.png?signature=abc#fragment');

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/preview.png?w=320',
            ImageForge::make('templates/preview.png', 320)->storage(),
        );
    }

    public function test_service_must_be_set_when_a_forge_url_is_requested(): void
    {
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png');

        $this->expectExceptionMessage('imageforge.service');

        ImageForge::make('templates/preview.png', 320)->storage();
    }

    public function test_settings_are_read_again_for_each_call_on_the_same_builder(): void
    {
        config([
            'imageforge.base_url' => 'https://first.example.test',
            'imageforge.service' => 'first',
        ]);
        $this->mockStorageUrl('https://cdn.example.test/storage/templates/preview.png', 2);

        $builder = ImageForge::make('templates/preview.png', 320);

        $this->assertSame(
            'https://first.example.test/first/storage/templates/preview.png?w=320',
            $builder->storage(),
        );

        config([
            'imageforge.base_url' => 'https://second.example.test',
            'imageforge.service' => 'second',
        ]);

        $this->assertSame(
            'https://second.example.test/second/storage/templates/preview.png?w=320',
            $builder->storage(),
        );
    }

    public function test_storage_propagates_an_undefined_disk_error_before_composing(): void
    {
        $exception = new \RuntimeException('undefined disk');
        config(['imageforge.disk' => 'undefined']);
        Storage::shouldReceive('disk')->once()->with('undefined')->andThrow($exception);

        $this->expectExceptionObject($exception);

        ImageForge::make('templates/preview.png', 320)->storage();
    }

    private function mockStorageUrl(string $url, int $times = 1): void
    {
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->exactly($times))
            ->method('url')
            ->with('templates/preview.png')
            ->willReturn($url);

        Storage::shouldReceive('disk')->times($times)->withNoArgs()->andReturn($disk);
    }
}
