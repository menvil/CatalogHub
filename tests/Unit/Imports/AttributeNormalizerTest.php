<?php

namespace Tests\Unit\Imports;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Imports\AttributeNormalizer;
use Tests\TestCase;

class AttributeNormalizerTest extends TestCase
{
    public function test_dispatches_to_normalizer_that_supports_attribute_definition(): void
    {
        $definition = new AttributeDefinition([
            'name' => 'Available',
            'data_type' => AttributeDataType::Boolean,
        ]);
        $expected = NormalizedAttributeValueData::success(true, 'yes');
        $normalizer = $this->createMock(AttributeValueNormalizerInterface::class);
        $normalizer->expects($this->once())->method('supports')->with($definition)->willReturn(true);
        $normalizer->expects($this->once())->method('normalize')->with($definition, 'yes')->willReturn($expected);

        $result = (new AttributeNormalizer([$normalizer]))->normalize($definition, 'yes');

        $this->assertSame($expected, $result);
    }

    public function test_returns_controlled_result_for_unsupported_data_type(): void
    {
        $definition = new AttributeDefinition([
            'name' => 'Payload',
            'data_type' => AttributeDataType::Json,
        ]);

        $result = (new AttributeNormalizer)->normalize($definition, ['unknown' => true]);

        $this->assertFalse($result->isValid);
        $this->assertSame('unsupported_attribute_type', $result->errorCode);
        $this->assertSame(['unknown' => true], $result->rawValue);
        $this->assertNull($result->value);
    }
}
