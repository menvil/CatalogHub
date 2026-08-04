<?php

namespace Tests\Unit\Auth;

use App\Enums\Permission;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    public function test_registry_contains_only_documented_unique_permission_names(): void
    {
        $expected = [
            'central.panel.access',
            'central.page.access',
            'central.mutation.execute',
            'site.panel.access',
            'site.page.access',
            'site.mutation.execute',
            'central.view',
            'central.manage',
            'catalog.products.manage',
            'catalog.categories.manage',
            'catalog.schema.manage',
            'imports.manage',
            'media.manage',
            'translations.manage',
            'sites.manage',
            'site.settings.manage',
            'site.content.manage',
            'reviews.moderate',
            'leads.manage',
            'prices.manage',
            'backups.manage',
            'corrections.request',
            'corrections.review',
        ];

        $values = Permission::values();

        $this->assertSame($expected, $values);
        $this->assertSame($values, array_values(array_unique($values)));
        $this->assertSame($values, config('cataloghub_permissions.permissions'));

        foreach ($values as $permission) {
            $this->assertMatchesRegularExpression('/^[a-z]+(?:\.[a-z]+)+$/', $permission);
        }
    }
}
