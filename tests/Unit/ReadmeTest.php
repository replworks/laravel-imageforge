<?php

namespace Replworks\ImageForge\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    public function test_the_first_readme_line_identifies_the_package_as_an_image_forge_client(): void
    {
        $firstLine = strtok(file_get_contents(__DIR__.'/../../README.md'), "\n");

        $this->assertSame('This package is a client for the Image Forge service.', $firstLine);
    }
}
