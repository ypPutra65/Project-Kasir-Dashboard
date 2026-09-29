<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Menu Makanan & Minuman';

    protected static ?string $modelLabel = 'Menu';

    protected static ?string $pluralModelLabel = 'Menu Makanan & Minuman';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Katalog & Harga')
                    ->columns(2)
                    ->components([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama Menu')
                            ->required()
                            ->maxLength(150),
                        FileUpload::make('image_path')
                            ->label('Foto Menu')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->columnSpanFull(),
                        TextInput::make('cost_price')
                            ->label('HPP / Modal Bahan')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('stock')
                            ->label('Stok Porsi Awal')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('min_stock_alert')
                            ->label('Batas Alert Stok Menipis')
                            ->required()
                            ->numeric()
                            ->default(5),
                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('cost_price')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Stok Porsi')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Product $record): string => match (true) {
                        $record->stock === 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Filter::make('low_stock')
                    ->label('Stok Menipis / Kritis')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock_alert')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
