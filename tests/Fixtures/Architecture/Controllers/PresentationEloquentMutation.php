<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Models\ContentItem;
use App\Models\OfferClick;
use App\Models\User;

final class PresentationEloquentMutation
{
    public function mutateModel(User $user): void
    {
        $user->update(['name' => 'Changed']);
    }

    public function mutateBuilder(): void
    {
        OfferClick::query()->create([]);
    }

    public function mutateRelation(ContentItem $item): void
    {
        $item->translations()->updateOrCreate(['locale' => 'en-US'], ['title' => 'Changed']);
    }

    public function mutateNullableModel(?User $user): void
    {
        $user?->delete();
    }

    public function mutateStaticModel(): void
    {
        User::updateOrCreate(['email' => 'admin@example.test'], ['name' => 'Admin']);
    }

    /** @param array<string, mixed> $state */
    public function frameworkLookalikes(FixtureForm $form, FixtureSyncer $syncer, array $state): void
    {
        $form->fill($state);
        $syncer->sync($state);
        $this->save();
    }

    private function save(): void {}
}

final class FixtureForm
{
    /** @param array<string, mixed> $state */
    public function fill(array $state): void {}
}

final class FixtureSyncer
{
    /** @param array<string, mixed> $state */
    public function sync(array $state): void {}
}
