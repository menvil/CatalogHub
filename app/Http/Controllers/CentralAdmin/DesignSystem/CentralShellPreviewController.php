<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin\DesignSystem;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\DesignSystem\CentralShellPreviewRequest;
use App\Support\DesignSystem\CentralShellFixture;
use Illuminate\Contracts\View\View;

final class CentralShellPreviewController extends Controller
{
    public function __invoke(CentralShellPreviewRequest $request): View
    {
        return view('central.shell-preview', [
            'acceptance' => $request->acceptanceRequested(),
            'centralShellPreviewState' => $request->shellState(),
            'centralUser' => CentralShellFixture::user(),
            'fixtureVersion' => CentralShellFixture::VERSION,
        ]);
    }
}
