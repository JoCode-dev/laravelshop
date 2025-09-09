<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $cartItems = Cart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return $this->errorResponse(
                ['message' => 'Your cart is empty.'],
                'Cannot create order with an empty cart',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $order = $this->createOrderFromCart($user, $cartItems);

            return $this->successResponse([
                'message' => 'Order created successfully',
                'data' => $order->load('items'),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Order creation failed',
                'errors' => $e->getMessage(),
            ], 'Order creation failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createOrderFromCart($user, Collection $cartItems): Order
    {
        return DB::transaction(function () use ($user, $cartItems) {
            $totalAmount = $cartItems->sum(fn($item) => $item->quantity * $item->price);

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => OrderStatus::PENDING,
            ]);

            $orderItems = $cartItems->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ])->all();

            $order->items()->createMany($orderItems);

            Cart::where('user_id', $user->id)->delete();

            return $order;
        });
    }
}
