<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\PermissionMatrix;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    public function test_super_admin_has_all_permissions(): void
    {
        $matrix = app(PermissionMatrix::class);
        $user = new User(['role' => UserRole::SuperAdmin]);

        foreach ($matrix->permissions() as $permission) {
            $this->assertTrue($user->hasCatalogHubPermission($permission), $permission);
        }
    }

    public function test_central_admin_can_manage_central_catalog_foundation(): void
    {
        $user = new User(['role' => UserRole::CentralAdmin]);

        $this->assertTrue($user->hasCatalogHubPermission('central.manage'));
        $this->assertTrue($user->hasCatalogHubPermission('catalog.schema.manage'));
    }

    public function test_catalog_editor_cannot_manage_site_settings(): void
    {
        $user = new User(['role' => UserRole::CatalogEditor]);

        $this->assertFalse($user->hasCatalogHubPermission('site.settings.manage'));
        $this->assertTrue($user->hasCatalogHubPermission('catalog.brands.manage'));
    }

    public function test_site_admin_cannot_manage_central_schema(): void
    {
        $user = new User(['role' => UserRole::SiteAdmin]);

        $this->assertFalse($user->hasCatalogHubPermission('catalog.schema.manage'));
    }

    public function test_translator_can_manage_translations_only(): void
    {
        $user = new User(['role' => UserRole::Translator]);

        $this->assertTrue($user->hasCatalogHubPermission('translations.manage'));
        $this->assertTrue($user->hasCatalogHubPermission('central.view'));
        $this->assertFalse($user->hasCatalogHubPermission('catalog.products.manage'));
        $this->assertFalse($user->hasCatalogHubPermission('catalog.brands.manage'));
        $this->assertFalse($user->hasCatalogHubPermission('site.content.manage'));
    }

    #[DataProvider('brandPermissionProvider')]
    public function test_brand_permission_role_matrix(UserRole $role, bool $allowed): void
    {
        $user = new User(['role' => $role]);

        $this->assertSame($allowed, $user->hasCatalogHubPermission('catalog.brands.manage'));
    }

    public function test_product_and_brand_permissions_are_independent(): void
    {
        config()->set('cataloghub_permissions.roles.catalog_editor', ['catalog.products.manage']);
        $user = new User(['role' => UserRole::CatalogEditor]);

        $this->assertTrue($user->hasCatalogHubPermission('catalog.products.manage'));
        $this->assertFalse($user->hasCatalogHubPermission('catalog.brands.manage'));

        config()->set('cataloghub_permissions.roles.catalog_editor', ['catalog.brands.manage']);
        $this->assertFalse($user->hasCatalogHubPermission('catalog.products.manage'));
        $this->assertTrue($user->hasCatalogHubPermission('catalog.brands.manage'));
    }

    /** @return array<string, array{UserRole, bool}> */
    public static function brandPermissionProvider(): array
    {
        return [
            'super admin' => [UserRole::SuperAdmin, true],
            'central admin' => [UserRole::CentralAdmin, true],
            'catalog editor' => [UserRole::CatalogEditor, true],
            'translator' => [UserRole::Translator, false],
            'site admin' => [UserRole::SiteAdmin, false],
            'moderator' => [UserRole::Moderator, false],
        ];
    }

    public function test_moderator_can_moderate_reviews_and_leads_only(): void
    {
        $user = new User(['role' => UserRole::Moderator]);

        $this->assertTrue($user->hasCatalogHubPermission('reviews.moderate'));
        $this->assertTrue($user->hasCatalogHubPermission('leads.manage'));
        $this->assertFalse($user->hasCatalogHubPermission('catalog.schema.manage'));
    }

    #[DataProvider('rolesProvider')]
    public function test_permissions_matrix_knows_each_role(UserRole $role): void
    {
        $this->assertIsArray(config("cataloghub_permissions.roles.{$role->value}"));
    }

    public function test_unknown_permission_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PermissionMatrix::class)->allows(UserRole::CatalogEditor, 'unknown.permission');
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function rolesProvider(): array
    {
        return [
            'super_admin' => [UserRole::SuperAdmin],
            'central_admin' => [UserRole::CentralAdmin],
            'catalog_editor' => [UserRole::CatalogEditor],
            'site_admin' => [UserRole::SiteAdmin],
            'translator' => [UserRole::Translator],
            'moderator' => [UserRole::Moderator],
        ];
    }
}
