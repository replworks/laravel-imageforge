<?php

namespace Replworks\ImageForge\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Replworks\ImageForge\Dsl;

class DslTest extends TestCase
{
    public function test_it_maps_classes_and_last_values_win(): void
    {
        $parsed = Dsl::parse([
            'rounded', 'if-w-320', 'if-w-5000', 'if-crop', 'if-fill', 'if-webp', 'if-auto',
        ], 'if-');

        $this->assertSame([
            'w' => 2000,
            'h' => null,
            'fit' => 'fill',
            'q' => null,
            'f' => null,
        ], $parsed['options']);
        $this->assertSame(['if-w-320', 'if-w-5000', 'if-crop', 'if-fill', 'if-webp', 'if-auto'], $parsed['consumed']);
    }

    public function test_it_clamps_width_height_and_quality(): void
    {
        $parsed = Dsl::parse(['if-w--1', 'if-w-9999', 'if-h-9999', 'if-q-0', 'if-q-999'], 'if-');

        $this->assertSame(2000, $parsed['options']['w']);
        $this->assertSame(2000, $parsed['options']['h']);
        $this->assertSame(100, $parsed['options']['q']);
        $this->assertSame(['if-w-9999', 'if-h-9999', 'if-q-0', 'if-q-999'], $parsed['consumed']);
    }

    public function test_unknown_prefixed_classes_are_not_consumed(): void
    {
        $parsed = Dsl::parse(['if-custom', 'if-w-480'], 'if-');

        $this->assertSame(['if-w-480'], $parsed['consumed']);
        $this->assertSame(480, $parsed['options']['w']);
    }
}
