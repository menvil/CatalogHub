<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\RuntimePlatformChecker;
use Illuminate\Console\Command;

final class CheckRuntimePlatformCommand extends Command
{
    protected $signature = 'cataloghub:platform-check';

    protected $description = 'Verify the supported PHP and PostgreSQL production runtime versions';

    public function handle(RuntimePlatformChecker $checker): int
    {
        $result = $checker->check();

        $this->line("PHP: {$result->phpVersion}");
        $this->line("Database driver: {$result->databaseDriver}");
        $this->line('PostgreSQL: '.($result->postgresVersion ?? 'unavailable'));

        foreach ($result->failures as $failure) {
            $this->error($failure);
        }

        if (! $result->supported) {
            return self::FAILURE;
        }

        $this->info('Runtime platform meets the CatalogHub production minimums.');

        return self::SUCCESS;
    }
}
