<?php

namespace Tests\Feature\Attributes;

use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\CreateGlobalAttributeDefinitionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class GlobalIdentityRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unreferenced_canonical_code_is_immutable_and_rejected_write_is_atomic(): void
    {
        $definition = AttributeDefinition::factory()->create(['code' => 'stable_meaning']);
        $before = $definition->fresh()->getRawOriginal();
        try {
            app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['code' => 'other_meaning', 'name' => 'Changed'], User::factory()->centralAdmin()->create());
            self::fail('Canonical identity changed.');
        } catch (ValidationException $error) {
            self::assertArrayHasKey('code', $error->errors());
            self::assertSame($before, $definition->fresh()->getRawOriginal());
            self::assertSame(0, AuditLogEntry::query()->count());
        }
    }

    public function test_global_code_uniqueness_rejects_new_meaning_transactionally(): void
    {
        AttributeDefinition::factory()->create(['code' => 'screen_size']);
        try {
            app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'screen_size', 'name' => 'Another meaning', 'data_type' => 'string'], User::factory()->centralAdmin()->create());
            self::fail('Duplicate canonical identity created.');
        } catch (ValidationException) {
            self::assertSame(1, AttributeDefinition::query()->count());
            self::assertSame(0, AuditLogEntry::query()->count());
        }
    }

    public function test_option_code_cannot_be_renamed_deleted_or_reused_even_without_facts(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create(['code' => 'stable_option']);
        $before = $option->fresh()->getRawOriginal();
        foreach ([
            fn () => app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'renamed', 'label' => 'Changed'], $actor),
            fn () => app(DeleteAttributeOptionAction::class)->handle($option, $actor),
            fn () => app(CreateAttributeOptionAction::class)->handle($definition, ['code' => 'stable_option', 'label' => 'Another option'], $actor),
        ] as $write) {
            try {
                $write();
                self::fail('Option identity changed or was reused.');
            } catch (ValidationException) {
                self::assertSame($before, $option->fresh()->getRawOriginal());
                self::assertSame(1, AttributeOption::query()->count());
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
        app(UpdateAttributeOptionAction::class)->handle($option, ['code' => $option->code, 'label' => $option->label, 'is_visible' => false], $actor);
        try {
            app(CreateAttributeOptionAction::class)->handle($definition, ['code' => 'stable_option', 'label' => 'Reuse hidden'], $actor);
            self::fail('Hidden identity reused.');
        } catch (ValidationException) {
            self::assertFalse($option->fresh()->is_visible);
            self::assertSame(1, AttributeOption::query()->count());
        }
    }
}
