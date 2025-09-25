<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create 2 admins
        User::factory()->count(2)->create(['role' => 'admin']);

        // Create 10 customers
        $customers = User::factory()->count(10)->create(['role' => 'customer']);

        // Create 5 categories
        $categories = Category::factory()->count(5)->create();

        // Create 20 products, each assigned to a random category
        $products = Product::factory()->count(20)->make()->each(function ($product) use ($categories) {
            $product->category_id = $categories->random()->id;
            $product->save();
        });

        // Create 10 carts, each for a random customer and product
        Cart::factory()->count(10)->make()->each(function ($cart) use ($customers, $products) {
            $cart->user_id = $customers->random()->id;
            $cart->product_id = $products->random()->id;
            $cart->save();
        });

        // Create 15 orders, each for a random customer
        Order::factory()->count(15)->make()->each(function ($order) use ($customers) {
            $order->user_id = $customers->random()->id;
            $order->save();
        });
    }
}
