<?php

declare(strict_types=1);

namespace App\Support\DesignSystem;

use Carbon\CarbonImmutable;

final class AdminComponentGalleryFixture
{
    public const VERSION = 'admin-components-v1';

    /** @var list<array{key: string, label: string, align?: string}> */
    public const COLUMNS = [
        ['key' => 'name', 'label' => 'Brand'],
        ['key' => 'code', 'label' => 'Code'],
        ['key' => 'status', 'label' => 'Status'],
        ['key' => 'updated', 'label' => 'Updated', 'align' => 'end'],
    ];

    /** @var list<array{id: string, name: string, code: string, status: string, updated: string}> */
    public const ROWS = [
        ['id' => 'brand-1', 'name' => 'Acme Displays', 'code' => 'ACME', 'status' => 'Active', 'updated' => '2026-08-05'],
        ['id' => 'brand-2', 'name' => 'Northstar Labs', 'code' => 'NORTHSTAR', 'status' => 'Draft', 'updated' => '2026-08-04'],
        ['id' => 'brand-3', 'name' => 'Helios Systems', 'code' => 'HELIOS', 'status' => 'Needs review', 'updated' => '2026-08-03'],
    ];

    /** @var array<string, string> */
    public const OPTIONS = [
        'active' => 'Active',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ];

    /** @var list<array{key: string, label: string, removeUrl: string}> */
    public const FILTERS = [
        ['key' => 'status', 'label' => 'Status: Active', 'removeUrl' => '?mode=components&section=tables'],
        ['key' => 'market', 'label' => 'Market: Germany', 'removeUrl' => '?mode=components&section=tables&status=active'],
    ];

    public static function timestamp(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-08-05 10:15:00', 'UTC');
    }

    private function __construct() {}
}
