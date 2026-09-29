<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'ordered_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke OrderItem (Satu Order memiliki banyak OrderItem).
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope untuk memfilter order pada hari ini.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('ordered_at', today());
    }

    /**
     * Scope untuk memfilter order dengan status paid.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Accessor format Rupiah untuk Total Belanja.
     */
    protected function formattedTotalAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp ' . number_format((float) $this->total_amount, 0, ',', '.')
        );
    }
}
