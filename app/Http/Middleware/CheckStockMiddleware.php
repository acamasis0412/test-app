<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $cartItems = $user->carts()->with('product')->get();

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            if ($product->stock < $cartItem->quantity) {
                return response()->json([
                    'message' => 'Not enough stock for ' . $product->name . '. Only ' . $product->stock . ' available.'
                ], 400);
            }
        }

        return $next($request);
    }
}
