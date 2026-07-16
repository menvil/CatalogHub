<?php

namespace App\Queries\Translations;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use Illuminate\Database\Eloquent\Model;

final class TranslationStatusCountsQuery implements RawSqlPersistenceBoundary
{
    /**
     * @param  class-string<Model>  $translationModel
     * @return array<string, int>
     */
    public function forLocale(string $translationModel, string $locale): array
    {
        $counts = $translationModel::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->where('locale', $locale)
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $result = [];

        foreach ($counts as $status => $count) {
            $result[(string) $status] = (int) $count;
        }

        return $result;
    }
}
