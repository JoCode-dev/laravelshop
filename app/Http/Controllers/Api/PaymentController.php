<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use ApiResponses;

    public function store(Request $request): JsonResponse
    {
        $request->validate(['order_id' => 'required|integer|exists:orders,id']);
        $user = $request->user();
        $order = Order::with('items.product')->findOrFail($request->order_id);

        if ($user->id !== $order->user_id) {
            return $this->errorResponse(['message' => 'Forbidden'], 'You are not authorized to pay for this order.', Response::HTTP_FORBIDDEN);
        }

        if ($order->status === OrderStatus::PAID) {
            return $this->errorResponse(['message' => 'Order already paid'], 'This order has already been paid.', Response::HTTP_CONFLICT);
        }

        try {
            $payment = $this->processPayment($order);

            return $this->successResponse([
                'message' => 'Payment created successfully',
                'data' => $payment,
            ], Response::HTTP_CREATED);
        } catch (InsufficientStockException $e) {
            return $this->errorResponse(['message' => $e->getMessage()], $e->getMessage(), Response::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return $this->errorResponse(['message' => 'Payment creation failed'], $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function processPayment(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $updatedRows = Product::where('id', $item->product_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);

                if ($updatedRows === 0) {
                    throw new InsufficientStockException("Not enough stock for product: {$item->product->name}");
                }
            }

            $order->update(['status' => OrderStatus::PAID]);

            return Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'status' => PaymentStatus::PAID,
            ]);
        });
    }
}
