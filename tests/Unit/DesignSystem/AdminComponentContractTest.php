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
        yield 'button' => ['ui/button', 'actions', 'data-ui-button="primary"'];
        yield 'action group' => ['ui/action-group', 'actions', 'data-ui-action-group'];
        yield 'input' => ['ui/form/input', 'forms', 'data-ui-form-field="gallery-name"'];
        yield 'textarea' => ['ui/form/textarea', 'forms', 'data-ui-form-field="gallery-description"'];
        yield 'slug' => ['ui/form/slug-input', 'forms', 'data-ui-form-field="gallery-slug"'];
        yield 'select' => ['ui/form/select', 'forms', 'data-ui-form-field="gallery-status"'];
        yield 'multi select' => ['ui/form/multi-select', 'forms', 'data-ui-form-field="gallery-markets"'];
        yield 'checkbox list' => ['ui/form/checkbox-list', 'forms', 'data-ui-checkbox-list="gallery-market-checkboxes"'];
        yield 'checkbox' => ['ui/form/checkbox', 'forms', 'id="gallery-featured"'];
        yield 'toggle' => ['ui/form/toggle', 'forms', 'data-ui-toggle-indicator'];
        yield 'radio' => ['ui/form/radio-group', 'forms', 'id="gallery-source-0"'];
        yield 'date time' => ['ui/form/date-time', 'forms', 'data-ui-form-field="gallery-publish-at"'];
        yield 'date picker' => ['ui/form/date-picker', 'forms', 'data-ui-date-picker="gallery-publish-date"'];
        yield 'file' => ['ui/form/file-input', 'forms', 'data-ui-form-field="gallery-file"'];
        yield 'form state' => ['ui/form/form-state', 'forms', 'data-admin-form-state'];
        yield 'card' => ['admin/card', 'layout', 'data-admin-card="default"'];
        yield 'section' => ['ui/section', 'layout', 'data-ui-section'];
        yield 'tabs' => ['admin/tabs', 'layout', 'data-admin-tabs'];
        yield 'detail layout' => ['admin/detail-layout', 'layout', 'data-admin-detail-layout'];
        yield 'data table' => ['admin/data-table', 'tables', 'data-admin-data-table'];
        yield 'toolbar' => ['admin/table-toolbar', 'tables', 'data-admin-table-toolbar'];
        yield 'pagination' => ['admin/pagination', 'tables', 'aria-label="Table pagination"'];
        yield 'filter bar' => ['admin/filter-bar', 'tables', 'data-admin-filter-bar'];
        yield 'active filters' => ['admin/active-filters', 'tables', 'data-admin-active-filters'];
        yield 'bulk actions' => ['admin/bulk-actions', 'tables', 'data-admin-bulk-actions="gallery-brands"'];
        yield 'row actions' => ['admin/row-actions', 'tables', 'data-admin-row-actions="brand-1"'];
        yield 'status' => ['ui/status-badge', 'indicators', 'data-ui-status="success"'];
        yield 'admin status' => ['admin/status-badge', 'indicators', 'data-admin-status-badge="success"'];
        yield 'translation status' => ['admin/translation-status-badge', 'indicators', 'data-admin-translation-status="missing"'];
        yield 'projection status' => ['admin/projection-status-badge', 'indicators', 'data-admin-projection-status="synced"'];
        yield 'quality warning' => ['admin/quality-warning-badge', 'indicators', 'data-admin-quality-warning="low"'];
        yield 'identifier' => ['ui/identifier', 'indicators', 'data-ui-identifier'];
        yield 'timestamp' => ['ui/timestamp', 'indicators', 'data-ui-timestamp'];
        yield 'reference' => ['ui/reference', 'indicators', 'data-ui-reference'];
        yield 'modal' => ['ui/modal', 'overlays', 'data-admin-modal="gallery-modal"'];
        yield 'confirmation' => ['ui/confirmation-dialog', 'overlays', 'data-destructive-confirmation="true"'];
        yield 'drawer' => ['admin/drawer', 'overlays', 'data-admin-drawer'];
        yield 'alert' => ['ui/alert', 'feedback', 'data-ui-alert="warning"'];
        yield 'toast' => ['ui/toast', 'feedback', 'data-ui-toast'];
        yield 'retry' => ['ui/retry-block', 'feedback', 'data-ui-retry-block'];
        yield 'admin empty state' => ['admin/empty-state', 'feedback', 'data-admin-empty-state="default"'];
        yield 'empty state' => ['ui/states/empty', 'states', 'data-ui-screen-state="empty"'];
        yield 'filtered empty state' => ['ui/states/filtered-empty', 'states', 'data-ui-screen-state="filtered-empty"'];
        yield 'loading state' => ['ui/states/loading', 'states', 'data-ui-screen-state="loading"'];
        yield 'action progress' => ['ui/states/action-progress', 'actions', 'data-ui-action-progress="idle"'];
        yield 'localized field editor' => ['admin/localized-field-editor', 'advanced', 'data-admin-localized-field-editor="tabs"'];
        yield 'unit value input' => ['admin/unit-value-input', 'advanced', 'data-admin-unit-value-input'];
        yield 'media picker' => ['admin/media-picker', 'advanced', 'data-admin-media-picker="multiple"'];
        yield 'diff viewer' => ['admin/diff-viewer', 'advanced', 'data-admin-diff-viewer="side-by-side"'];
        yield 'attribute value editor' => ['admin/attribute-value-editor', 'advanced', 'data-admin-attribute-value-editor="unit"'];
        yield 'import progress' => ['admin/import-progress-panel', 'advanced', 'data-admin-import-progress-panel'];
        yield 'stepper wizard' => ['admin/stepper-wizard', 'advanced', 'data-admin-stepper="horizontal"'];
        yield 'change request' => ['admin/change-request-card', 'advanced', 'data-admin-change-request-card'];
        yield 'conflict review' => ['admin/conflict-review-card', 'advanced', 'data-admin-conflict-review-card'];
        yield 'admin confirmation' => ['admin/confirmation-modal', 'advanced', 'data-admin-modal="gallery-foundation-confirmation"'];
    }

    public function test_field_and_label_are_covered_through_the_rendered_input_composition_pipeline(): void
    {
        $html = Blade::render('<x-ui.form.input id="brand-name" name="name" label="Brand name" value="Acme" />');

        self::assertStringContainsString('data-ui-form-field="brand-name"', $html);
        self::assertMatchesRegularExpression('/<label[^>]*for="brand-name"/', $html);
        self::assertStringContainsString('id="brand-name" name="name" type="text" value="Acme"', $html);
    }
}
