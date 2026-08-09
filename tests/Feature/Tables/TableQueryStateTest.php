<?php

declare(strict_types=1);

namespace Tests\Feature\Tables;

use App\Support\Tables\TableQueryState;
use PHPUnit\Framework\TestCase;

final class TableQueryStateTest extends TestCase
{
    public function test_state_whitelists_sort_and_normalizes_search_page_and_direction(): void
    {
        $state = TableQueryState::from(
            ['sort' => 'unsafe', 'direction' => 'sideways', 'q' => '  Acme  ', 'page' => '-4'],
            ['name', 'status'],
            'name',
        );

        self::assertSame('name', $state->sort);
        self::assertSame('asc', $state->direction);
        self::assertSame('Acme', $state->search);
        self::assertSame(1, $state->page);
    }

    public function test_sort_toggle_and_pagination_preserve_relevant_query_state(): void
    {
        $state = TableQueryState::from(
            ['sort' => 'name', 'direction' => 'asc', 'q' => 'Acme', 'page' => '3', 'status' => 'active'],
            ['name', 'status'],
            'name',
            ['status'],
        );

        self::assertSame([
            'q' => 'Acme', 'sort' => 'name', 'direction' => 'desc', 'status' => 'active',
        ], $state->forSort('name'));
        self::assertSame([
            'q' => 'Acme', 'sort' => 'name', 'direction' => 'asc', 'page' => 4, 'status' => 'active',
        ], $state->forPage(4));
        self::assertSame('asc', $state->directionFor('status'));
    }

    public function test_reserved_query_keys_cannot_be_registered_as_filters(): void
    {
        $state = TableQueryState::from(
            ['q' => 'Acme', 'sort' => 'name', 'direction' => 'desc', 'page' => '3', 'status' => 'active'],
            ['name'],
            'name',
            ['q', 'sort', 'direction', 'page', 'status'],
        );

        self::assertSame(['status' => 'active'], $state->filters);
        self::assertSame([
            'q' => 'Acme', 'sort' => 'name', 'direction' => 'desc', 'page' => 2, 'status' => 'active',
        ], $state->forPage(2));
    }
}
