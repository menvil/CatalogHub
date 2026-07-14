<?php

namespace App\Filament\Support;

use App\Filament\Resources\SiteProductProjectionResource;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\SiteProductProjection;
use App\Models\User;

final class ProjectionResourceSupport
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }

    public static function prettyJson(mixed $value): string
    {
        return (string) json_encode(
            $value ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    public static function productPreviewUrl(
        mixed $siteId,
        mixed $entityType,
        mixed $entityId,
        ?string $locale = null,
    ): ?string {
        if (! in_array($entityType, ['product', CentralProduct::class], true)) {
            return null;
        }

        $query = SiteProductProjection::query()
            ->where('site_id', (int) $siteId)
            ->where('central_product_id', (int) $entityId);

        if ($locale !== null && $locale !== '') {
            $query->where('locale', $locale);
        }

        $projection = $query->latest('built_at')->latest('id')->first();

        return $projection instanceof SiteProductProjection
            ? SiteProductProjectionResource::getUrl('view', ['record' => $projection])
            : null;
    }
}
