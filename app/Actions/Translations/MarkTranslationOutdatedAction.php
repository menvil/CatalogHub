<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Database\Eloquent\Model;

final class MarkTranslationOutdatedAction
{
    public function handle(Model $translation): Model
    {
        $translation->setAttribute('status', TranslationStatus::Outdated);
        $translation->save();
        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
