<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\OrderService;
use App\Notifications\OrderPlaced;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    protected $orderService;

    /**
     * Create a new OrderController instance.
     *
     * @param  OrderService  $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the user's orders.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();
        
        return OrderResource::collection($user->orders()->with('payments')->get());
    }

    /**
     * Store a newly created order from the user's cart.
     *
     * @param  StoreOrderRequest  $request
     * @return \App\Http\Resources\OrderResource|Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function store(StoreOrderRequest $request): OrderResource|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $cartItems = $user->carts()->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product;
                if ($product->stock < $cartItem->quantity) {
                    DB::rollBack();
                    return response()->json(['message' => 'Not enough stock for ' . $product->name], 400);
                }
                $product->decrement('stock', $cartItem->quantity);
            }

            $totalAmount = $this->orderService->calculateTotal($cartItems);

            $order = $user->orders()->create([
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // Clear the cart after order creation
            $user->carts()->delete();

            // Notify customer
            $user->notify(new OrderPlaced($order));

            DB::commit();
            return new OrderResource($order->load('payments'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order creation failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the status of the specified order.
     *
     * @param  UpdateOrderStatusRequest  $request
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\OrderResource
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $order->update(['status' => $request->status]);
        return new OrderResource($order);
    }
}
