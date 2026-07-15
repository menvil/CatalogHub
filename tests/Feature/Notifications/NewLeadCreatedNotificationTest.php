<?php

namespace Tests\Feature\Notifications;

use App\Actions\Leads\CreateLeadAction;
use App\Enums\LeadType;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\User;
use App\Notifications\NewLeadCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewLeadCreatedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_lead_notifies_only_managers_for_the_same_site(): void
    {
        Notification::fake();

        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $moderator = User::factory()->create([
            'site_id' => $site->id,
            'role' => UserRole::Moderator,
        ]);
        $otherAdmin = User::factory()->siteAdmin(Site::factory()->create())->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'leads',
            'is_enabled' => true,
        ]);

        $lead = app(CreateLeadAction::class)->handle(
            site: $site,
            productId: null,
            categoryId: null,
            type: LeadType::BuyingAdvice,
            name: 'Ivan',
            email: 'ivan@example.com',
            phone: null,
            city: null,
            message: 'Need help.',
            consentAccepted: true,
            locale: 'en-US',
            source: 'site_form',
        );

        Notification::assertSentTo([$admin, $moderator], NewLeadCreatedNotification::class, function (NewLeadCreatedNotification $notification, array $channels) use ($admin, $lead): bool {
            return in_array('database', $channels, true)
                && $notification->lead->is($lead)
                && $notification->toArray($admin)['type'] === LeadType::BuyingAdvice->value;
        });
        Notification::assertNotSentTo($otherAdmin, NewLeadCreatedNotification::class);
    }
}
