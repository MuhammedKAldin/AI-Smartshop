<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentOrders;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            RecentOrders::class,
        ];
    }
}
