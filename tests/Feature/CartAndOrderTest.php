<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderPlaced;

class CartAndOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateCustomer()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        return $this->actingAs($customer, 'sanctum');
    }

    protected function authenticateAdmin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($admin, 'sanctum');
    }

    /**
     * Test customer can add product to cart.
     */
    public function test_customer_can_add_product_to_cart(): void
    {
        $this->authenticateCustomer();
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'quantity',
                         'product' => [
                             'id',
                             'name',
                             'description',
                             'price',
                             'stock',
                             'category' => [
                                 'id',
                                 'name',
                                 'description',
                                 'created_at',
                                 'updated_at',
                             ],
                             'created_at',
                             'updated_at',
                         ],
                         'created_at',
                         'updated_at',
                     ]
                 ])
                 ->assertJsonFragment(['quantity' => 1]);

        $this->assertDatabaseHas('carts', [
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    /**
     * Test customer can update cart item quantity.
     */
    public function test_customer_can_update_cart_item_quantity(): void
    {
        $this->authenticateCustomer();
        $product = Product::factory()->create(['stock' => 10]);
        $cartItem = Cart::factory()->create(['user_id' => auth()->id(), 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->putJson('/api/cart/' . $cartItem->id, [
            'quantity' => 3,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'user_id',
                         'quantity',
                         'product' => [
                             'id',
                             'name',
                             'description',
                             'price',
                             'stock',
                             'category' => [
                                 'id',
                                 'name',
                                 'description',
                                 'created_at',
                                 'updated_at',
                             ],
                             'created_at',
                             'updated_at',
                         ],
                         'created_at',
                         'updated_at',
                     ]
                 ])
                 ->assertJsonFragment(['quantity' => 3]);

        $this->assertDatabaseHas('carts', [
            'id' => $cartItem->id,
            'quantity' => 3,
        ]);
    }

    /**
     * Test customer can remove product from cart.
     */
    public function test_customer_can_remove_product_from_cart(): void
    {
        $this->authenticateCustomer();
        $cartItem = Cart::factory()->create(['user_id' => auth()->id()]);

        $response = $this->deleteJson('/api/cart/' . $cartItem->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('carts', ['id' => $cartItem->id]);
    }

    /**
     * Test customer can create an order from cart.
     */
    public function test_customer_can_create_order_from_cart(): void
    {
        $this->authenticateCustomer();
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
        Cart::factory()->create(['user_id' => auth()->id(), 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->postJson('/api/orders');

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'total_amount',
                         'status',
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'created_at',
                             'updated_at',
                         ],
                         'payments',
                         'created_at',
                         'updated_at',
                     ]
                 ])
                 ->assertJsonFragment(['total_amount' => 200.00, 'status' => 'pending']);

        $this->assertDatabaseHas('orders', [
            'user_id' => auth()->id(),
            'total_amount' => 200.00,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('carts', ['user_id' => auth()->id()]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
    }

    /**
     * Test user receives notification after placing an order.
     */
    public function test_user_receives_notification_after_order(): void
    {
        Notification::fake();
        $this->authenticateCustomer();
        $product = Product::factory()->create(['stock' => 5, 'price' => 50]);
        Cart::factory()->create(['user_id' => auth()->id(), 'product_id' => $product->id, 'quantity' => 2]);
        $user = auth()->user();

        $response = $this->postJson('/api/orders');
        $response->assertStatus(201);

        Notification::assertSentTo(
            $user,
            OrderPlaced::class
        );
    }

    /**
     * Test admin can update order status.
     */
    public function test_admin_can_update_order_status(): void
    {
        $this->authenticateAdmin();
        $customer = User::factory()->create(['role' => 'customer']);

        $order = Order::factory()
            ->create([
                'status' => 'pending', 
                'user_id' => $customer->id
            ]);

        $response = $this->putJson('/api/orders/' . $order->id . '/status', [
            'status' => 'shipped',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'total_amount',
                         'status',
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'created_at',
                             'updated_at',
                         ],
                         'payments',
                         'created_at',
                         'updated_at',
                     ]
                 ])
                 ->assertJsonFragment(['status' => 'shipped']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);
    }
}
