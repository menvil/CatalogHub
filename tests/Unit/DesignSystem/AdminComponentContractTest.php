<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminComponentContractTest extends TestCase
{
    #[DataProvider('componentProvider')]
    public function test_every_phase_component_exists_and_is_represented_by_the_gallery(string $component, string $galleryNeedle): void
    {
        $root = dirname(__DIR__, 3);
        $componentPath = $root.'/resources/views/components/'.str_replace('.', '/', $component).'.blade.php';
        $gallery = (string) file_get_contents($root.'/resources/views/central/partials/admin-component-gallery.blade.php');

        self::assertFileExists($componentPath);
        self::assertStringContainsString($galleryNeedle, $gallery, "Component [{$component}] has no deterministic gallery fixture.");
    }

    /** @return iterable<string, array{string, string}> */
    public static function componentProvider(): iterable
    {
        yield 'button' => ['ui/button', '<x-ui.button'];
        yield 'action group' => ['ui/action-group', '<x-ui.action-group'];
        yield 'input' => ['ui/form/input', '<x-ui.form.input'];
        yield 'textarea' => ['ui/form/textarea', '<x-ui.form.textarea'];
        yield 'slug' => ['ui/form/slug-input', '<x-ui.form.slug-input'];
        yield 'select' => ['ui/form/select', '<x-ui.form.select'];
        yield 'multi select' => ['ui/form/multi-select', '<x-ui.form.multi-select'];
        yield 'checkbox' => ['ui/form/checkbox', '<x-ui.form.checkbox'];
        yield 'toggle' => ['ui/form/toggle', '<x-ui.form.toggle'];
        yield 'radio' => ['ui/form/radio-group', '<x-ui.form.radio-group'];
        yield 'date time' => ['ui/form/date-time', '<x-ui.form.date-time'];
        yield 'file' => ['ui/form/file-input', '<x-ui.form.file-input'];
        yield 'form state' => ['ui/form/form-state', '<x-ui.form.form-state'];
        yield 'card' => ['admin/card', '<x-admin.card'];
        yield 'section' => ['ui/section', '<x-ui.section'];
        yield 'tabs' => ['admin/tabs', '<x-admin.tabs'];
        yield 'detail layout' => ['admin/detail-layout', '<x-admin.detail-layout'];
        yield 'data table' => ['admin/data-table', '<x-admin.data-table'];
        yield 'toolbar' => ['admin/table-toolbar', '<x-admin.table-toolbar'];
        yield 'pagination' => ['admin/pagination', '<x-admin.pagination'];
        yield 'filter bar' => ['admin/filter-bar', '<x-admin.filter-bar'];
        yield 'active filters' => ['admin/active-filters', '<x-admin.active-filters'];
        yield 'bulk actions' => ['admin/bulk-actions', '<x-admin.bulk-actions'];
        yield 'row actions' => ['admin/row-actions', '<x-admin.row-actions'];
        yield 'status' => ['ui/status-badge', '<x-ui.status-badge'];
        yield 'identifier' => ['ui/identifier', '<x-ui.identifier'];
        yield 'timestamp' => ['ui/timestamp', '<x-ui.timestamp'];
        yield 'reference' => ['ui/reference', '<x-ui.reference'];
        yield 'modal' => ['ui/modal', '<x-ui.modal'];
        yield 'confirmation' => ['ui/confirmation-dialog', '<x-ui.confirmation-dialog'];
        yield 'alert' => ['ui/alert', '<x-ui.alert'];
        yield 'toast' => ['ui/toast', '<x-ui.toast'];
        yield 'retry' => ['ui/retry-block', '<x-ui.retry-block'];
    }

    public function test_field_and_label_are_covered_through_the_rendered_input_composition_pipeline(): void
    {
        $root = dirname(__DIR__, 3);
        $gallery = (string) file_get_contents($root.'/resources/views/central/partials/admin-component-gallery.blade.php');
        $input = (string) file_get_contents($root.'/resources/views/components/ui/form/input.blade.php');
        $field = (string) file_get_contents($root.'/resources/views/components/ui/form/field.blade.php');

        self::assertStringContainsString('<x-ui.form.input', $gallery);
        self::assertStringContainsString('<x-ui.form.field', $input);
        self::assertStringContainsString('<x-ui.form.label', $field);
    }
}
