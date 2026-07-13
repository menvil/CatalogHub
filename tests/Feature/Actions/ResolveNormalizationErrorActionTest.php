<?php

namespace Tests\Feature\Actions;

use App\Actions\Imports\ResolveNormalizationErrorAction;
use App\Enums\UserRole;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\RawProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveNormalizationErrorActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolving_an_already_resolved_error_preserves_original_audit_values(): void
    {
        $originalReviewer = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $secondReviewer = User::factory()->centralAdmin()->create();
        $rawProduct = RawProduct::factory()->create();
        $resolvedAt = now()->subHour()->startOfSecond();
        $error = NormalizationError::query()->create([
            'import_batch_id' => $rawProduct->import_batch_id,
            'raw_product_id' => $rawProduct->id,
            'severity' => 'error',
            'code' => 'invalid_value',
            'message' => 'Invalid source value',
            'resolved_at' => $resolvedAt,
            'resolved_by_user_id' => $originalReviewer->id,
        ]);

        $result = app(ResolveNormalizationErrorAction::class)->handle($error, $secondReviewer);

        $this->assertTrue($resolvedAt->equalTo($result->resolved_at));
        $this->assertSame($originalReviewer->id, $result->resolved_by_user_id);
    }
}
