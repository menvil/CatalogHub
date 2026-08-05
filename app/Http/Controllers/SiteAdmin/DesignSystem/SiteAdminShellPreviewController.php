<?php

declare(strict_types=1);

namespace App\Http\Controllers\SiteAdmin\DesignSystem;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdmin\DesignSystem\SiteAdminShellPreviewRequest;
use App\Support\DesignSystem\SiteAdminShellFixture;
use Illuminate\Contracts\View\View;

final class SiteAdminShellPreviewController extends Controller
{
    public function __invoke(SiteAdminShellPreviewRequest $request): View
    {
        $state = $request->shellState();
        $fixture = SiteAdminShellFixture::context();

        return view('site-admin.shell-preview', [
            'acceptance' => $request->acceptanceRequested(),
            'fixtureVersion' => SiteAdminShellFixture::VERSION,
            'siteAdminAuthorizedSites' => $state === 'one-site'
                ? [$fixture['current']]
                : [$fixture['current'], $fixture['alternate']],
            'siteAdminCurrentSite' => $fixture['current'],
            'siteAdminNavigation' => SiteAdminShellFixture::navigation($fixture['current']),
            'siteAdminRuntimeContext' => $fixture['context'],
            'siteAdminShellPreviewState' => $state,
            'siteAdminUser' => SiteAdminShellFixture::user(),
        ]);
    }
}
