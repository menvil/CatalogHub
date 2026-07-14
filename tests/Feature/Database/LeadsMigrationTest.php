<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_table_has_required_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('leads'));
        $this->assertTrue(Schema::hasColumns('leads', [
            'id',
            'site_id',
            'central_product_id',
            'central_category_id',
            'type',
            'status',
            'name',
            'email',
            'phone',
            'city',
            'message',
            'locale',
            'source',
            'consent_accepted_at',
            'metadata',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('leads'));

        foreach ([
            ['site_id', 'status'],
            ['site_id', 'type'],
            ['site_id', 'central_product_id'],
            ['created_at'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}
