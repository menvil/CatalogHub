<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class NewLeadCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Lead $lead) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'lead_id' => $this->lead->getKey(),
            'site_id' => $this->lead->site_id,
            'type' => $this->lead->type->value,
            'status' => $this->lead->status->value,
            'name' => $this->lead->name,
            'central_product_id' => $this->lead->central_product_id,
            'central_category_id' => $this->lead->central_category_id,
            'source' => $this->lead->source,
            'created_at' => $this->lead->created_at?->toAtomString(),
        ];
    }
}
