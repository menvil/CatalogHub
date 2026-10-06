<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class MarkTranslationOutdatedAction
{
    public function handle(Model $translation, bool $forgetDashboardCache = true): Model
    {
        return DB::transaction(function () use ($translation, $forgetDashboardCache): Model {
            app(AttributeIdentityLock::class)->acquireTarget();
            $translation = $translation::query()->whereKey($translation->getKey())->lockForUpdate()->firstOrFail();
            if ($translation->getAttribute('status') === TranslationStatus::Outdated) {
                return $translation;
            }

            $translation->setAttribute('status', TranslationStatus::Outdated);
            $translation->setAttribute('approved_at', null);
            $translation->setAttribute('approved_by_user_id', null);
            $translation->saveOrFail();
            if ($forgetDashboardCache) {
                TranslationStatsService::forgetDashboardCache();
            }

            return $translation;
        }, 3);
    }
}
