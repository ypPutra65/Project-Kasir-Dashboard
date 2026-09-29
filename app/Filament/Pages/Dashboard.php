<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Dashboard Monitoring';

    protected static ?string $title = 'Dashboard Monitoring Warung';

    protected static ?int $navigationSort = 2;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter Periode Operasional')
                    ->description('Pilih tanggal untuk melihat riwayat omset & penjualan (kosongkan untuk otomatis memantau hari ini).')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->compact()
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ])
                    ->components([
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->placeholder('Pilih tanggal awal')
                            ->prefixIcon(Heroicon::OutlinedCalendar)
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->maxDate(now()),
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->placeholder('Pilih tanggal akhir')
                            ->prefixIcon(Heroicon::OutlinedCalendar)
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->maxDate(now()),
                    ]),
            ]);
    }
}
