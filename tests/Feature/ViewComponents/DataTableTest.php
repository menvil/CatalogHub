<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\View\Components\Admin\DataTable;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Tests\TestCase;

final class DataTableTest extends TestCase
{
    public function test_table_renders_caption_headers_rows_and_escaped_values(): void
    {
        $columns = [
            ['key' => 'name', 'label' => 'Brand'],
            ['key' => 'status', 'label' => 'Status', 'align' => 'end'],
        ];
        $rows = [
            ['id' => 'brand-1', 'name' => '<Unsafe>', 'status' => 'Active'],
            ['id' => 'brand-2', 'name' => 'Acme', 'status' => 'Draft'],
        ];
        $html = Blade::render(
            '<x-admin.data-table caption="Brands" :columns="$columns" :rows="$rows" />',
            compact('columns', 'rows'),
        );

        $this->assertStringContainsString('<caption', $html);
        $this->assertStringContainsString('scope="col"', $html);
        $this->assertStringContainsString('data-row-id="brand-1"', $html);
        $this->assertStringContainsString('&lt;Unsafe&gt;', $html);
        $this->assertStringNotContainsString('<Unsafe>', $html);
        $this->assertStringContainsString('overflow-x-auto', $html);
    }

    public function test_table_renders_truthful_empty_state(): void
    {
        $html = Blade::render(
            '<x-admin.data-table caption="Brands" :columns="$columns" :rows="[]" empty="No brands match." />',
            ['columns' => [['key' => 'name', 'label' => 'Brand']]],
        );

        $this->assertStringContainsString('No brands match.', $html);
        $this->assertStringContainsString('colspan="1"', $html);
    }

    public function test_table_rejects_duplicate_row_identifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data table row identifiers must be unique');

        new DataTable(
            caption: 'Brands',
            columns: [['key' => 'name', 'label' => 'Brand']],
            rows: [['id' => 'same', 'name' => 'One'], ['id' => 'same', 'name' => 'Two']],
        );
    }
}
