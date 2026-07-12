<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProductMediaManagerController extends Controller
{
    private const ENTITY_TYPE = 'central_product';

    /**
     * @var list<string>
     */
    private const ROLES = ['main', 'card', 'gallery', 'hero', 'og', 'logo', 'icon', 'manual', 'package', 'technical'];

    /**
     * @var list<string>
     */
    private const SINGULAR_ROLES = ['main', 'card', 'hero', 'og', 'logo', 'icon'];

    public function show(
        Request $request,
        CentralProduct $product,
        MediaResolver $resolver,
        MediaUrlGenerator $urls,
    ): View {
        $this->authorizeMedia($request);

        $filters = $request->validate([
            'preview_role' => ['nullable', 'string', Rule::in(self::ROLES)],
            'preview_locale' => ['nullable', 'string', 'max:20', 'regex:/^[a-z]{2,3}(-[A-Z]{2})?$/'],
            'preview_site_id' => ['nullable', 'integer', 'min:1'],
            'preview_market_id' => ['nullable', 'integer', 'min:1'],
            'media_search' => ['nullable', 'string', 'max:255'],
        ]);

        $previewRole = $filters['preview_role'] ?? 'main';
        $previewLocale = $filters['preview_locale'] ?? null;
        $previewSiteId = isset($filters['preview_site_id']) ? (int) $filters['preview_site_id'] : null;
        $previewMarketId = isset($filters['preview_market_id']) ? (int) $filters['preview_market_id'] : null;

        $assignments = MediaAssignment::query()
            ->with('asset.variants')
            ->forEntity(self::ENTITY_TYPE, $product->id)
            ->orderBy('role')
            ->orderBy('position')
            ->get()
            ->groupBy('role');

        $assetSearch = trim((string) ($filters['media_search'] ?? ''));
        $assets = MediaAsset::query()
            ->when($assetSearch !== '', function ($query) use ($assetSearch): void {
                $query->where(function ($query) use ($assetSearch): void {
                    $query->where('original_filename', 'like', "%{$assetSearch}%")
                        ->orWhere('checksum', 'like', "%{$assetSearch}%");

                    if (ctype_digit($assetSearch)) {
                        $query->orWhere('id', (int) $assetSearch);
                    }
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('central-admin.products.media-manager', [
            'product' => $product,
            'roles' => self::ROLES,
            'assets' => $assets,
            'assetSearch' => $assetSearch,
            'assignments' => $assignments,
            'urlGenerator' => $urls,
            'resolution' => $resolver->explain(
                entityType: self::ENTITY_TYPE,
                entityId: $product->id,
                role: in_array($previewRole, self::ROLES, true) ? $previewRole : 'main',
                locale: $previewLocale === null ? null : (string) $previewLocale,
                siteId: $previewSiteId,
                marketId: $previewMarketId,
            ),
            'previewRole' => $previewRole,
            'previewLocale' => $previewLocale,
            'previewSiteId' => $previewSiteId,
            'previewMarketId' => $previewMarketId,
        ]);
    }

    public function assign(Request $request, CentralProduct $product): RedirectResponse
    {
        $this->authorizeMedia($request);

        $data = $request->validate([
            'media_asset_id' => ['required', 'integer', Rule::exists('media_assets', 'id')],
            'role' => ['required', 'string', Rule::in(self::ROLES)],
            'locale' => ['nullable', 'string', 'max:20', 'regex:/^[a-z]{2,3}(-[A-Z]{2})?$/'],
            'site_id' => ['nullable', 'integer', 'min:1'],
            'market_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $role = (string) $data['role'];
        $scope = [
            'entity_type' => self::ENTITY_TYPE,
            'entity_id' => $product->id,
            'role' => $role,
            'locale' => $data['locale'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'market_id' => $data['market_id'] ?? null,
        ];

        DB::transaction(function () use ($data, $product, $role, $scope): void {
            $lockedAssignments = MediaAssignment::query()
                ->forEntity(self::ENTITY_TYPE, $product->id)
                ->forRole($role)
                ->lockForUpdate()
                ->get();

            if (in_array($role, self::SINGULAR_ROLES, true)) {
                MediaAssignment::query()->where($scope)->delete();
            }

            $position = ((int) $lockedAssignments->max('position')) + 1;

            MediaAssignment::query()->create($scope + [
                'media_asset_id' => (int) $data['media_asset_id'],
                'position' => $position,
                'is_primary' => in_array($role, self::SINGULAR_ROLES, true),
                'visibility' => 'global',
            ]);
        });

        return redirect()
            ->route('central.products.media', $product)
            ->with('status', 'Media assignment saved.');
    }

    private function authorizeMedia(Request $request): void
    {
        abort_unless($request->user()?->hasCatalogHubPermission('media.manage'), 403);
    }
}
