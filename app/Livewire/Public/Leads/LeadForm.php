<?php

namespace App\Livewire\Public\Leads;

use App\Actions\Leads\CreateLeadAction;
use App\Enums\LeadType;
use App\Exceptions\Leads\CannotCreateLeadException;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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

    public string $city = '';

    public string $type = '';

    public bool $consentAccepted = false;

    public string $locale = '';

    public string $source = 'public_form';

    public bool $submitted = false;

    public function mount(
        Site $site,
        CentralProduct|int|null $product = null,
        CentralCategory|int|null $category = null,
    ): void {
        $this->site = $site;
        $this->productId = $product instanceof CentralProduct ? (int) $product->getKey() : $product;
        $this->categoryId = $category instanceof CentralCategory ? (int) $category->getKey() : $category;
        $this->locale = $site->default_locale;
        $this->source = $this->productId !== null ? 'product_page' : 'public_form';
    }

    public function submit(CreateLeadAction $createLead): void
    {
        $data = $this->validate([
            'type' => ['required', Rule::enum(LeadType::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:64', 'required_without:email'],
            'city' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'consentAccepted' => ['accepted'],
        ]);

        try {
            $createLead->handle(
                site: $this->site,
                productId: $this->productId,
                categoryId: $this->categoryId,
                type: $data['type'],
                name: $data['name'],
                email: $data['email'] ?: null,
                phone: $data['phone'] ?: null,
                city: $data['city'] ?: null,
                message: $data['message'] ?: null,
                consentAccepted: $this->consentAccepted,
                locale: $this->locale,
                source: $this->source,
            );
        } catch (CannotCreateLeadException $exception) {
            $this->addError('form', $exception->getMessage());

            return;
        }

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.public.leads.lead-form', [
            'leadTypes' => LeadType::options(),
        ]);
    }
}
