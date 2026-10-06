<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AttributeGlobalization\AttributeReconciliationWriter;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

final class ReconcileAttributeGlobalizationCommand extends Command
{
    protected $signature = 'catalog:reconcile-attribute-globalization {plan : Reviewed JSON plan path} {--actor= : Active Central schema actor ID} {--apply : Apply the complete validated plan; default is dry-run}';

    protected $description = 'Validate explicit identity decisions without inferring canonical equivalence';

    public function handle(AttributeReconciliationWriter $writer): int
    {
        $path = $this->argument('plan');
        if (! is_file($path) || filesize($path) > 2097152) {
            throw ValidationException::withMessages(['plan' => 'Provide a reviewed JSON plan of at most 2 MiB.']);
        }
        $plan = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($plan)) {
            throw ValidationException::withMessages(['plan' => 'Plan must be a JSON object.']);
        }
        $result = $writer->run($plan, (bool) $this->option('apply'), User::query()->find((int) $this->option('actor')));
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $result['report']['cutover_ready'] ? self::SUCCESS : self::FAILURE;
    }
}
