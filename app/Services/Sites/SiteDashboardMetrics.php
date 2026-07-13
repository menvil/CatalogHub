<?php

namespace App\Services\Sites;

use App\Models\Site;
use Illuminate\Support\Facades\DB;

final class SiteDashboardMetrics
{
    /** @return array<string, int|string> */
    public function metricsFor(Site $site): array
    {
        $visibleIds = $site->products()->where('visibility', 'visible')->pluck('central_product_id');
        $seoOverrideIds = $site->overrides()->where('entity_type', 'product')->where('status', 'active')->whereIn('field', ['meta_title', 'meta_description'])->whereIn('entity_id', $visibleIds)->distinct()->pluck('entity_id');

        return [
            'visible_products' => $visibleIds->count(),
            'hidden_products' => $site->products()->where('visibility', 'hidden')->count(),
            'enabled_categories' => DB::table('site_categories')->where('site_id', $site->id)->where('is_enabled', true)->count(),
            'enabled_locales' => DB::table('site_locales')->where('site_id', $site->id)->where('is_enabled', true)->count(),
            'enabled_features' => $site->features()->where('is_enabled', true)->count(),
            'products_without_local_seo' => $visibleIds->diff($seoOverrideIds)->count(),
            'products_without_prices' => 'Phase 17',
            'stale_products' => 'Phase 19',
            'pending_reviews' => 'Future module',
            'new_leads' => 'Future module',
            'sync_status' => 'Phase 19',
        ];
    }
}
