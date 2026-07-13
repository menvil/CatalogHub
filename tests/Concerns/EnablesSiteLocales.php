<?php

namespace Tests\Concerns;

use App\Models\Locale;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

trait EnablesSiteLocales
{
    protected function enableLocale(Site $site, string $code): void
    {
        Locale::factory()->create(['code' => $code]);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => $code,
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
