<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\Concerns\HasImageForge;
use Replworks\ImageForge\ImageForge;
use Replworks\ImageForge\ImageForgeServiceProvider;

class HasImageForgeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_a_declared_field_gets_a_builder(): void
    {
        $model = new ImageForgeModel(['preview_path' => 'templates/preview.png']);

        $this->assertInstanceOf(ImageForge::class, $model->previewPathImageForge(320));
    }

    public function test_generated_method_accepts_named_options(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $model = new ImageForgeModel(['preview_path' => 'templates/preview.png']);
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->method('url')->willReturn('https://cdn.example.test/storage/templates/preview.png');
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320&h=240',
            $model->previewPathImageForge(320, h: 240)->storage(),
        );
    }

    public function test_each_declared_field_gets_its_own_method(): void
    {
        $model = new ImageForgeModel([
            'preview_path' => 'templates/preview.png',
            'thumbnail_path' => 'templates/thumbnail.png',
        ]);

        $this->assertInstanceOf(ImageForge::class, $model->thumbnailPathImageForge(150));
    }

    public function test_generated_method_reads_the_raw_attribute_not_an_accessor(): void
    {
        $model = new ImageForgeModel(['preview_path' => 'templates/preview.png']);
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->once())
            ->method('url')
            ->with('templates/preview.png')
            ->willReturn('https://cdn.example.test/storage/templates/preview.png');
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->assertSame(
            'https://cdn.example.test/storage/templates/preview.png',
            $model->previewPathImageForge()->storage(),
        );
    }

    public function test_a_non_string_raw_attribute_raises_before_returning_a_builder(): void
    {
        $model = new ImageForgeModel(['preview_path' => ['invalid']]);

        $this->expectException(InvalidArgumentException::class);

        $model->previewPathImageForge(320);
    }
}

class ImageForgeModel extends Model
{
    use HasImageForge;

    protected $fillable = ['preview_path', 'thumbnail_path'];

    protected array $imageForgeFields = ['preview_path', 'thumbnail_path'];

    public function getPreviewPathAttribute($value): string
    {
        return 'accessor/should-not-be-used.png';
    }
}
