<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApproveTranslationAction
{
    public function handle(Model $translation, User $user, bool $forgetDashboardCache = true): Model
    {
        return DB::transaction(function () use ($translation, $user, $forgetDashboardCache): Model {
            app(AttributeIdentityLock::class)->acquire();
            $translation = $translation::query()->whereKey($translation->getKey())->lockForUpdate()->firstOrFail();
            if ($translation->getAttribute('status') === TranslationStatus::Approved) {
                return $translation;
            }

            if ($translation->getAttribute('status') !== TranslationStatus::HumanReviewed) {
                throw ValidationException::withMessages([
                    'translation' => 'Only a human-reviewed translation can be approved.',
                ]);
            }

            $translation->setAttribute('status', TranslationStatus::Approved);
            $translation->setAttribute('approved_at', now());
            $translation->setAttribute('approved_by_user_id', $user->getKey());
            $translation->saveOrFail();
            if ($forgetDashboardCache) {
                TranslationStatsService::forgetDashboardCache();
            }

            return $translation;
        }, 3);
    }
}
