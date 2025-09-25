<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Collection;

class OrderService
{
    /**
     * Calculate the total amount of cart items with an optional discount.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Cart>  $cartItems
     * @param  float  $discount
     * @return float
     */
    public function calculateTotal(Collection $cartItems, float $discount = 0)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += $cartItem->product->price * $cartItem->quantity;
        }

        return max(0, $total - $discount);
    }

    /**
     * Apply a percentage discount to a total amount.
     *
     * @param  float  $total
     * @param  float  $discountPercentage
     * @return float
     */
    public function applyDiscount(float $total, float $discountPercentage)
    {
        return $total * (1 - ($discountPercentage / 100));
    }
}
