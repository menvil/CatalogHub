<?php

namespace Tests\Feature\Localization;

use App\Models\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_locale(): void
    {
        $locale = Locale::factory()->create([
            'code' => 'de-DE',
            'language_code' => 'de',
            'region_code' => 'DE',
            'name' => 'German (Germany)',
            'native_name' => 'Deutsch (Deutschland)',
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->assertSame('de-DE', $locale->code);
        $this->assertTrue($locale->is_active);
        $this->assertFalse($locale->is_default);
    }

    public function test_only_one_locale_remains_default_after_saving_new_default(): void
    {
        $english = Locale::factory()->create(['code' => 'en-US', 'is_default' => true]);
        $german = Locale::factory()->create(['code' => 'de-DE', 'is_default' => true]);

        $this->assertFalse($english->fresh()->is_default);
        $this->assertTrue($german->fresh()->is_default);
        $this->assertSame(1, Locale::query()->where('is_default', true)->count());
    }
}
