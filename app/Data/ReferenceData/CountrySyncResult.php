<?php

declare(strict_types=1);

namespace App\Data\ReferenceData;

final readonly class CountrySyncResult
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $unchanged,
        public int $deactivated,
        public int $translationsCreated,
        public int $translationsUpdated,
        public int $translationsUnchanged,
        public bool $dryRun,
    ) {}
}
