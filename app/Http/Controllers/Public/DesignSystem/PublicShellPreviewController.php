<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public\DesignSystem;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\DesignSystem\PublicShellPreviewRequest;
use App\Support\DesignSystem\PublicShellFixture;
use Illuminate\Contracts\View\View;

final class PublicShellPreviewController extends Controller
{
    public function __invoke(PublicShellPreviewRequest $request): View
    {
        $state = $request->shellState();
        $fixture = PublicShellFixture::context($state);

        return view('public.shell-preview', [
            'acceptance' => $request->acceptanceRequested(),
            'fixtureVersion' => PublicShellFixture::VERSION,
            'locale' => $fixture['locale'],
            'publicLocaleOptions' => $fixture['localeOptions'],
            'publicNavigation' => $fixture['navigation'],
            'publicShellPreviewState' => $state,
            'seoMetadata' => $fixture['seo'],
            'site' => $fixture['site'],
            'theme' => $fixture['theme'],
        ]);
    }
}
