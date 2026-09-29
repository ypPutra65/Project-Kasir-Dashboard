<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    /**
     * Polling interval untuk update realtime data monitoring.
     */
    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $startDate = ! empty($this->pageFilters['startDate']) ? Carbon::parse($this->pageFilters['startDate'])->startOfDay() : null;
        $endDate = ! empty($this->pageFilters['endDate']) ? Carbon::parse($this->pageFilters['endDate'])->endOfDay() : null;

        $isFiltered = ($startDate !== null) || ($endDate !== null);
        $isToday = (! $isFiltered) || ($startDate && $endDate && $startDate->isToday() && $endDate->isToday());

        // Scope filter helper
        $applyDateFilter = function (Builder $query) use ($startDate, $endDate, $isFiltered) {
            if ($isFiltered) {
                if ($startDate) {
                    $query->where('ordered_at', '>=', $startDate);
                }
                if ($endDate) {
                    $query->where('ordered_at', '<=', $endDate);
                }
            } else {
                $query->today();
            }
        };

        // 1. Revenue
        $revenue = (float) Order::query()
            ->paid()
            ->where($applyDateFilter)
            ->sum('total_amount');

        // Dynamic Label & Subtext
        if ($isToday) {
            $periodLabel = 'Hari Ini';
            $yesterdayRevenue = (float) Order::whereDate('ordered_at', Carbon::yesterday())->paid()->sum('total_amount');
            $revenueDiff = $revenue - $yesterdayRevenue;
            $revenueTrendIcon = $revenueDiff >= 0 ? Heroicon::OutlinedArrowTrendingUp : Heroicon::OutlinedArrowTrendingDown;
            $revenueTrendColor = $revenueDiff >= 0 ? 'success' : 'danger';
            $revenueDescription = $yesterdayRevenue > 0
                ? ($revenueDiff >= 0 ? '+' : '') . 'Rp ' . number_format($revenueDiff, 0, ',', '.') . ' vs kemarin'
                : 'Rekap transaksi hari berjalan';
        } else {
            $formattedStart = $startDate ? $startDate->translatedFormat('d M Y') : 'Awal';
            $formattedEnd = $endDate ? $endDate->translatedFormat('d M Y') : 'Sekarang';
            $periodLabel = ($startDate && $endDate && $startDate->isSameDay($endDate))
                ? $startDate->translatedFormat('d M Y')
                : "{$formattedStart} - {$formattedEnd}";
            $revenueTrendIcon = Heroicon::OutlinedCalendar;
            $revenueTrendColor = 'primary';
            $revenueDescription = "Riwayat: {$periodLabel}";
        }

        // 2. Cash Drawer
        $cashDrawer = (float) Order::query()
            ->paid()
            ->where('payment_method', 'cash')
            ->where($applyDateFilter)
            ->sum('total_amount');

        $cashCount = Order::query()
            ->paid()
            ->where('payment_method', 'cash')
            ->where($applyDateFilter)
            ->count();

        // 3. Non-Cash Revenue
        $nonCashRevenue = (float) Order::query()
            ->paid()
            ->whereIn('payment_method', ['qris', 'transfer'])
            ->where($applyDateFilter)
            ->sum('total_amount');

        $nonCashCount = Order::query()
            ->paid()
            ->whereIn('payment_method', ['qris', 'transfer'])
            ->where($applyDateFilter)
            ->count();

        // 4. Gross Profit
        $totalHpp = (float) OrderItem::whereHas('order', function (Builder $query) use ($applyDateFilter) {
            $query->paid()->where($applyDateFilter);
        })->sum(DB::raw('quantity * cost_price'));

        $grossProfit = $revenue - $totalHpp;
        $profitMargin = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0.0;

        $omsetTitle = $isToday ? 'Total Omset Hari Ini' : "Total Omset ({$periodLabel})";

        return [
            Stat::make($omsetTitle, 'Rp ' . number_format($revenue, 0, ',', '.'))
                ->description($revenueDescription)
                ->descriptionIcon($revenueTrendIcon)
                ->color($revenueTrendColor)
                ->chart([
                    max(1, (int) round($revenue / 3000)),
                    max(1, (int) round($revenue / 1500)),
                    max(1, (int) round($revenue / 1000)),
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
                    max(1, (int) round($totalHpp / 1000)),
                    max(1, (int) round($grossProfit / 1000)),
                ]),
        ];
    }
}
