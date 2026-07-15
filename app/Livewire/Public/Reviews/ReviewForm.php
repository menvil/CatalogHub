<?php

namespace App\Livewire\Public\Reviews;

use App\Actions\Reviews\CreateReviewAction;
use App\Exceptions\Reviews\CannotCreateReviewException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ReviewForm extends Component
{
    #[Locked]
    public Site $site;

    #[Locked]
    public int $productId;

    public string $authorName = '';

    public string $authorEmail = '';

    public ?int $rating = null;

    public string $comment = '';

    public string $pros = '';

    public string $cons = '';

    public string $locale = '';

    public bool $submitted = false;

    public function mount(Site $site, CentralProduct|int $product): void
    {
        $this->site = $site;
        $this->productId = (int) ($product instanceof CentralProduct ? $product->getKey() : $product);
        $defaultLocale = $site->getAttribute('default_locale');
        $this->locale = is_string($defaultLocale) && $defaultLocale !== ''
            ? $defaultLocale
            : app()->getLocale();
    }

    public function submit(CreateReviewAction $createReview): void
    {
        $data = $this->validate([
            'authorName' => ['required', 'string', 'max:255'],
            'authorEmail' => ['nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'pros' => ['nullable', 'string', 'max:1000', 'required_without_all:cons,comment'],
            'cons' => ['nullable', 'string', 'max:1000', 'required_without_all:pros,comment'],
            'comment' => ['nullable', 'string', 'max:3000', 'required_without_all:pros,cons'],
        ]);

        try {
            $createReview->handle(
                site: $this->site,
                product: $this->productId,
                authorName: $data['authorName'],
                authorEmail: $data['authorEmail'] ?: null,
                rating: $data['rating'],
                pros: $data['pros'] ?: null,
                cons: $data['cons'] ?: null,
                comment: $data['comment'] ?: null,
                locale: $this->locale,
            );
        } catch (CannotCreateReviewException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        }

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.public.reviews.review-form');
    }
}
