<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMutation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static ?string $modelLabel = 'Riwayat Nota';

    protected static ?string $pluralModelLabel = 'Riwayat Transaksi';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->defaultSort('ordered_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('ordered_at')
                    ->label('Waktu Transaksi')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Belanja')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode Bayar')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'qris' => 'info',
                        'transfer' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Paid (Lunas)',
                        'cancelled' => 'Cancelled (Void)',
                        default => $state,
                    }),
            ])
            ->filters([
                Filter::make('ordered_at')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('ordered_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('ordered_at', '<=', $date));
                    }),
                SelectFilter::make('payment_method')
                    ->label('Metode Bayar')
                    ->options([
                        'cash' => 'Cash (Tunai)',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                    ]),
                SelectFilter::make('status')
                    ->label('Status Order')
                    ->options([
                        'paid' => 'Paid (Lunas)',
                        'cancelled' => 'Cancelled (Void)',
                    ]),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail Nota')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (Order $record): string => "Detail Transaksi: {$record->order_number}")
                    ->modalWidth(Width::TwoExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.components.order-detail', [
                        'order' => $record->loadMissing(['orderItems.product.category']),
                    ])),
                Action::make('reprintReceipt')
                    ->label('Cetak Ulang Struk')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->color('info')
                    ->modalHeading(fn (Order $record): string => "Cetak Ulang Struk - {$record->order_number}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Order $record) => view('filament.resources.orders.components.receipt-modal', [
                        'order' => $record->loadMissing('orderItems.product'),
                    ])),
                Action::make('voidOrder')
                    ->label('Batalkan Transaksi')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status === 'paid')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Transaksi (Void Order)')
                    ->modalDescription(fn (Order $record): string => "Apakah Anda yakin ingin membatalkan transaksi nota {$record->order_number}? Seluruh stok porsi akan dikembalikan ke inventaris.")
                    ->modalSubmitActionLabel('Ya, Batalkan Transaksi')
                    ->form([
                        TextInput::make('reason')
                            ->label('Alasan Pembatalan')
                            ->placeholder('Contoh: Salah input pesanan / Pelanggan membatalkan pesanan')
                            ->default('Koreksi pembatalan kasir')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Order $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $order = Order::where('id', $record->id)->lockForUpdate()->firstOrFail();

                            if ($order->status !== 'paid') {
                                Notification::make()
                                    ->title('Gagal Membatalkan Transaksi')
                                    ->body('Hanya pesanan berstatus paid yang dapat dibatalkan.')
                                    ->danger()
                                    ->send();

                                throw ValidationException::withMessages([
                                    'order' => 'Hanya pesanan berstatus paid yang dapat dibatalkan.',
                                ]);
                            }

                            $order->status = 'cancelled';
                            $order->save();

                            $orderItems = $order->orderItems()->get();
                            $productIds = $orderItems->pluck('product_id')->filter()->unique()->toArray();

                            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
                            $reason = !empty($data['reason']) ? $data['reason'] : 'Pembatalan transaksi';

                            foreach ($orderItems as $item) {
                                if ($item->product_id && isset($products[$item->product_id])) {
                                    $product = $products[$item->product_id];
                                    $product->stock += $item->quantity;
                                    $product->save();

                                    StockMutation::create([
                                        'product_id' => $product->id,
                                        'type' => 'adjustment',
                                        'quantity' => $item->quantity,
                                        'notes' => "Pengembalian stok pembatalan nota {$order->order_number} ({$reason})",
                                        'created_at' => now(),
                                    ]);
                                }
                            }
                        });

                        Notification::make()
                            ->title('Transaksi Berhasil Dibatalkan')
                            ->body("Nota {$record->order_number} berhasil dibatalkan dan stok porsi telah dikembalikan.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([]);
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
            'index' => ListOrders::route('/'),
        ];
    }
}
