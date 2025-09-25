<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use App\Models\Cart;
use App\Models\Product;

class OrderServiceTest extends TestCase
{
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    /**
     * Test calculateTotal method.
     */
    public function test_calculate_total(): void
    {
        $product1 = new Product(['price' => 100.00]);
        $product2 = new Product(['price' => 50.00]);

        $cartItem1 = new Cart(['quantity' => 2]);
        $cartItem1->setRelation('product', $product1);

        $cartItem2 = new Cart(['quantity' => 3]);
        $cartItem2->setRelation('product', $product2);

        $cartItems = new Collection([$cartItem1, $cartItem2]);

        $total = $this->orderService->calculateTotal($cartItems);
        $this->assertEquals(350.00, $total);

        $totalWithDiscount = $this->orderService->calculateTotal($cartItems, 50.00);
        $this->assertEquals(300.00, $totalWithDiscount);

        $totalWithExcessiveDiscount = $this->orderService->calculateTotal($cartItems, 500.00);
        $this->assertEquals(0.00, $totalWithExcessiveDiscount);
    }

    /**
     * Test applyDiscount method.
     */
    public function test_apply_discount(): void
    {
        $total = 100.00;
        $discountedTotal = $this->orderService->applyDiscount($total, 10);
        $this->assertEquals(90.00, $discountedTotal);

        $discountedTotal = $this->orderService->applyDiscount($total, 50);
        $this->assertEquals(50.00, $discountedTotal);

        $discountedTotal = $this->orderService->applyDiscount($total, 0);
        $this->assertEquals(100.00, $discountedTotal);

        $discountedTotal = $this->orderService->applyDiscount(0, 10);
        $this->assertEquals(0.00, $discountedTotal);
    }
}
