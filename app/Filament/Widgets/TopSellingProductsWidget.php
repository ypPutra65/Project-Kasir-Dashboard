<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSellingProductsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 5 Menu Terlaris Hari Ini')
            ->description('Peringkat menu dengan volume penjualan tertinggi hari berjalan.')
            ->query(
                Product::query()
                    ->with('category')
                    ->withSum(['orderItems as total_sold' => function (Builder $query) {
                        $query->whereHas('order', fn (Builder $q) => $q->today()->paid());
                    }], 'quantity')
                    ->withSum(['orderItems as total_revenue' => function (Builder $query) {
                        $query->whereHas('order', fn (Builder $q) => $q->today()->paid());
                    }], 'subtotal')
                    ->whereHas('orderItems.order', fn (Builder $q) => $q->today()->paid())
                    ->orderByDesc('total_sold')
                    ->limit(5)
            )
            ->paginated(false)
            ->emptyStateHeading('Belum Ada Penjualan Hari Ini')
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
