<?php

namespace Tests\Unit;

use Tests\TestCase;

class ImportsConfigTest extends TestCase
{
    public function test_import_artifacts_use_the_documented_imports_disk_by_default(): void
    {
        $this->assertSame('imports', config('imports.artifact_disk'));
    }
}
