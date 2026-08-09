<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin\DesignSystem;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\DesignSystem\ComponentGalleryRequest;
use App\Support\DesignSystem\AdminComponentGalleryFixture;
use App\Support\DesignSystem\CentralShellFixture;
use App\Support\DesignSystem\FoundationDesignSystem;
use App\ViewModels\ActionProgressViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

final class ComponentGalleryController extends Controller
{
    public function __invoke(ComponentGalleryRequest $request): View
    {
        return view('central.component-gallery', [
            'centralUser' => request()->routeIs('dev.component-gallery.capture')
                ? CentralShellFixture::user()
                : null,
            'fixtureVersion' => FoundationDesignSystem::FIXTURE_VERSION,
            'icons' => FoundationDesignSystem::ICONS,
            'statuses' => FoundationDesignSystem::STATUS_LABELS,
            'viewports' => FoundationDesignSystem::VIEWPORTS,
            'componentMode' => $request->componentMode(),
            'componentSection' => $request->componentSection(),
            'componentAcceptance' => $request->acceptanceRequested(),
            'adminComponentFixture' => [
                'version' => AdminComponentGalleryFixture::VERSION,
                'columns' => AdminComponentGalleryFixture::COLUMNS,
                'rows' => AdminComponentGalleryFixture::ROWS,
                'options' => AdminComponentGalleryFixture::OPTIONS,
                'filters' => AdminComponentGalleryFixture::FILTERS,
                'timestamp' => AdminComponentGalleryFixture::timestamp(),
            ],
            'actionProgressFixture' => [
                'idle' => ActionProgressViewModel::idle('Ready to generate the deterministic export.'),
                'pending' => ActionProgressViewModel::pending('Export generation is running.', CarbonImmutable::parse('2026-08-05 10:15:00 UTC')),
                'success' => ActionProgressViewModel::success('Export completed.', CarbonImmutable::parse('2026-08-05 10:15:00 UTC'), CarbonImmutable::parse('2026-08-05 10:16:00 UTC')),
                'failure' => ActionProgressViewModel::failure('Export failed without changing stored data.', CarbonImmutable::parse('2026-08-05 10:15:00 UTC'), CarbonImmutable::parse('2026-08-05 10:16:00 UTC')),
            ],
        ]);
    }
}
