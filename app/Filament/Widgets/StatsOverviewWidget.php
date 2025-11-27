<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        
        // This month's orders
        $ordersThisMonth = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
        $ordersLastMonth = Order::whereBetween('created_at', [
            $startOfMonth->copy()->subMonth(),
            $endOfMonth->copy()->subMonth()
        ])->count();
        
        // Revenue this month (paid orders only)
        $revenueThisMonth = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total');
        $revenueLastMonth = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [
                $startOfMonth->copy()->subMonth(),
                $endOfMonth->copy()->subMonth()
            ])
            ->sum('total');

        // Profit calculation (revenue - cost of goods sold)
        $profitThisMonth = $this->calculateProfit($startOfMonth, $endOfMonth);
        $profitLastMonth = $this->calculateProfit(
            $startOfMonth->copy()->subMonth(),
            $endOfMonth->copy()->subMonth()
        );

        // Pending orders count
        $pendingOrders = Order::where('status', 'pending')->count();

        return [
            Stat::make('Revenue This Month', 'Rp ' . number_format($revenueThisMonth, 0, ',', '.'))
                ->description($this->getChangeDescription($revenueThisMonth, $revenueLastMonth))
                ->descriptionIcon($this->getChangeIcon($revenueThisMonth, $revenueLastMonth))
                ->color($this->getChangeColor($revenueThisMonth, $revenueLastMonth))
                ->chart($this->getRevenueChart()),

            Stat::make('Profit This Month', 'Rp ' . number_format($profitThisMonth, 0, ',', '.'))
                ->description($this->getChangeDescription($profitThisMonth, $profitLastMonth))
                ->descriptionIcon($this->getChangeIcon($profitThisMonth, $profitLastMonth))
                ->color($this->getChangeColor($profitThisMonth, $profitLastMonth)),

            Stat::make('Orders This Month', $ordersThisMonth)
                ->description($this->getChangeDescription($ordersThisMonth, $ordersLastMonth))
                ->descriptionIcon($this->getChangeIcon($ordersThisMonth, $ordersLastMonth))
                ->color($this->getChangeColor($ordersThisMonth, $ordersLastMonth))
                ->chart($this->getOrdersChart()),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-o-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),
        ];
    }

    private function calculateProfit(Carbon $start, Carbon $end): float
    {
        $paidOrders = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->pluck('id');

        if ($paidOrders->isEmpty()) {
            return 0;
        }

        $revenue = Order::whereIn('id', $paidOrders)->sum('total');
        
        // Calculate cost from order items
        $cost = OrderItem::whereIn('order_id', $paidOrders)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(order_items.quantity * COALESCE(products.cost, 0)) as total_cost')
            ->value('total_cost') ?? 0;

        return $revenue - $cost;
    }

    private function getChangeDescription(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'New this month' : 'No change';
        }

        $change = (($current - $previous) / $previous) * 100;
        $direction = $change >= 0 ? 'increase' : 'decrease';
        
        return abs(round($change, 1)) . '% ' . $direction;
    }

    private function getChangeIcon(float $current, float $previous): string
    {
        if ($current >= $previous) {
            return 'heroicon-m-arrow-trending-up';
        }
        return 'heroicon-m-arrow-trending-down';
    }

    private function getChangeColor(float $current, float $previous): string
    {
        if ($current >= $previous) {
            return 'success';
        }
        return 'danger';
    }

    private function getRevenueChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total') / 1000000; // in millions for better chart display
        }
        return $data;
    }

    private function getOrdersChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = Order::whereDate('created_at', $date)->count();
        }
        return $data;
    }
}
