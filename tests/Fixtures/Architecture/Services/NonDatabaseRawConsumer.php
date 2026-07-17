<?php

namespace App\Services\ArchitectureFixtures;

final class NonDatabaseRawConsumer
{
    public function query(NonDatabaseRawApi $api): void
    {
        $api->whereRaw('not sql');
    }
}
