<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Database\Eloquent\Model;

final class MarkTranslationOutdatedAction
{
    public function handle(Model $translation): Model
    {
        if ($translation->getAttribute('status') === TranslationStatus::Outdated) {
            return $translation;
        }

        $translation->setAttribute('status', TranslationStatus::Outdated);
        $translation->setAttribute('approved_at', null);
        $translation->setAttribute('approved_by_user_id', null);
        $translation->saveOrFail();
        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
