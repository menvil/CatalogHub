<?php

namespace Tests\Unit\Architecture;

use CatalogHub\PHPStan\Architecture\ArchitectureDebtScanner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ArchitectureDebtScannerTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/cataloghub-architecture-debt-'.bin2hex(random_bytes(8));
        mkdir($this->fixtureRoot.'/tools/architecture', 0777, true);
        mkdir($this->fixtureRoot.'/app', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->fixtureRoot);

        parent::tearDown();
    }

    #[Test]
    public function the_project_has_no_untracked_or_stale_suppressions(): void
    {
        $report = (new ArchitectureDebtScanner(base_path()))->scan();

        $this->assertTrue($report['valid']);
        $this->assertSame(0, $report['active']);
        $this->assertSame(0, $report['actual']);
    }

    #[Test]
    public function it_reports_unregistered_inline_suppressions(): void
    {
        $this->writeRegistry([]);
        file_put_contents($this->fixtureRoot.'/app/Example.php', "<?php\n// @phpstan-ignore argument.type\n");

        $report = (new ArchitectureDebtScanner($this->fixtureRoot))->scan();

        $this->assertFalse($report['valid']);
        $this->assertSame(['phpstan_inline:app/Example.php:argument.type'], $report['unregistered']);
    }

    #[Test]
    public function it_accepts_exact_registered_debt_and_rejects_stale_entries(): void
    {
        file_put_contents($this->fixtureRoot.'/app/Example.php', "<?php\n// @phpstan-ignore argument.type\n");
        $this->writeRegistry([[
            'id' => 'ARCH-DEBT-001',
            'source' => 'phpstan_inline',
            'file' => 'app/Example.php',
            'identifier' => 'argument.type',
            'count' => 1,
            'reason' => 'Temporary fixture debt.',
            'owner' => 'architecture',
            'target' => 'ARCH-999',
            'expiresOn' => '2099-12-31',
        ]]);

        $scanner = new ArchitectureDebtScanner($this->fixtureRoot);
        $this->assertSame([], $scanner->scan()['stale']);

        file_put_contents($this->fixtureRoot.'/app/Example.php', "<?php\n");
        $report = $scanner->scan();

        $this->assertFalse($report['valid']);
        $this->assertSame(['phpstan_inline:app/Example.php:argument.type'], $report['stale']);
    }

    #[Test]
    public function it_reports_unregistered_phpstan_baseline_entries(): void
    {
        $this->writeRegistry([]);
        file_put_contents($this->fixtureRoot.'/app/Example.php', "<?php\n");
        file_put_contents($this->fixtureRoot.'/phpstan-baseline.neon', <<<'NEON'
parameters:
    ignoreErrors:
        -
            message: '#example#'
            identifier: argument.type
            count: 2
            path: app/Example.php
NEON);

        $report = (new ArchitectureDebtScanner($this->fixtureRoot))->scan();

        $this->assertFalse($report['valid']);
        $this->assertSame(2, $report['baseline']);
        $this->assertSame(['phpstan_baseline:app/Example.php:argument.type'], $report['unregistered']);
    }

    #[Test]
    public function it_rejects_expired_registered_debt(): void
    {
        file_put_contents($this->fixtureRoot.'/app/Example.php', "<?php\n// @phpstan-ignore argument.type\n");
        $this->writeRegistry([[
            'id' => 'ARCH-DEBT-001',
            'source' => 'phpstan_inline',
            'file' => 'app/Example.php',
            'identifier' => 'argument.type',
            'count' => 1,
            'reason' => 'Expired fixture debt.',
            'owner' => 'architecture',
            'target' => 'ARCH-999',
            'expiresOn' => '2000-01-01',
        ]]);

        $report = (new ArchitectureDebtScanner($this->fixtureRoot))->scan();

        $this->assertFalse($report['valid']);
        $this->assertSame(['ARCH-DEBT-001'], $report['expired']);
    }

    /** @param list<array<string, mixed>> $suppressions */
    private function writeRegistry(array $suppressions): void
    {
        file_put_contents(
            $this->fixtureRoot.'/tools/architecture/debt.json',
            json_encode(['version' => 1, 'suppressions' => $suppressions], JSON_THROW_ON_ERROR),
        );
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $target = $path.'/'.$file;
            is_dir($target) ? $this->removeDirectory($target) : unlink($target);
        }

        rmdir($path);
    }
}
