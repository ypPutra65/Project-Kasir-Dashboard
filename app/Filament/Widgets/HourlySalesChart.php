<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class HourlySalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Grafik Penjualan Per Jam (09:00 - 23:00)';

    protected ?string $description = 'Pemetaan omset penjualan per jam hari ini untuk analisis jam sibuk (peak hours).';

    protected ?string $pollingInterval = '15s';

    protected ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $orders = Order::today()
            ->paid()
            ->select(['id', 'ordered_at', 'total_amount'])
            ->get();

        $hourlyData = [];
        $labels = [];

        for ($hour = 9; $hour <= 23; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);
            $hourlyData[$hour] = 0.0;
        }

        foreach ($orders as $order) {
            $orderHour = (int) Carbon::parse($order->ordered_at)->format('H');
            if (isset($hourlyData[$orderHour])) {
                $hourlyData[$orderHour] += (float) $order->total_amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omset Penjualan (Rp)',
                    'data' => array_values($hourlyData),
                    'backgroundColor' => 'rgba(217, 119, 6, 0.75)',
                    'borderColor' => '#d97706',
                    'borderWidth' => 2,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
