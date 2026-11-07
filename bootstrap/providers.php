<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\LegacyGuardServiceProvider;

return [
    AppServiceProvider::class,
    LegacyGuardServiceProvider::class,
    AdminPanelProvider::class,
];
