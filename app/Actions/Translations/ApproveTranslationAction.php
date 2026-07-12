<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ApproveTranslationAction
{
    public function handle(Model $translation, User $user): Model
    {
        if ($translation->getAttribute('status') === TranslationStatus::Approved) {
            return $translation;
        }

        $translation->setAttribute('status', TranslationStatus::Approved);
        $translation->setAttribute('approved_at', now());
        $translation->setAttribute('approved_by_user_id', $user->getKey());
        $translation->save();

        return $translation;
    }
}
