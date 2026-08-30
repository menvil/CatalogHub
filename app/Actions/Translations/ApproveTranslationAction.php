<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\User;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ApproveTranslationAction
{
    public function handle(Model $translation, User $user): Model
    {
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
        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
