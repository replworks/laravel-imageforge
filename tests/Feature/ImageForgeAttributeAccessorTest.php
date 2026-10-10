<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForge;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeAttributeAccessorTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    public function test_an_attribute_accessor_can_return_an_image_forge_builder_without_the_trait(): void
    {
        config(['imageforge.service' => 'wifinote']);
        $model = new ImageForgeAttributeModel(['preview_path' => 'templates/preview.png']);
        $disk = $this->createMock(FilesystemAdapter::class);
        $disk->expects($this->once())
            ->method('url')
            ->with('templates/preview.png')
            ->willReturn('https://cdn.example.test/storage/templates/preview.png');
        Storage::shouldReceive('disk')->once()->withNoArgs()->andReturn($disk);

        $this->assertInstanceOf(ImageForge::class, $model->preview_path_image_forge);
        $this->assertSame(
            'https://img.repl.net/wifinote/storage/templates/preview.png?w=320',
            $model->preview_path_image_forge->storage(),
        );
    }
}

class ImageForgeAttributeModel extends Model
{
    protected $fillable = ['preview_path'];

    protected function previewPathImageForge(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => ImageForge::make($attributes['preview_path'] ?? null, w: 320),
        );
    }
}
