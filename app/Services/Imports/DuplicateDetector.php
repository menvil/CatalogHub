<?php

namespace App\Services\Imports;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\NormalizedProductDraft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class DuplicateDetector
{
    /**
     * @return Collection<int, DuplicateCandidate>
     */
    public function detect(NormalizedProductDraft $draft): Collection
    {
        $minimumScore = (float) config('imports.duplicate_min_score', 0.55);
        $candidates = new Collection;

        $products = CentralProduct::query()
            ->with(['brand', 'category'])
            ->when(
                $draft->brand_id !== null || $draft->category_id !== null,
                fn ($query) => $query->where(function ($query) use ($draft): void {
                    $query
                        ->when(
                            $draft->brand_id !== null,
                            fn ($query) => $query->where('central_brand_id', $draft->brand_id),
                        )
                        ->when(
                            $draft->category_id !== null,
                            fn ($query) => $query->orWhere('central_category_id', $draft->category_id),
                        );
                }),
            );

        foreach ($products->lazyById() as $product) {
            [$score, $reason] = $this->score($draft, $product);

            if ($score < $minimumScore) {
                continue;
            }

            $candidate = DuplicateCandidate::query()->updateOrCreate(
                [
                    'normalized_product_draft_id' => $draft->id,
                    'candidate_type' => 'central_product',
                    'candidate_id' => $product->id,
                ],
                [
                    'import_batch_id' => $draft->import_batch_id,
                    'score' => number_format($score, 4, '.', ''),
                    'reason_json' => $reason,
                ]
            );

            $candidates->push($candidate);
        }

        return $candidates->sortByDesc('score')->values();
    }

    /**
     * @return array{float, array<string, bool|float>}
     */
    private function score(NormalizedProductDraft $draft, CentralProduct $product): array
    {
        $titleSimilarity = max(
            $this->similarity($draft->title, $product->name),
            $this->similarity($draft->title, trim($product->name.' '.$product->model)),
        );
        $brandMatch = $draft->brand_id !== null
            && (int) $draft->brand_id === (int) $product->central_brand_id;
        $categoryMatch = $draft->category_id !== null
            && (int) $draft->category_id === (int) $product->central_category_id;
        $externalIdMatch = filled($draft->rawProduct->external_id)
            && filled($product->model)
            && $this->normalize((string) $draft->rawProduct->external_id) === $this->normalize((string) $product->model);

        $score = ($titleSimilarity * 0.55)
            + ($brandMatch ? 0.20 : 0.0)
            + ($categoryMatch ? 0.15 : 0.0)
            + ($externalIdMatch ? 0.10 : 0.0);

        return [round($score, 4), [
            'title_similarity' => round($titleSimilarity, 4),
            'brand_match' => $brandMatch,
            'category_match' => $categoryMatch,
            'external_id_match' => $externalIdMatch,
        ]];
    }

    private function similarity(string $left, string $right): float
    {
        $left = $this->normalize($left);
        $right = $this->normalize($right);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percentage);

        return $percentage / 100;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', Str::lower(trim($value))) ?? '';
    }
}
