<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Traits\ApiResponses;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        try {
            return $this->successResponse([
                'message' => 'Dashboard fetched successfully',
                'data' => [
                    'products' => Product::all(),
                    'products_count' => Product::count(),
                    'orders' => Order::all(),
                    'payments' => Payment::all(),
                    'users_simple' => User::simpleUsers(),
                ],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => 'Dashboard fetched failed',
                'errors' => $e->getMessage(),
            ], 'Dashboard fetched failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function users(): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        try {
            return $this->successResponse([
                'message' => 'Users fetched successfully',
                'data' => User::paginate(10),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => 'Users fetched failed',
                'errors' => $e->getMessage(),
            ], 'Users fetched failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function products(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);
        try {
            return $this->successResponse([
                'message' => 'Products fetched successfully',
                'data' => Product::paginate(10),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => 'Products fetched failed',
                'errors' => $e->getMessage(),
            ], 'Products fetched failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function orders(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        try {
            return $this->successResponse([
                'message' => 'Orders fetched successfully',
                'data' => Order::paginate(10),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => 'Orders fetched failed',
                'errors' => $e->getMessage(),
            ], 'Orders fetched failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function payments(): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        try {
            return $this->successResponse([
                'message' => 'Payments fetched successfully',
                'data' => Payment::paginate(10),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => 'Payments fetched failed',
                'errors' => $e->getMessage(),
            ], 'Payments fetched failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
