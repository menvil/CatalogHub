<?php

namespace App\Console\Commands;

use App\Queries\Categories\CategoryHierarchyDiagnosticsQuery;
use Illuminate\Console\Command;

final class DiagnoseCategoryHierarchyCommand extends Command
{
    protected $signature = 'catalog:diagnose-category-hierarchy';

    protected $description = 'Report legacy Category hierarchy invariants without modifying data';

    public function handle(CategoryHierarchyDiagnosticsQuery $diagnostics): int
    {
        $report = $diagnostics->report();
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $report['issues'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
