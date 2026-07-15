<?php

namespace App\Actions\Leads;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Exceptions\Leads\CannotCreateLeadException;
use App\Models\Lead;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\User;
use App\Notifications\NewLeadCreatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateLeadAction
{
    public function handle(
        Site $site,
        ?int $productId,
        ?int $categoryId,
        LeadType|string $type,
        string $name,
        ?string $email,
        ?string $phone,
        ?string $city,
        ?string $message,
        bool $consentAccepted,
        ?string $locale,
        ?string $source,
    ): Lead {
        $data = Validator::make([
            'central_product_id' => $productId,
            'central_category_id' => $categoryId,
            'type' => $type instanceof LeadType ? $type->value : $type,
            'name' => trim($name),
            'email' => $this->nullableText($email),
            'phone' => $this->nullableText($phone),
            'city' => $this->nullableText($city),
            'message' => $this->nullableText($message),
            'locale' => $this->nullableText($locale),
            'source' => $this->nullableText($source),
        ], [
            'central_product_id' => ['nullable', 'integer'],
            'central_category_id' => ['nullable', 'integer'],
            ...self::inputRules(),
            'locale' => ['nullable', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:255'],
        ])->validate();

        if (! $consentAccepted) {
            throw CannotCreateLeadException::because('Consent is required to create a lead.');
        }

        $leadsEnabled = $site->features()
            ->where('feature_key', 'leads')
            ->where('is_enabled', true)
            ->exists();

        if (! $leadsEnabled) {
            throw CannotCreateLeadException::because('Leads are not enabled for this site.');
        }

        $this->ensureContextIsVisible($site, $data['central_product_id'], $data['central_category_id'], $data['locale']);

        $lead = Lead::query()->create([
            'site_id' => $site->getKey(),
            ...$data,
            'status' => LeadStatus::New,
            'consent_accepted_at' => now(),
        ]);

        $recipients = User::query()
            ->where('site_id', $site->getKey())
            ->get()
            ->filter(fn (User $user): bool => $user->hasCatalogHubPermission('leads.manage'));

        Notification::send($recipients, new NewLeadCreatedNotification($lead));

        return $lead;
    }

    /** @return array<string, list<mixed>> */
    public static function inputRules(): array
    {
        return [
            'type' => ['required', Rule::enum(LeadType::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:64', 'required_without:email'],
            'city' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    private function ensureContextIsVisible(Site $site, ?int $productId, ?int $categoryId, ?string $locale): void
    {
        if ($productId !== null) {
            $productVisible = SiteProductProjection::query()
                ->where('site_id', $site->getKey())
                ->where('central_product_id', $productId)
                ->where('status', ProjectionStatus::Active)
                ->when($locale !== null, fn ($query) => $query->where('locale', $locale))
                ->exists();

            if (! $productVisible) {
                throw CannotCreateLeadException::because('This product is not available on the site.');
            }
        }

        if ($categoryId !== null) {
            $categoryVisible = SiteCategoryProjection::query()
                ->where('site_id', $site->getKey())
                ->where('central_category_id', $categoryId)
                ->where('status', ProjectionStatus::Active)
                ->when($locale !== null, fn ($query) => $query->where('locale', $locale))
                ->exists();

            if (! $categoryVisible) {
                throw CannotCreateLeadException::because('This category is not available on the site.');
            }
        }
    }

    private function nullableText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
