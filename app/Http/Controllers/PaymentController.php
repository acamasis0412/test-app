<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Process a mock payment for a specific order.
     *
     * @param  StorePaymentRequest  $request
     * @param  \App\Models\Order  $order
     * @return \App\Http\Resources\PaymentResource|Illuminate\Http\JsonResponse
     */
    public function store(StorePaymentRequest $request, Order $order): PaymentResource|JsonResponse
    {
        $user = Auth::user();
        // Ensure the order belongs to the authenticated user
        if ($user->id !== $order->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mock payment processing
        $status = (rand(0, 1) === 1) ? 'success' : 'failed'; // 50% chance of success

        $payment = $order->payments()->create([
            'amount' => $request->amount,
            'status' => $status,
        ]);

        if ($status === 'success') {
            $order->update(['status' => 'confirmed']);
        }

        return new PaymentResource($payment);
    }

    /**
     * Display the specified payment details.
     *
     * @param  \App\Models\Payment  $payment
     * @return \App\Http\Resources\PaymentResource|Illuminate\Http\JsonResponse
     */
    public function show(Payment $payment): PaymentResource|JsonResponse
    {
        $user = Auth::user();
        // Ensure the payment belongs to an order of the authenticated user
        if ($user->id !== $payment->order->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new PaymentResource($payment);
    }
}
