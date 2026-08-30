<?php

namespace App\Models;

use App\Support\ShopMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'free_shipping_threshold',
        'estimated_days',
        'icon',
        'is_active',
    ];

    public function iconUrl(): ?string
    {
        return ShopMedia::url($this->icon);
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'free_shipping_threshold' => 'integer',
            'estimated_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function calculateCost(int $orderSubtotal): int
    {
        if ($this->free_shipping_threshold !== null && $orderSubtotal >= $this->free_shipping_threshold) {
            return 0;
        }

        return (int) $this->price;
    }
}
