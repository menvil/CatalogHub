<?php

namespace App\Actions\Reviews;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotCreateReviewException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Review;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Illuminate\Support\Facades\Validator;

final class CreateReviewAction
{
    public function handle(
        Site $site,
        CentralProduct $product,
        string $authorName,
        ?string $authorEmail,
        int $rating,
        ?string $pros,
        ?string $cons,
        ?string $comment,
        ?string $locale,
    ): Review {
        $data = Validator::make([
            'author_name' => trim($authorName),
            'author_email' => $this->nullableText($authorEmail),
            'rating' => $rating,
            'pros' => $this->nullableText($pros),
            'cons' => $this->nullableText($cons),
            'comment' => $this->nullableText($comment),
            'locale' => $this->nullableText($locale),
        ], [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'pros' => ['nullable', 'string', 'max:1000', 'required_without_all:cons,comment'],
            'cons' => ['nullable', 'string', 'max:1000', 'required_without_all:pros,comment'],
            'comment' => ['nullable', 'string', 'max:3000', 'required_without_all:pros,cons'],
            'locale' => ['nullable', 'string', 'max:20'],
        ])->validate();

        $reviewsEnabled = $site->features()
            ->where('feature_key', 'reviews')
            ->where('is_enabled', true)
            ->exists();

        if (! $reviewsEnabled) {
            throw CannotCreateReviewException::because('Reviews are not enabled for this site.');
        }

        $visibleProduct = SiteProductProjection::query()
            ->where('site_id', $site->getKey())
            ->where('central_product_id', $product->getKey())
            ->where('status', ProjectionStatus::Active)
            ->when(
                $data['locale'] !== null,
                fn ($query) => $query->where('locale', $data['locale']),
            )
            ->exists();

        if (! $visibleProduct) {
            throw CannotCreateReviewException::because('This product is not available on the site.');
        }

        return Review::query()->create([
            'site_id' => $site->getKey(),
            'central_product_id' => $product->getKey(),
            ...$data,
            'status' => ReviewStatus::Pending,
        ]);
    }

    private function nullableText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
