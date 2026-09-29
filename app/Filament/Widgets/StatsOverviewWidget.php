<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Polling interval untuk update realtime data monitoring.
     */
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Total Omset Hari Ini vs Kemarin
        $todayRevenue = (float) Order::today()->paid()->sum('total_amount');
        $yesterdayRevenue = (float) Order::whereDate('ordered_at', Carbon::yesterday())->paid()->sum('total_amount');

        $revenueDiff = $todayRevenue - $yesterdayRevenue;
        $revenueTrendIcon = $revenueDiff >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown;
        $revenueTrendColor = $revenueDiff >= 0 ? 'success' : 'danger';
        $revenueDescription = $yesterdayRevenue > 0
            ? ($revenueDiff >= 0 ? '+' : '') . 'Rp ' . number_format($revenueDiff, 0, ',', '.') . ' vs kemarin'
            : 'Rekap transaksi hari ini';

        // 2. Uang Kas Fisik di Laci (Metode Cash)
        $cashDrawer = (float) Order::today()
            ->paid()
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $cashCount = Order::today()
            ->paid()
            ->where('payment_method', 'cash')
            ->count();

        // 3. Uang Masuk Non-Tunai (QRIS & Transfer)
        $nonCashRevenue = (float) Order::today()
            ->paid()
            ->whereIn('payment_method', ['qris', 'transfer'])
            ->sum('total_amount');

        $nonCashCount = Order::today()
            ->paid()
            ->whereIn('payment_method', ['qris', 'transfer'])
            ->count();

        // 4. Estimasi Laba Kotor: Total Omset - Total HPP (Qty Terjual * HPP)
        $todayTotalHpp = (float) OrderItem::whereHas('order', function ($query) {
            $query->today()->paid();
        })->sum(DB::raw('quantity * cost_price'));

        $grossProfit = $todayRevenue - $todayTotalHpp;
        $profitMargin = $todayRevenue > 0 ? round(($grossProfit / $todayRevenue) * 100, 1) : 0.0;

        return [
            Stat::make('Total Omset Hari Ini', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description($revenueDescription)
                ->descriptionIcon($revenueTrendIcon)
                ->color($revenueTrendColor)
                ->chart([
                    max(1, (int) round($yesterdayRevenue / 1000)),
                    max(1, (int) round(($yesterdayRevenue + $todayRevenue) / 2000)),
                    max(1, (int) round($todayRevenue / 1000)),
                ]),

            Stat::make('Uang Kas di Laci', 'Rp ' . number_format($cashDrawer, 0, ',', '.'))
                ->description("{$cashCount} transaksi tunai fisik")
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->chart([1, 3, 5, 8, max(1, $cashCount)]),

            Stat::make('Uang Masuk Non-Tunai', 'Rp ' . number_format($nonCashRevenue, 0, ',', '.'))
                ->description("{$nonCashCount} transaksi QRIS & Transfer")
                ->descriptionIcon(Heroicon::OutlinedQrCode)
                ->color('info')
                ->chart([2, 4, 3, 6, max(1, $nonCashCount)]),

            Stat::make('Estimasi Laba Kotor', 'Rp ' . number_format($grossProfit, 0, ',', '.'))
                ->description("Margin keuntungan: {$profitMargin}%")
                ->descriptionIcon(Heroicon::OutlinedChartBar)
                ->color('warning')
                ->chart([
                    max(1, (int) round($todayTotalHpp / 1000)),
                    max(1, (int) round($grossProfit / 1000)),
                ]),
        ];
    }
}
