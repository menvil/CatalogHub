<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

final class DatabaseQueryRecorder
{
    /** @var list<string> */
    private array $queries = [];

    public function __construct()
    {
        DB::listen($this->record(...));
    }

    public function reset(): void
    {
        $this->queries = [];
    }

    /** @return list<string> */
    public function queries(): array
    {
        return $this->queries;
    }

    private function record(QueryExecuted $query): void
    {
        $this->queries[] = strtolower($query->sql);
    }
}
