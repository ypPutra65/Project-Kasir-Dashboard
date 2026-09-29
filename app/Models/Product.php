<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'cost_price',
        'selling_price',
        'image_path',
        'stock',
        'min_stock_alert',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock' => 'integer',
            'min_stock_alert' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke Category (Setiap Produk dimiliki oleh satu Kategori).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke OrderItem (Setiap Produk dapat ada pada banyak OrderItem).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relasi ke StockMutation (Setiap Produk memiliki banyak riwayat mutasi stok).
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    /**
     * Helper untuk mengecek apakah stok porsi sudah menipis/kritis.
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock_alert;
    }

    /**
     * Accessor format Rupiah untuk Harga Jual.
     */
    protected function formattedSellingPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->selling_price, 0, ',', '.')
        );
    }

    /**
     * Accessor format Rupiah untuk HPP (Modal).
     */
    protected function formattedCostPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->cost_price, 0, ',', '.')
        );
    }
}
