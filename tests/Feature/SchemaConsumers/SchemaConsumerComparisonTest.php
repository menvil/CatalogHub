<?php

namespace Tests\Feature\SchemaConsumers;

use App\Domains\PublicSite\ComparisonViewModelBuilder;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SchemaConsumerComparisonTest extends TestCase
{
    use RefreshDatabase;

    /** @return iterable<string, array{string, mixed, mixed, bool}> */
    public static function values(): iterable
    {
        yield 'numeric normalized' => ['decimal', '001.500000', '1.5', true];
        yield 'numeric scientific' => ['decimal', '1.5e2', '150.0000', true];
        yield 'numeric exact precision' => ['decimal', '9007199254740993', '9007199254740992', false];
        yield 'missing versus zero' => ['integer', null, '0', false];
        yield 'false versus missing' => ['boolean', false, null, false];
        yield 'false equality' => ['boolean', false, false, true];
        yield 'string exact' => ['string', 'Size', 'size', false];
        yield 'empty versus missing' => ['text', '', null, false];
        yield 'enum identity' => ['enum', 'ips', 'ips', true];
        yield 'enum unequal' => ['enum', 'ips', 'oled', false];
        yield 'multi enum set' => ['multi_enum', ['hdmi', 'usb'], ['usb', 'hdmi'], true];
        yield 'multi enum missing' => ['multi_enum', [], null, false];
        yield 'missing equality' => ['string', null, null, true];
    }

    #[DataProvider('values')]
    public function test_explicit_comparison_uses_typed_equality(string $type, mixed $left, mixed $right, bool $equal): void
    {
        $projections = collect([$left, $right])->map(function ($value) use ($type): SiteProductProjection {
            return new SiteProductProjection(['site_id' => 1, 'locale' => 'en-US', 'title' => 'Product', 'payload_json' => [
                'attribute_identity_version' => 2, 'category' => ['id' => 1],
                'comparison' => [['category_attribute_assignment_id' => 5]],
                'attributes' => [['assignment_id' => 5, 'definition_id' => 9, 'label' => 'Canonical meaning',
                    'section' => ['id' => null, 'label' => 'Ungrouped'], 'data_type' => $type,
                    'has_value' => $value !== null, 'canonical_value' => $value, 'display_value' => 'Display',
                    'measurement_dimension_id' => null, 'canonical_measurement_unit_id' => null]],
            ]]);
        });
        $result = app(ComparisonViewModelBuilder::class)->build($projections);
        self::assertNull($result['error']);
        self::assertSame($equal, $result['sections'][0]['attributes'][0]['is_equal']);
        self::assertArrayNotHasKey('winner', $result['sections'][0]['attributes'][0]);
        foreach ($projections as $projection) {
            $projection->payload_json = [...$projection->payload_json, 'comparison' => []];
        }
        self::assertSame([], app(ComparisonViewModelBuilder::class)->build($projections)['sections']);
    }
}
