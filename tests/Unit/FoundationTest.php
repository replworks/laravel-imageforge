<?php

namespace Replworks\ImageForge\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;
use Replworks\ImageForge\ImageForgeServiceProvider;

class FoundationTest extends TestCase
{
    public function test_the_service_provider_is_available(): void
    {
        $this->assertTrue(is_subclass_of(ImageForgeServiceProvider::class, ServiceProvider::class));
    }
}
