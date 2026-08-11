<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class FoundationDemoUsersSeeder extends Seeder
{
    public const PASSWORD = 'cataloghub-foundation-demo';

    public const CENTRAL_ADMIN_EMAIL = 'central-admin@demo.cataloghub.test';

    public const SITE_ADMIN_EMAIL = 'site-admin@demo.cataloghub.test';

    /**
     * @var list<array{
     *     name: string,
     *     email: string,
     *     role: UserRole,
     *     disabled: bool,
     *     primary_site: string|null,
     *     memberships: array<string, SiteMembershipRole>
     * }>
     */
    private const PERSONAS = [
        [
            'name' => 'Foundation Super Admin',
            'email' => 'super-admin@demo.cataloghub.test',
            'role' => UserRole::SuperAdmin,
            'disabled' => false,
            'primary_site' => 'tech-germany',
            'memberships' => [
                'tech-germany' => SiteMembershipRole::SiteAdmin,
                'monitors-germany' => SiteMembershipRole::SiteAdmin,
            ],
        ],
        [
            'name' => 'Foundation Central Admin',
            'email' => self::CENTRAL_ADMIN_EMAIL,
            'role' => UserRole::CentralAdmin,
            'disabled' => false,
            'primary_site' => null,
            'memberships' => [],
        ],
        [
            'name' => 'Foundation Catalog Editor',
            'email' => 'catalog-editor@demo.cataloghub.test',
            'role' => UserRole::CatalogEditor,
            'disabled' => false,
            'primary_site' => null,
            'memberships' => [],
        ],
        [
            'name' => 'Foundation Site Admin',
            'email' => self::SITE_ADMIN_EMAIL,
            'role' => UserRole::SiteAdmin,
            'disabled' => false,
            'primary_site' => 'tech-germany',
            'memberships' => [
                'tech-germany' => SiteMembershipRole::SiteAdmin,
                'monitors-germany' => SiteMembershipRole::SiteAdmin,
            ],
        ],
        [
            'name' => 'Foundation Translator',
            'email' => 'translator@demo.cataloghub.test',
            'role' => UserRole::Translator,
            'disabled' => false,
            'primary_site' => 'tech-germany',
            'memberships' => ['tech-germany' => SiteMembershipRole::Translator],
        ],
        [
            'name' => 'Foundation Moderator',
            'email' => 'moderator@demo.cataloghub.test',
            'role' => UserRole::Moderator,
            'disabled' => false,
            'primary_site' => 'monitors-germany',
            'memberships' => ['monitors-germany' => SiteMembershipRole::Moderator],
        ],
        [
            'name' => 'Foundation No Access',
            'email' => 'no-access@demo.cataloghub.test',
            'role' => UserRole::SiteAdmin,
            'disabled' => false,
            'primary_site' => null,
            'memberships' => [],
        ],
        [
            'name' => 'Foundation Disabled User',
            'email' => 'disabled@demo.cataloghub.test',
            'role' => UserRole::CentralAdmin,
            'disabled' => true,
            'primary_site' => null,
            'memberships' => [],
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Foundation demo users may only be seeded in local or testing environments.');
        }

        $this->call(SiteFoundationSeeder::class);

        DB::transaction(function (): void {
            $sites = Site::query()
                ->whereIn('code', ['tech-germany', 'monitors-germany'])
                ->get()
                ->keyBy('code');

            if ($sites->count() !== 2) {
                throw new LogicException('Foundation demo users require both active foundation sites.');
            }

            foreach (self::PERSONAS as $persona) {
                $primarySite = $persona['primary_site'];
                $user = User::query()->updateOrCreate(
                    ['email' => $persona['email']],
                    [
                        'site_id' => $primarySite === null ? null : $sites->get($primarySite)?->getKey(),
                        'name' => $persona['name'],
                        'password' => self::PASSWORD,
                        'role' => $persona['role'],
                        'email_verified_at' => '2026-01-01 00:00:00',
                        'disabled_at' => $persona['disabled'] ? '2026-01-01 00:00:00' : null,
                    ],
                );

                $user->memberships()->delete();

                foreach ($persona['memberships'] as $siteCode => $role) {
                    $site = $sites->get($siteCode);

                    if (! $site instanceof Site) {
                        throw new LogicException("Unknown foundation demo site [{$siteCode}].");
                    }

                    $user->memberships()->create([
                        'site_id' => $site->getKey(),
                        'role' => $role,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
