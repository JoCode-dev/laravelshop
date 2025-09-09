<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartRequest;
use App\Models\Cart;
use App\Traits\ApiResponses;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // ✅ Récupérer le panier (session ou DB selon l'état de connexion)
    public function index(Request $request): JsonResponse
    {
        try {
            if ($request->user()) {
                // Utilisateur connecté → Panier en DB
                $cartItems = Cart::where('user_id', $request->user()->id)
                    ->with('product')
                    ->get();
            } else {
                // Visiteur anonyme → Panier en session
                $cartItems = session()->get('cart', []);
            }

            return $this->successResponse([
                'message' => 'Cart fetched successfully',
                'data' => $cartItems,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart fetch failed',
                'errors' => $e->getMessage(),
            ], 'Cart fetch failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ✅ Ajouter au panier (session ou DB selon l'état de connexion)
    public function store(CartRequest $request): JsonResponse
    {
        try {
            if ($request->user()) {
                // Utilisateur connecté → Sauvegarder en DB
                $user = $request->user();
                $validatedData = $request->validated();

                $cart = Cart::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $validatedData['product_id']
                    ],
                    [
                        'quantity' => $validatedData['quantity'],
                        'price' => $validatedData['price']
                    ]
                );

                $data = $cart->load('product');
            } else {
                // Visiteur anonyme → Sauvegarder en session
                $validatedData = $request->validated();
                $validatedData['id'] = uniqid(); // ID unique pour la session

                $cartItems = session()->get('cart', []);

                // Vérifier si le produit existe déjà dans le panier session
                $existingIndex = null;
                foreach ($cartItems as $index => $item) {
                    if ($item['product_id'] == $validatedData['product_id']) {
                        $existingIndex = $index;
                        break;
                    }
                }

                if ($existingIndex !== null) {
                    // Mettre à jour la quantité si le produit existe déjà
                    $cartItems[$existingIndex]['quantity'] += $validatedData['quantity'];
                } else {
                    // Ajouter un nouvel élément
                    $cartItems[] = $validatedData;
                }

                session()->put('cart', $cartItems);
                $data = $validatedData;
            }

            return $this->successResponse([
                'message' => 'Cart item added successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart storage failed',
                'errors' => $e->getMessage(),
            ], 'Cart storage failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ✅ Mettre à jour le panier
    public function update(CartRequest $request, $id): JsonResponse
    {
        try {
            if ($request->user()) {
                // Utilisateur connecté → Mettre à jour en DB
                $user = $request->user();
                $validatedData = $request->validated();

                $cartItem = Cart::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$cartItem) {
                    return $this->errorResponse([
                        'message' => 'Cart item not found',
                    ], 'Cart item not found', Response::HTTP_NOT_FOUND);
                }

                $cartItem->update([
                    'quantity' => $validatedData['quantity'],
                    'price' => $validatedData['price']
                ]);

                $data = $cartItem->load('product');
            } else {
                // Visiteur anonyme → Mettre à jour en session
                $validatedData = $request->validated();
                $cartItems = session()->get('cart', []);

                foreach ($cartItems as $key => $item) {
                    if ($item['id'] == $id) {
                        $cartItems[$key]['quantity'] = $validatedData['quantity'];
                        $cartItems[$key]['price'] = $validatedData['price'];
                        break;
                    }
                }

                session()->put('cart', $cartItems);
                $data = $validatedData;
            }

            return $this->successResponse([
                'message' => 'Cart item updated successfully',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart update failed',
                'errors' => $e->getMessage(),
            ], 'Cart update failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ✅ Supprimer du panier
    public function removeItem($id): JsonResponse
    {
        try {
            if (request()->user()) {
                // Utilisateur connecté → Supprimer de la DB
                $user = request()->user();

                $cartItem = Cart::where('id', $id)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$cartItem) {
                    return $this->errorResponse([
                        'message' => 'Cart item not found',
                    ], 'Cart item not found', Response::HTTP_NOT_FOUND);
                }

                $cartItem->delete();
            } else {
                // Visiteur anonyme → Supprimer de la session
                $cartItems = session()->get('cart', []);
                $cartItems = array_filter($cartItems, function ($item) use ($id) {
                    return $item['id'] !== $id;
                });

                session()->put('cart', array_values($cartItems));
            }

            return $this->successResponse([
                'message' => 'Cart item removed successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart item removal failed',
                'errors' => $e->getMessage(),
            ], 'Cart item removal failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ✅ Vider le panier
    public function clearCart(Request $request): JsonResponse
    {
        try {
            if ($request->user()) {
                // Utilisateur connecté → Vider la DB
                Cart::where('user_id', $request->user()->id)->delete();
            } else {
                // Visiteur anonyme → Vider la session
                session()->forget('cart');
            }

            return $this->successResponse([
                'message' => 'Cart cleared successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart clearing failed',
                'errors' => $e->getMessage(),
            ], 'Cart clearing failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ✅ NOUVELLE MÉTHODE : Migrer le panier session vers DB lors de la connexion
    public function migrateSessionToDatabase(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $sessionCart = session()->get('cart', []);

            if (empty($sessionCart)) {
                return $this->successResponse([
                    'message' => 'No session cart to migrate',
                ]);
            }

            foreach ($sessionCart as $item) {
                Cart::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'product_id' => $item['product_id']
                    ],
                    [
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ]
                );
            }

            // Vider le panier session après migration
            session()->forget('cart');

            return $this->successResponse([
                'message' => 'Session cart migrated to database successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse([
                'message' => 'Cart migration failed',
                'errors' => $e->getMessage(),
            ], 'Cart migration failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
