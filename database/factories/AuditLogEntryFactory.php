<?php

namespace Database\Factories;

use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLogEntry> */
final class AuditLogEntryFactory extends Factory
{
    protected $model = AuditLogEntry::class;

    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'context' => 'central',
            'site_id' => null,
            'action' => 'security.test',
            'subject_type' => null,
            'subject_id' => null,
            'before_json' => null,
            'after_json' => null,
            'request_id' => fake()->uuid(),
        ];
    }

    public function central(): static
    {
        return $this->state(fn (): array => [
            'context' => 'central',
            'site_id' => null,
        ]);
    }

    public function site(Site $site): static
    {
        return $this->state(fn (): array => [
            'context' => 'site',
            'site_id' => $site->getKey(),
        ]);
    }
}
