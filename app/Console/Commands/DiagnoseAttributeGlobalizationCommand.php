<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use Illuminate\Console\Command;

final class DiagnoseAttributeGlobalizationCommand extends Command
{
    protected $signature = 'catalog:diagnose-attribute-globalization {--actor= : Active Central schema operator ID}';

    protected $description = 'Read-only, deterministic attribute identity and membership reconciliation report';

    public function handle(AttributeGlobalizationDiagnosticsQuery $query): int
    {
        $actor = User::query()->find((int) $this->option('actor'));
        $report = $query->report($actor);
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $report['cutover_ready'] ? self::SUCCESS : self::FAILURE;
    }
}
