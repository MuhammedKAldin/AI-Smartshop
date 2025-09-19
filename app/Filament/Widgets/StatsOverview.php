<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $totalSales = (float) Order::query()->sum('total');
        $totalOrders = (int) Order::query()->count();

        return [
            Card::make('Total Sales', '$'.number_format($totalSales, 2))
                ->description('All-time sales')
                ->color('success'),

            Card::make('Total Orders', (string) number_format($totalOrders))
                ->description('All-time orders')
                ->color('primary'),
        ];
    }
}
