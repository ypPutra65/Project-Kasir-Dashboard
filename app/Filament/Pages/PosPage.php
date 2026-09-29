<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class PosPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Kasir (POS)';

    protected static ?string $title = 'Kasir (Point of Sale)';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.pos-page';

    /**
     * State keranjang pesanan.
     * Format: [product_id => ['id' => ..., 'name' => ..., 'price' => ..., 'cost_price' => ..., 'qty' => ..., 'subtotal' => ...]]
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
