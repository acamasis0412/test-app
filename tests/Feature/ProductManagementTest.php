<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateAdmin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        return $this->actingAs($admin, 'sanctum');
    }

    protected function authenticateCustomer()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        return $this->actingAs($customer, 'sanctum');
    }

    /**
     * Test admin can create a product.
     */
    public function test_admin_can_create_product(): void
    {
        $this->authenticateAdmin();
        $category = Category::factory()->create();

        $productData = [
            'name' => 'New Product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
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
                     ]
                 ])
                 ->assertJsonFragment(['name' => 'New Product']);

        $this->assertDatabaseHas('products', $productData);
    }

    /**
     * Test customer cannot create a product.
     */
    public function test_customer_cannot_create_product(): void
    {
        $this->authenticateCustomer();
        $category = Category::factory()->create();

        $productData = [
            'name' => 'New Product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['name' => 'New Product']);
    }

    /**
     * Test admin can update a product.
     */
    public function test_admin_can_update_product(): void
    {
        $this->authenticateAdmin();
        $product = Product::factory()->create();

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 120.00,
        ];

        $response = $this->putJson('/api/products/' . $product->id, $updateData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
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
                     ]
                 ])
                 ->assertJsonFragment(['name' => 'Updated Product Name']);

        $this->assertDatabaseHas('products', array_merge($updateData, ['id' => $product->id]));
    }

    /**
     * Test admin can delete a product.
     */
    public function test_admin_can_delete_product(): void
    {
        $this->authenticateAdmin();
        $product = Product::factory()->create();

        $response = $this->deleteJson('/api/products/' . $product->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /**
     * Test product listing with filters.
     */
    public function test_product_listing_can_be_filtered(): void
    {
        $this->authenticateCustomer(); // Authenticate as customer to access public product APIs
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->create(['name' => 'Apple', 'price' => 10.00, 'category_id' => $category1->id]);
        Product::factory()->create(['name' => 'Banana', 'price' => 20.00, 'category_id' => $category1->id]);
        Product::factory()->create(['name' => 'Orange', 'price' => 30.00, 'category_id' => $category2->id]);

        // Filter by category
        $response = $this->getJson('/api/products?category_id=' . $category1->id);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [
                     '*' => [
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
                     ]
                 ]])
                 ->assertJsonFragment(['name' => 'Apple'])
                 ->assertJsonMissing(['name' => 'Orange']);

        // Filter by price range
        $response = $this->getJson('/api/products?min_price=15&max_price=25');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [
                     '*' => [
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
                     ]
                 ]])
                 ->assertJsonFragment(['name' => 'Banana']);

        // Search by name
        $response = $this->getJson('/api/products?search=Apple');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [
                     '*' => [
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
                     ]
                 ]])
                 ->assertJsonFragment(['name' => 'Apple']);

        // Combined filters
        $response = $this->getJson('/api/products?category_id=' . $category1->id . '&min_price=5&max_price=15');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [
                     '*' => [
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
                     ]
                 ]])
                 ->assertJsonFragment(['name' => 'Apple']);
    }
}
