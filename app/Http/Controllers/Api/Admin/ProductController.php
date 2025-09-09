<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);
        $data = $request->validated();

        try {
            if ($request->hasFile('image')) {
                $data['image'] = $this->handleImageUpload($request);
            }

            $product = Product::create($data);

            return $this->successResponse([
                'message' => 'Product created successfully',
                'data' => $product,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Product creation failed',
                'errors' => $e->getMessage(),
            ], 'Product creation failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $data = $request->validated();

        try {
            if ($request->hasFile('image')) {
                $data['image'] = $this->handleImageUpload($request, $product);
            }

            $product->update($data);

            return $this->successResponse([
                'message' => 'Product updated successfully',
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Product update failed',
                'errors' => $e->getMessage(),
            ], 'Product update failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        try {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return $this->successResponse([
                'message' => 'Product deleted successfully',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Product deletion failed',
                'errors' => $e->getMessage(),
            ], 'Product deletion failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function handleImageUpload(Request $request, ?Product $product = null): string
    {
        if ($product?->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $request->file('image')->store('products', 'public');
    }
}
