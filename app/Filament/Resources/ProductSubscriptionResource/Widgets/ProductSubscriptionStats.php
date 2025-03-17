<?php

namespace App\Filament\Resources\ProductSubscriptionResource\Widgets;

use App\Models\ProductSubscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductSubscriptionStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total_transaction = ProductSubscription::count();
        $approved_transaction = ProductSubscription::where('is_paid', true)->count();
        $total_revenue = ProductSubscription::where('is_paid', true)->sum('total_amount');
        return [
            Stat::make('Total Transaction', $total_transaction)
            ->description('All transactions')
            ->descriptionIcon('heroicon-o-currency-dollar'),
            
            Stat::make('Approved Transaction', $approved_transaction)
            ->description('Approved transactions')
            ->descriptionIcon('heroicon-o-check-circle')
            ->color('success'),

            Stat::make('Total Revenue', 'IDR ' . number_format($total_revenue))
            ->description('Revenue from approved transactions')
            ->descriptionIcon('heroicon-o-check-circle')
            ->color('success')
        ];
    }
}
