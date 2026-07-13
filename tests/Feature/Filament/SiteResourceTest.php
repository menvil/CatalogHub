<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\CreateSite;
use App\Filament\Resources\SiteResource\Pages\EditSite;
use App\Filament\Resources\SiteResource\Pages\ListSites;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\Site;
use Tests\TestCase;

class SiteResourceTest extends TestCase
{
    public function test_has_site_resource_and_crud_pages(): void
    {
        $this->assertTrue(class_exists(SiteResource::class));
        $this->assertSame(Site::class, SiteResource::getModel());
        $this->assertArrayHasKey('index', SiteResource::getPages());
        $this->assertArrayHasKey('create', SiteResource::getPages());
        $this->assertArrayHasKey('edit', SiteResource::getPages());
        $this->assertTrue(class_exists(ListSites::class));
        $this->assertTrue(class_exists(CreateSite::class));
        $this->assertTrue(class_exists(EditSite::class));
        $this->assertContains(SiteFeaturesRelationManager::class, SiteResource::getRelations());
    }
}
