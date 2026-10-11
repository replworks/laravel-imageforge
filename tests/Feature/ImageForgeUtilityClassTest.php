<?php

namespace Replworks\ImageForge\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Orchestra\Testbench\TestCase;
use Replworks\ImageForge\ImageForgeServiceProvider;

class ImageForgeUtilityClassTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ImageForgeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['imageforge.service' => 'wifinote']);
        URL::forceRootUrl('https://wifinote.test');
        URL::forceScheme('https');
    }

    public function test_it_rewrites_a_static_img_src_and_removes_consumed_classes(): void
    {
        $rendered = Blade::render('<img src="photo.jpg" class="rounded if-w-480 if-h-320 if-crop" alt="">');

        $this->assertSame(
            '<img src="https://img.repl.net/wifinote/photo.jpg?w=480&amp;h=320&amp;fit=fill" class="rounded" alt="">',
            $rendered,
        );
    }

    public function test_it_supports_dynamic_and_mixed_src_values(): void
    {
        $rendered = Blade::render(
            '<img class="if-w-480" src="/img/{{ $id }}.jpg">',
            ['id' => '42'],
        );

        $this->assertSame(
            '<img  src="https://img.repl.net/wifinote/img/42.jpg?w=480">',
            $rendered,
        );
    }

    public function test_it_accepts_a_resolved_source_expression_in_a_quoted_or_unquoted_src(): void
    {
        $quoted = Blade::render(
            '<img src="{{ $source }}" class="if-w-320">',
            ['source' => 'https://cdn.example.test/storage/photo.jpg'],
        );
        $unquoted = Blade::render(
            '<img src={{ $source }} class="if-w-320">',
            ['source' => 'https://cdn.example.test/storage/photo.jpg'],
        );

        $expected = 'https://img.repl.net/wifinote/storage/photo.jpg?w=320';
        $this->assertStringContainsString('src="'.$expected.'"', $quoted);
        $this->assertStringContainsString('src="'.$expected.'"', $unquoted);
    }

    public function test_it_transforms_an_already_resolved_storage_url_and_fills_missing_width(): void
    {
        $rendered = Blade::render('<img src="https://cdn.example.test/storage/uploads/photo.jpg" class="if-h-320">');

        $this->assertStringContainsString(
            'src="https://img.repl.net/wifinote/storage/uploads/photo.jpg?w=0&amp;h=320"',
            $rendered,
        );
        $this->assertStringNotContainsString('class=', $rendered);
    }

    public function test_it_clamps_values_and_preserves_unknown_prefixed_classes(): void
    {
        $rendered = Blade::render('<img src="photo.jpg" class="if-w-5000 if-q-0 if-custom">');

        $this->assertSame(
            '<img src="https://img.repl.net/wifinote/photo.jpg?w=2000&amp;q=1" class="if-custom">',
            $rendered,
        );
    }

    public function test_it_uses_a_configured_prefix(): void
    {
        config(['imageforge.dsl_prefix' => 'media-']);

        $rendered = Blade::render('<img src="photo.jpg" class="media-w-640">');

        $this->assertSame(
            '<img src="https://img.repl.net/wifinote/photo.jpg?w=640">',
            $rendered,
        );
    }

    public function test_it_transforms_absolute_source_urls(): void
    {
        $rendered = Blade::render(<<<'BLADE'
<img src="https://example.test/photo.jpg" class="if-w-480 rounded">
BLADE);

        $this->assertStringContainsString('src="https://img.repl.net/wifinote/photo.jpg?w=480" class="rounded"', $rendered);
    }

    public function test_it_ignores_protected_regions(): void
    {
        $template = <<<'BLADE'
@verbatim
<img src="photo.jpg" class="if-w-480">
@endverbatim
{{-- <img src="photo.jpg" class="if-w-480"> --}}
@php($html = '<img src="photo.jpg" class="if-w-480">')
<script>const html = '<img src="photo.jpg" class="if-w-480">';</script>
<style>.x::after { content: '<img src="photo.jpg" class="if-w-480">'; }</style>
BLADE;

        $rendered = Blade::render($template);

        $this->assertSame(3, substr_count($rendered, 'if-w-480'));
    }

    public function test_it_supports_multiline_and_single_quoted_tags(): void
    {
        $rendered = Blade::render(<<<'BLADE'
<img
    class='if-w-480 if-avif'
    src='photo.jpg'
    />
BLADE);

        $this->assertSame(<<<'HTML'
<img
    
    src="https://img.repl.net/wifinote/photo.jpg?w=480&amp;f=avif"
    />
HTML, $rendered);
    }

    public function test_it_leaves_a_mixed_src_unchanged_when_it_cannot_parse_it(): void
    {
        $rendered = Blade::render('<img src="/img/{{ $id.jpg" class="if-w-480">');

        $this->assertSame('<img src="/img/{{ $id.jpg" class="if-w-480">', $rendered);
    }
}
