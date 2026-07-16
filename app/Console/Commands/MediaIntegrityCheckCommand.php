<?php

namespace App\Console\Commands;

use App\Services\Media\MediaIntegrityChecker;
use Illuminate\Console\Command;

final class MediaIntegrityCheckCommand extends Command
{
    protected $signature = 'cataloghub:media-integrity-check';

    protected $description = 'Check that media originals and variants exist without modifying media files';

    public function handle(MediaIntegrityChecker $checker): int
    {
        $result = $checker->run();

        foreach ($result->missingPaths as $path) {
            $this->error("Missing: {$path}");
        }

        if ($result->hasMissingFiles()) {
            $count = count($result->missingPaths);
            $this->error("Integrity check found {$count} missing file(s) across {$result->assetCount} asset(s).");

            return self::FAILURE;
        }

        $this->info("All {$result->checkedFileCount} media files are present across {$result->assetCount} asset(s).");

        return self::SUCCESS;
    }
}
