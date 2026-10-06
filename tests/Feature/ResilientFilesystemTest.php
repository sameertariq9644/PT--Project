<?php

namespace Tests\Feature;

use App\Support\ResilientFilesystem;
use Tests\TestCase;

class ResilientFilesystemTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/resilient-filesystem');

        $this->app['files']->ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_the_blade_compiler_uses_the_resilient_filesystem(): void
    {
        $this->assertInstanceOf(ResilientFilesystem::class, $this->app['files']);
    }

    public function test_replace_writes_and_overwrites_a_file_without_leaving_temp_files(): void
    {
        $path = $this->directory.DIRECTORY_SEPARATOR.'compiled.php';

        $this->app['files']->replace($path, 'first');
        $this->app['files']->replace($path, 'second');

        $this->assertSame('second', file_get_contents($path));
        $this->assertCount(1, $this->app['files']->files($this->directory));
    }
}
