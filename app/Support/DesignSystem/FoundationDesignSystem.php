<?php

declare(strict_types=1);

namespace App\Support\DesignSystem;

final class FoundationDesignSystem
{
    public const FIXTURE_VERSION = 'foundation-v1';

    /** @var array<string, string> */
    public const HEROICON_COMPONENTS = [
        'check-circle' => 'heroicon-o-check-circle',
        'exclamation-triangle' => 'heroicon-o-exclamation-triangle',
        'x-circle' => 'heroicon-o-x-circle',
        'information-circle' => 'heroicon-o-information-circle',
        'eye' => 'heroicon-o-eye',
        'pencil-square' => 'heroicon-o-pencil-square',
        'home' => 'heroicon-o-home',
        'squares-2x2' => 'heroicon-o-squares-2x2',
        'arrow-up-tray' => 'heroicon-o-arrow-up-tray',
        'photo' => 'heroicon-o-photo',
        'language' => 'heroicon-o-language',
        'inbox-stack' => 'heroicon-o-inbox-stack',
        'currency-dollar' => 'heroicon-o-currency-dollar',
        'archive-box' => 'heroicon-o-archive-box',
        'users' => 'heroicon-o-users',
        'cog-6-tooth' => 'heroicon-o-cog-6-tooth',
        'magnifying-glass' => 'heroicon-o-magnifying-glass',
        'bell' => 'heroicon-o-bell',
        'user-circle' => 'heroicon-o-user-circle',
        'arrow-left-start-on-rectangle' => 'heroicon-o-arrow-left-start-on-rectangle',
    ];

    /** @var array<string, array{width: int, height: int, density: string, behavior: string}> */
    public const VIEWPORTS = [
        'mobile' => ['width' => 360, 'height' => 800, 'density' => 'comfortable', 'behavior' => 'Stack controls and collapse navigation'],
        'tablet' => ['width' => 768, 'height' => 1024, 'density' => 'comfortable', 'behavior' => 'Use two-column compositions where safe'],
        'desktop' => ['width' => 1280, 'height' => 900, 'density' => 'compact', 'behavior' => 'Show persistent sidebar and dense tables'],
        'wide' => ['width' => 1440, 'height' => 1200, 'density' => 'compact', 'behavior' => 'Cap content width and preserve readable lines'],
    ];

    /** @var array<string, array{icon: string, meaning: string}> */
    public const ICONS = [
        'success' => ['icon' => 'check-circle', 'meaning' => 'Completed or healthy'],
        'warning' => ['icon' => 'exclamation-triangle', 'meaning' => 'Attention required'],
        'danger' => ['icon' => 'x-circle', 'meaning' => 'Failed or destructive'],
        'info' => ['icon' => 'information-circle', 'meaning' => 'Informational context'],
        'view' => ['icon' => 'eye', 'meaning' => 'Open read-only detail'],
        'edit' => ['icon' => 'pencil-square', 'meaning' => 'Modify an existing record'],
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'success' => 'Completed',
        'warning' => 'Needs review',
        'danger' => 'Failed',
        'info' => 'Queued',
        'neutral' => 'Draft',
    ];

    private function __construct() {}
}
