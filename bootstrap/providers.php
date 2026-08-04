<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\CentralAdminPanelProvider;
use App\Providers\Filament\SiteAdminPanelProvider;

return [
    AppServiceProvider::class,
    CentralAdminPanelProvider::class,
    SiteAdminPanelProvider::class,
];
