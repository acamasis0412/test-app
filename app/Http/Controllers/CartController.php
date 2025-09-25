<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartController extends Controller
{
    /**
     * Display a listing of the user's cart items.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();

        return CartResource::collection($user->carts()->with('product')->get());
    }

    /**
     * Store a newly created cart item in storage or update quantity if it exists.
     *
     * @param  StoreCartRequest  $request
     * @return \App\Http\Resources\CartResource|Illuminate\Http\JsonResponse
     */
    public function store(StoreCartRequest $request): CartResource|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Not enough stock available'], 400);
        }

        $cartItem = $user->carts()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'Not enough stock available for the requested quantity'], 400);
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = $user->carts()->create([
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        return new CartResource($cartItem->load('product'));
    }

    /**
     * Update the specified cart item in storage.
     *
     * @param  UpdateCartRequest  $request
     * @param  \App\Models\Cart  $cart
     * @return \App\Http\Resources\CartResource|Illuminate\Http\JsonResponse
     */
    public function update(UpdateCartRequest $request, Cart $cart): CartResource|JsonResponse
    {
        $user = Auth::user();
        // Ensure the cart item belongs to the authenticated user
        if ($user->id !== $cart->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = $cart->product;

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Not enough stock available'], 400);
        }

        $cart->update($request->all());
        return new CartResource($cart->load('product'));
    }

    /**
     * Remove the specified cart item from storage.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Cart $cart): JsonResponse
    {
        $user = Auth::user();
        // Ensure the cart item belongs to the authenticated user
        if ($user->id !== $cart->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $cart->delete();
        return response()->json(null, 204);
    }
}
