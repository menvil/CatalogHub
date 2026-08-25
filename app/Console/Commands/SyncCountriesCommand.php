<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReferenceData\CountrySynchronizer;
use Illuminate\Console\Command;
use Throwable;

final class SyncCountriesCommand extends Command
{
    protected $signature = 'reference:countries:sync {--dry-run : Validate and report changes without writing}';

    protected $description = 'Synchronize Countries from the committed versioned reference dataset';

    public function handle(CountrySynchronizer $synchronizer): int
    {
        try {
            $result = $synchronizer->sync(dryRun: (bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info($result->dryRun ? 'Country reference dry run complete.' : 'Country reference sync complete.');
        $this->table(['Metric', 'Count'], [
            ['Countries created', $result->created],
            ['Countries updated', $result->updated],
            ['Countries unchanged', $result->unchanged],
            ['Countries deactivated', $result->deactivated],
            ['Translations created', $result->translationsCreated],
            ['Translations updated', $result->translationsUpdated],
            ['Translations unchanged', $result->translationsUnchanged],
        ]);

        return self::SUCCESS;
    }
}
