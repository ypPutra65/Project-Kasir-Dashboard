<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TopSellingProductsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        $startDate = ! empty($this->pageFilters['startDate']) ? Carbon::parse($this->pageFilters['startDate'])->startOfDay() : null;
        $endDate = ! empty($this->pageFilters['endDate']) ? Carbon::parse($this->pageFilters['endDate'])->endOfDay() : null;

        $applyDateFilter = function (Builder $query) use ($startDate, $endDate) {
            $query->paid();
            if ($startDate || $endDate) {
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

        $isFiltered = ($startDate !== null) || ($endDate !== null);
        $isToday = (! $isFiltered) || ($startDate && $endDate && $startDate->isToday() && $endDate->isToday());

        return $table
            ->heading($isToday ? 'Top 5 Menu Terlaris Hari Ini' : 'Top 5 Menu Terlaris')
            ->description('Peringkat menu dengan volume penjualan tertinggi pada periode operasional yang dipilih.')
            ->query(
                Product::query()
                    ->with('category')
                    ->withSum(['orderItems as total_sold' => function (Builder $query) use ($applyDateFilter) {
                        $query->whereHas('order', $applyDateFilter);
                    }], 'quantity')
                    ->withSum(['orderItems as total_revenue' => function (Builder $query) use ($applyDateFilter) {
                        $query->whereHas('order', $applyDateFilter);
                    }], 'subtotal')
                    ->whereHas('orderItems.order', $applyDateFilter)
                    ->orderByDesc('total_sold')
                    ->limit(5)
            )
            ->paginated(false)
            ->emptyStateHeading($isToday ? 'Belum Ada Penjualan Hari Ini' : 'Belum Ada Penjualan Pada Periode Ini')
            ->emptyStateDescription('Menu terlaris akan otomatis terdata setelah ada transaksi kasir yang selesai dibayar.')
            ->emptyStateIcon(Heroicon::OutlinedSparkles)
            ->columns([
                TextColumn::make('index')
                    ->label('Peringkat')
                    ->rowIndex()
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'amber',
                        default => 'gray',
                    }),
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Nama Menu')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('total_sold')
                    ->label('Total Porsi Terjual')
                    ->numeric()
                    ->suffix(' porsi')
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('total_revenue')
                    ->label('Subtotal Penjualan')
                    ->money('IDR')
                    ->weight('bold'),
            ]);
    }
}
