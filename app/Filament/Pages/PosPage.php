<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMutation;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Kasir (POS)';

    protected static ?string $title = 'Kasir (Point of Sale)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.pos-page';

    /**
     * State keranjang pesanan.
     * Format: [product_id => ['id' => ..., 'name' => ..., 'price' => ..., 'cost_price' => ..., 'qty' => ..., 'subtotal' => ..., 'max_stock' => ...]]
     */
    public array $cart = [];

    /**
     * Kategori yang sedang dipilih untuk filter.
     */
    public ?int $selectedCategoryId = null;

    /**
     * Kata kunci pencarian produk.
     */
    public string $search = '';

    /**
     * Metode pembayaran: 'cash', 'qris', 'transfer'.
     */
    public string $paymentMethod = 'cash';

    /**
     * Nominal uang yang diterima dari pembeli.
     */
    public ?float $paidAmount = null;

    /**
     * Status modal pembayaran.
     */
    public bool $showPaymentModal = false;

    /**
     * Status modal sukses transaksi & struk.
     */
    public bool $showSuccessModal = false;

    /**
     * Data pesanan terakhir yang berhasil dibayar.
     */
    public ?Order $lastOrder = null;

    /**
     * Konfigurasi lebar layar penuh (Full-width).
     */
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * Ambil daftar kategori aktif.
     */
    public function getCategoriesProperty(): Collection
    {
        return Category::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Ambil daftar produk aktif berdasarkan filter.
     */
    public function getProductsProperty(): Collection
    {
        return Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($this->selectedCategoryId, fn ($query) => $query->where('category_id', $this->selectedCategoryId))
            ->when(filled($this->search), fn ($query) => $query->where('name', 'like', '%' . trim($this->search) . '%'))
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Filter kategori menu.
     */
    public function selectCategory(?int $categoryId = null): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    /**
     * Tambahkan menu ke dalam keranjang.
     */
    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product || ! $product->is_active) {
            Notification::make()
                ->title('Menu tidak tersedia')
                ->danger()
                ->send();

            return;
        }

        if ($product->stock <= 0) {
            Notification::make()
                ->title('Stok Habis')
                ->body("Menu {$product->name} saat ini sedang habis.")
                ->warning()
                ->send();

            return;
        }

        $currentQty = $this->cart[$productId]['qty'] ?? 0;

        if ($currentQty + 1 > $product->stock) {
            Notification::make()
                ->title('Batas Stok Tercapai')
                ->body("Sisa stok {$product->name} hanya tersedia {$product->stock} porsi.")
                ->warning()
                ->send();

            return;
        }

        $newQty = $currentQty + 1;
        $price = (float) $product->selling_price;
        $costPrice = (float) $product->cost_price;

        $this->cart[$productId] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $price,
            'cost_price' => $costPrice,
            'qty' => $newQty,
            'subtotal' => $price * $newQty,
            'max_stock' => $product->stock,
        ];
    }

    /**
     * Tambah kuantitas produk di keranjang.
     */
    public function incrementQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $product = Product::find($productId);
        $maxStock = $product?->stock ?? $this->cart[$productId]['max_stock'] ?? 0;

        if ($this->cart[$productId]['qty'] + 1 > $maxStock) {
            Notification::make()
                ->title('Batas Stok Tercapai')
                ->body("Sisa stok porsi maksimal {$maxStock}.")
                ->warning()
                ->send();

            return;
        }

        $this->cart[$productId]['qty']++;
        $this->cart[$productId]['subtotal'] = $this->cart[$productId]['qty'] * $this->cart[$productId]['price'];
    }

    /**
     * Kurangi kuantitas produk di keranjang.
     */
    public function decrementQty(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['qty'] > 1) {
            $this->cart[$productId]['qty']--;
            $this->cart[$productId]['subtotal'] = $this->cart[$productId]['qty'] * $this->cart[$productId]['price'];
        } else {
            $this->removeFromCart($productId);
        }
    }

    /**
     * Hapus satu item dari keranjang.
     */
    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    /**
     * Kosongkan seluruh keranjang.
     */
    public function clearCart(): void
    {
        $this->cart = [];
    }

    /**
     * Buka modal pembayaran kasir.
     */
    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang Kosong')
                ->body('Silakan pilih menu terlebih dahulu sebelum melakukan pembayaran.')
                ->warning()
                ->send();

            return;
        }

        $this->paymentMethod = 'cash';
        $this->paidAmount = null;
        $this->showPaymentModal = true;
    }

    /**
     * Tutup modal pembayaran.
     */
    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }

    /**
     * Tutup modal sukses transaksi.
     */
    public function closeSuccessModal(): void
    {
        $this->showSuccessModal = false;
        $this->lastOrder = null;
    }

    /**
     * Set metode pembayaran dan sesuaikan nilai bayar jika non-tunai.
     */
    public function setPaymentMethod(string $method): void
    {
        $this->paymentMethod = $method;

        if ($method !== 'cash') {
            $this->paidAmount = $this->totalAmount;
        }
    }

    /**
     * Set nominal uang tunai cepat via tombol pecahan.
     */
    public function setPaidAmount(float $amount): void
    {
        $this->paidAmount = $amount;
    }

    /**
     * Set pembayaran uang pas.
     */
    public function setExactCash(): void
    {
        $this->paidAmount = $this->totalAmount;
    }

    /**
     * Hitung nilai uang kembalian.
     */
    public function getChangeAmountProperty(): float
    {
        if ($this->paymentMethod !== 'cash') {
            return 0.00;
        }

        return max(0, ($this->paidAmount ?? 0) - $this->totalAmount);
    }

    /**
     * Eksekusi transaksi penjualan atomik & pemotongan stok.
     */
    public function checkout(): void
    {
        if (empty($this->cart)) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang pesanan tidak boleh kosong.',
            ]);
        }

        if (! in_array($this->paymentMethod, ['cash', 'qris', 'transfer'])) {
            throw ValidationException::withMessages([
                'paymentMethod' => 'Metode pembayaran tidak valid.',
            ]);
        }

        $totalAmount = $this->totalAmount;

        if ($this->paymentMethod === 'cash') {
            if (is_null($this->paidAmount) || $this->paidAmount < $totalAmount) {
                throw ValidationException::withMessages([
                    'paidAmount' => 'Uang tunai yang diterima kurang dari total belanja.',
                ]);
            }
        } else {
            $this->paidAmount = $totalAmount;
        }

        $paidAmount = (float) $this->paidAmount;
        $changeAmount = $this->paymentMethod === 'cash' ? max(0, $paidAmount - $totalAmount) : 0.00;

        /** @var Order|null $order */
        $order = null;

        DB::transaction(function () use (&$order, $totalAmount, $paidAmount, $changeAmount) {
            $productIds = array_keys($this->cart);

            // Kunci baris produk untuk mencegah race condition
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validasi sisa stok riil database
            foreach ($this->cart as $productId => $item) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active || $product->stock < $item['qty']) {
                    $available = $product ? $product->stock : 0;
                    throw ValidationException::withMessages([
                        'cart' => "Stok menu '{$item['name']}' tidak mencukupi (sisa: {$available} porsi).",
                    ]);
                }
            }

            // Generate nomor nota unik: BL-YYYYMMDD-XXXX
            $todayCount = Order::whereDate('ordered_at', today())->count();
            $orderNumber = sprintf('BL-%s-%04d', date('Ymd'), $todayCount + 1);

            // Simpan header transaksi nota
            $order = Order::create([
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $this->paymentMethod,
                'status' => 'paid',
                'ordered_at' => now(),
            ]);

            // Simpan detail item nota, potong stok, dan catat mutasi penjualan
            foreach ($this->cart as $productId => $item) {
                $product = $products->get($productId);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['qty'],
                    'cost_price' => $product->cost_price,
                    'unit_price' => $product->selling_price,
                    'subtotal' => $item['subtotal'],
                ]);

                $product->decrement('stock', $item['qty']);

                StockMutation::create([
                    'product_id' => $product->id,
                    'type' => 'sale',
                    'quantity' => $item['qty'],
                    'notes' => "Penjualan nota {$order->order_number}",
                    'created_at' => now(),
                ]);
            }
        });

        $this->lastOrder = $order?->load(['orderItems.product']);
        $this->cart = [];
        $this->showPaymentModal = false;
        $this->showSuccessModal = true;

        Notification::make()
            ->title('Transaksi Berhasil!')
            ->body("Nota {$order->order_number} berhasil dibayar.")
            ->success()
            ->send();
    }

    /**
     * Hitung total nilai belanja seluruh item di keranjang.
     */
    public function getTotalAmountProperty(): float
    {
        return (float) array_sum(array_column($this->cart, 'subtotal'));
    }

    /**
     * Hitung total porsi seluruh item di keranjang.
     */
    public function getTotalItemsProperty(): int
    {
        return (int) array_sum(array_column($this->cart, 'qty'));
    }
}
