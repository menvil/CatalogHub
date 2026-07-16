<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\Site;
use App\Services\Export\SiteConfigJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteConfigJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_safe_site_configuration_without_secrets(): void
    {
        Storage::fake('local');
        $site = Site::factory()->create([
            'settings_json' => [
                'seo_title' => 'Safe SEO title',
                'api_key' => 'super-secret-api-key',
                'nested' => ['private_token' => 'super-secret-token', 'color' => 'blue'],
            ],
        ]);
        $site->market->update([
            'config_json' => ['tax_label' => 'VAT', 'client_secret' => 'super-secret-client'],
        ]);
        $site->features()->create([
            'feature_key' => 'reviews',
            'is_enabled' => true,
            'config_json' => ['moderation' => true, 'password' => 'super-secret-password'],
        ]);
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(SiteConfigJsonlExporter::class)->export($snapshot);
        $content = Storage::disk($result->disk)->get($result->path);
        $rows = collect(explode("\n", trim($content)))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame(3, $result->lineCount);
        $this->assertSame(['market', 'site', 'site_feature'], $rows->pluck('entity_type')->all());
        $this->assertStringContainsString('Safe SEO title', $content);
        $this->assertStringContainsString('moderation', $content);
        $this->assertStringNotContainsString('super-secret', $content);
        $this->assertStringNotContainsString('api_key', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertSame(3, $snapshot->fresh()->files_json['site_config']['line_count']);
    }
}
