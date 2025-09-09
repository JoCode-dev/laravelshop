<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $this->authorize('viewAny', $user);

        $productsCount = Product::count();
        $ordersCount = Order::count();
        $paymentsCount = Payment::count();
        $simpleUsersCount = User::simpleUsers()->count();

        $stats = [
            'products_count' => $productsCount,
            'orders_count' => $ordersCount,
            'payments_count' => $paymentsCount,
            'simple_users_count' => $simpleUsersCount,
        ];

        return $this->successResponse([
            'message' => 'Dashboard overview fetched successfully',
            'data' => $stats,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $data = User::paginate($request->input('per_page', 10));

        return $this->successResponse([
            'message' => 'Users fetched successfully',
            'data' => $data,
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $data = Product::paginate($request->input('per_page', 10));

        return $this->successResponse([
            'message' => 'Products fetched successfully',
            'data' => $data,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $orders = Order::query()
            ->when($request->input('status'), fn(Builder $query, $status) => $query->where('status', $status))
            ->when($request->input('date'), fn(Builder $query, $date) => $query->whereDate('created_at', $date))
            ->paginate($request->input('per_page', 10));

        return $this->successResponse([
            'message' => 'Orders fetched successfully',
            'data' => $orders,
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $data = Payment::paginate($request->input('per_page', 10));

        return $this->successResponse([
            'message' => 'Payments fetched successfully',
            'data' => $data,
        ]);
    }

    public function sellStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $stats = Payment::query()
            ->when($request->input('start_date'), fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($request->input('end_date'), fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date))
            ->selectRaw('SUM(amount) as total_revenue, COUNT(id) as total_sales')
            ->first();

        return $this->successResponse([
            'message' => 'Sell stats fetched successfully',
            'data' => $stats,
        ]);
    }

    public function topProducts(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $products = Product::orderBy('sold_count', 'desc')->limit(5)->get();

        return $this->successResponse([
            'message' => 'Top products fetched successfully',
            'data' => $products,
        ]);
    }
}
