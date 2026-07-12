<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Model;

final class MarkTranslationOutdatedAction
{
    public function handle(Model $translation, ?string $reason = null): Model
    {
        $translation->setAttribute('status', TranslationStatus::Outdated);
        $translation->save();

        return $translation;
    }
}
