<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminComponentContractTest extends TestCase
{
    #[DataProvider('componentProvider')]
    public function test_every_phase_component_exists_and_is_represented_by_the_gallery(string $component, string $section, string $outputMarker): void
    {
        $root = dirname(__DIR__, 3);
        $componentPath = $root.'/resources/views/components/'.str_replace('.', '/', $component).'.blade.php';

        self::assertFileExists($componentPath);
        $this->get('/dev/component-gallery?mode=components&section='.$section)
            ->assertOk()
            ->assertSee($outputMarker, false);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function componentProvider(): iterable
    {
        yield 'button' => ['ui/button', 'forms', 'data-ui-button="primary"'];
        yield 'action group' => ['ui/action-group', 'forms', 'data-ui-action-group'];
        yield 'input' => ['ui/form/input', 'forms', 'data-ui-form-field="gallery-name"'];
        yield 'textarea' => ['ui/form/textarea', 'forms', 'data-ui-form-field="gallery-description"'];
        yield 'slug' => ['ui/form/slug-input', 'forms', 'data-ui-form-field="gallery-slug"'];
        yield 'select' => ['ui/form/select', 'forms', 'data-ui-form-field="gallery-status"'];
        yield 'multi select' => ['ui/form/multi-select', 'forms', 'data-ui-form-field="gallery-markets"'];
        yield 'checkbox' => ['ui/form/checkbox', 'forms', 'id="gallery-featured"'];
        yield 'toggle' => ['ui/form/toggle', 'forms', 'data-ui-toggle-indicator'];
        yield 'radio' => ['ui/form/radio-group', 'forms', 'id="gallery-source-0"'];
        yield 'date time' => ['ui/form/date-time', 'forms', 'data-ui-form-field="gallery-publish-at"'];
        yield 'file' => ['ui/form/file-input', 'forms', 'data-ui-form-field="gallery-file"'];
        yield 'form state' => ['ui/form/form-state', 'forms', 'data-admin-form-state'];
        yield 'card' => ['admin/card', 'forms', 'data-admin-card="default"'];
        yield 'section' => ['ui/section', 'forms', 'data-ui-section'];
        yield 'tabs' => ['admin/tabs', 'forms', 'data-admin-tabs'];
        yield 'detail layout' => ['admin/detail-layout', 'forms', 'data-admin-detail-layout'];
        yield 'data table' => ['admin/data-table', 'tables', 'data-admin-data-table'];
        yield 'toolbar' => ['admin/table-toolbar', 'tables', 'data-admin-table-toolbar'];
        yield 'pagination' => ['admin/pagination', 'tables', 'aria-label="Table pagination"'];
        yield 'filter bar' => ['admin/filter-bar', 'tables', 'data-admin-filter-bar'];
        yield 'active filters' => ['admin/active-filters', 'tables', 'data-admin-active-filters'];
        yield 'bulk actions' => ['admin/bulk-actions', 'tables', 'data-admin-bulk-actions="gallery-brands"'];
        yield 'row actions' => ['admin/row-actions', 'tables', 'data-admin-row-actions="brand-1"'];
        yield 'status' => ['ui/status-badge', 'feedback', 'data-ui-status="success"'];
        yield 'identifier' => ['ui/identifier', 'feedback', 'data-ui-identifier'];
        yield 'timestamp' => ['ui/timestamp', 'feedback', 'data-ui-timestamp'];
        yield 'reference' => ['ui/reference', 'feedback', 'data-ui-reference'];
        yield 'modal' => ['ui/modal', 'feedback', 'data-admin-modal="gallery-modal"'];
        yield 'confirmation' => ['ui/confirmation-dialog', 'feedback', 'data-destructive-confirmation="true"'];
        yield 'alert' => ['ui/alert', 'feedback', 'data-ui-alert="warning"'];
        yield 'toast' => ['ui/toast', 'feedback', 'data-ui-toast'];
        yield 'retry' => ['ui/retry-block', 'feedback', 'data-ui-retry-block'];
        yield 'empty state' => ['ui/states/empty', 'states', 'data-ui-screen-state="empty"'];
        yield 'filtered empty state' => ['ui/states/filtered-empty', 'states', 'data-ui-screen-state="filtered-empty"'];
        yield 'loading state' => ['ui/states/loading', 'states', 'data-ui-screen-state="loading"'];
    }

    public function test_field_and_label_are_covered_through_the_rendered_input_composition_pipeline(): void
    {
        $html = Blade::render('<x-ui.form.input id="brand-name" name="name" label="Brand name" value="Acme" />');

        self::assertStringContainsString('data-ui-form-field="brand-name"', $html);
        self::assertMatchesRegularExpression('/<label[^>]*for="brand-name"/', $html);
        self::assertStringContainsString('id="brand-name" name="name" type="text" value="Acme"', $html);
    }
}
