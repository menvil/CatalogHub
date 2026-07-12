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
}
