<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\Backup\BackupStatusWidgetData;
use Filament\Widgets\Widget;

final class BackupStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.backup-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('central.manage');
    }

    /** @return array<string, int|string|null> */
    public function status(): array
    {
        return app(BackupStatusWidgetData::class)->resolve();
    }
}
