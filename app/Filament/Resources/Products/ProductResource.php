<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use App\Models\StockMutation;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                Action::make('adjustStock')
                    ->label('Penyesuaian Stok')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('warning')
                    ->modalHeading(fn (Product $record): string => "Penyesuaian Stok: {$record->name}")
                    ->modalDescription(fn (Product $record): string => "Sisa stok saat ini: {$record->stock} porsi (Batas alert: {$record->min_stock_alert} porsi).")
                    ->modalSubmitActionLabel('Simpan Mutasi')
                    ->form([
                        Select::make('type')
                            ->label('Jenis Mutasi')
                            ->options([
                                'in' => 'Stok Masuk / Belanja Pagi (In)',
                                'waste' => 'Porsi Basi / Rusak (Waste)',
                                'adjustment' => 'Koreksi Fisik / Opname (Adjustment)',
                            ])
                            ->default('in')
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Jumlah Porsi')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->rules([
                                fn (Get $get, ?Product $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                    if ($get('type') === 'waste' && $record && (int) $value > $record->stock) {
                                        $fail("Jumlah pengurangan ({$value} porsi) melebihi sisa stok yang ada ({$record->stock} porsi).");
                                    }
                                },
                            ]),
                        TextInput::make('notes')
                            ->label('Catatan Mutasi')
                            ->placeholder('Contoh: Belanja bahan pagi / Basi saat tutup')
                            ->maxLength(255),
                    ])
                    ->action(function (Product $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $product = Product::where('id', $record->id)->lockForUpdate()->firstOrFail();

                            if ($data['type'] === 'waste' && $data['quantity'] > $product->stock) {
                                Notification::make()
                                    ->title('Gagal Menyesuaikan Stok')
                                    ->body("Pengurangan {$data['quantity']} porsi melebihi stok yang ada ({$product->stock} porsi).")
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'quantity' => "Pengurangan {$data['quantity']} porsi melebihi stok yang ada (sisa: {$product->stock} porsi).",
                                ]);
                            }

                            if ($data['type'] === 'in' || $data['type'] === 'adjustment') {
                                $product->stock += (int) $data['quantity'];
                            } elseif ($data['type'] === 'waste') {
                                $product->stock -= (int) $data['quantity'];
                            }

                            $product->save();

                            StockMutation::create([
                                'product_id' => $product->id,
                                'type' => $data['type'],
                                'quantity' => (int) $data['quantity'],
                                'notes' => $data['notes'] ?? null,
                                'created_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Stok Berhasil Disesuaikan')
                            ->body("Stok {$record->name} sekarang menjadi {$record->fresh()->stock} porsi.")
                            ->success()
                            ->send();
                    }),
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
