<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\UserRole;
use App\Models\SiteMembership;
use App\Models\User;
use Database\Seeders\FoundationDemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

final class FoundationDemoUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_exact_deterministic_personas_and_memberships(): void
    {
        $this->seed(FoundationDemoUsersSeeder::class);

        self::assertSame([
            'catalog-editor@demo.cataloghub.test' => UserRole::CatalogEditor,
            'central-admin@demo.cataloghub.test' => UserRole::CentralAdmin,
            'disabled@demo.cataloghub.test' => UserRole::CentralAdmin,
            'moderator@demo.cataloghub.test' => UserRole::Moderator,
            'no-access@demo.cataloghub.test' => UserRole::SiteAdmin,
            'site-admin@demo.cataloghub.test' => UserRole::SiteAdmin,
            'super-admin@demo.cataloghub.test' => UserRole::SuperAdmin,
            'translator@demo.cataloghub.test' => UserRole::Translator,
        ], User::query()->where('email', 'like', '%@demo.cataloghub.test')
            ->orderBy('email')
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->email => $user->role])
            ->all());

        $memberships = SiteMembership::query()
            ->with(['user', 'site'])
            ->get()
            ->map(fn (SiteMembership $membership): string => implode(':', [
                $membership->user->email,
                $membership->site->code,
                $membership->role->value,
                $membership->is_active ? 'active' : 'inactive',
            ]))
            ->sort()
            ->values()
            ->all();

        self::assertSame([
            'moderator@demo.cataloghub.test:monitors-germany:moderator:active',
            'site-admin@demo.cataloghub.test:monitors-germany:site_admin:active',
            'site-admin@demo.cataloghub.test:tech-germany:site_admin:active',
            'super-admin@demo.cataloghub.test:monitors-germany:site_admin:active',
            'super-admin@demo.cataloghub.test:tech-germany:site_admin:active',
            'translator@demo.cataloghub.test:tech-germany:translator:active',
        ], $memberships);

        self::assertNull(User::query()->where('email', 'no-access@demo.cataloghub.test')->sole()->site_id);
        self::assertNotNull(User::query()->where('email', 'disabled@demo.cataloghub.test')->sole()->disabled_at);

        foreach (User::query()->where('email', 'like', '%@demo.cataloghub.test')->get() as $user) {
            self::assertTrue(Hash::check(FoundationDemoUsersSeeder::PASSWORD, $user->password));
        }
    }

    public function test_it_is_idempotent_and_rejects_non_local_environments(): void
    {
        $this->seed(FoundationDemoUsersSeeder::class);
        $this->seed(FoundationDemoUsersSeeder::class);

        self::assertSame(8, User::query()->where('email', 'like', '%@demo.cataloghub.test')->count());
        self::assertSame(6, SiteMembership::query()->count());

        $this->app['env'] = 'production';

        $this->expectException(LogicException::class);
        $this->seed(FoundationDemoUsersSeeder::class);
    }
}
