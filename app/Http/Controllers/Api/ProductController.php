<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponses;
    public function index(): JsonResponse
    {

        return $this->successResponse([
            'message' => 'Products fetched successfully',
            'data' => Product::all()
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->successResponse([
            'message' => 'Product fetched successfully',
            'data' => $product,
        ]);
    }
}