<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AttributeGlobalization\FinalizeSchemaConsumersV2;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;

final class FinalizeSchemaConsumersV2Command extends Command
{
    protected $signature = 'catalog:finalize-schema-consumer-v2 {--actor= : Active Central schema operator ID}';

    protected $description = 'Rebuild and verify v2 derived output after contraction, then resume identity writes';

    public function handle(FinalizeSchemaConsumersV2 $finalizer): int
    {
        $actor = User::query()->find((int) $this->option('actor'));
        if ($actor === null) {
            throw new AuthorizationException;
        }
        $this->line(json_encode($finalizer->run($actor), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
