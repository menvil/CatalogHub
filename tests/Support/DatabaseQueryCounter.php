<?php

namespace Tests\Support;

use Closure;
use Illuminate\Support\Facades\DB;

final class DatabaseQueryCounter
{
    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $operation
     * @return array{result: TResult, count: int}
     */
    public static function measure(Closure $operation): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $result = $operation();
            $count = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }

        return ['result' => $result, 'count' => $count];
    }
}
