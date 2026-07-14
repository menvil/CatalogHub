<?php

namespace App\Livewire\Public\Leads;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class LeadForm extends Component
{
    #[Locked]
    public Site $site;

    #[Locked]
    public ?int $productId = null;

    #[Locked]
    public ?int $categoryId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $message = '';

    public function mount(
        Site $site,
        CentralProduct|int|null $product = null,
        CentralCategory|int|null $category = null,
    ): void {
        $this->site = $site;
        $this->productId = $product instanceof CentralProduct ? (int) $product->getKey() : $product;
        $this->categoryId = $category instanceof CentralCategory ? (int) $category->getKey() : $category;
    }

    public function render(): View
    {
        return view('livewire.public.leads.lead-form');
    }
}
