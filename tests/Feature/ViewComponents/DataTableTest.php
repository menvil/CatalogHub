<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\View\Components\Admin\DataTable;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @param  list<mixed>  $columns
     * @param  list<mixed>  $rows
     */
    #[DataProvider('invalidTableProvider')]
    public function test_table_rejects_invalid_column_and_row_boundaries(array $columns, array $rows, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new DataTable(caption: 'Brands', columns: $columns, rows: $rows);
    }

    /** @return iterable<string, array{list<mixed>, list<mixed>, string}> */
    public static function invalidTableProvider(): iterable
    {
        yield 'empty columns' => [[], [], 'columns cannot be empty'];
        yield 'non-array column' => [['invalid'], [], 'columns must be arrays'];
        yield 'duplicate columns' => [[['key' => 'name', 'label' => 'Name'], ['key' => 'name', 'label' => 'Again']], [], 'column keys must be unique'];
        yield 'invalid alignment' => [[['key' => 'name', 'label' => 'Name', 'align' => 'sideways']], [], 'Unsupported data table alignment'];
        yield 'non-array row' => [[['key' => 'name', 'label' => 'Name']], ['invalid'], 'rows must be arrays'];
        yield 'empty row ID' => [[['key' => 'name', 'label' => 'Name']], [['id' => '', 'name' => 'Acme']], 'non-empty [id] identifier'];
        yield 'non-scalar row ID' => [[['key' => 'name', 'label' => 'Name']], [['id' => [], 'name' => 'Acme']], 'scalar [id] identifier'];
    }

    public function test_table_rejects_an_empty_accessible_caption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('captions cannot be empty');

        new DataTable(caption: '  ', columns: [['key' => 'name', 'label' => 'Name']], rows: []);
    }
}
