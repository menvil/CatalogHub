<?php

namespace App\Livewire\Public\Reviews;

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
    public CentralProduct $product;

    public string $authorName = '';

    public string $authorEmail = '';

    public ?int $rating = null;

    public string $comment = '';

    public function mount(Site $site, CentralProduct $product): void
    {
        $this->site = $site;
        $this->product = $product;
    }

    public function render(): View
    {
        return view('livewire.public.reviews.review-form');
    }
}
