<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin\DesignSystem;

use App\Http\Controllers\Controller;
use App\Support\DesignSystem\FoundationDesignSystem;
use Illuminate\Contracts\View\View;

final class ComponentGalleryController extends Controller
{
    public function __invoke(): View
    {
        return view('central.component-gallery', [
            'fixtureVersion' => FoundationDesignSystem::FIXTURE_VERSION,
            'icons' => FoundationDesignSystem::ICONS,
            'statuses' => FoundationDesignSystem::STATUS_LABELS,
            'viewports' => FoundationDesignSystem::VIEWPORTS,
        ]);
    }
}
