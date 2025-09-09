<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponses;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'message' => 'Login successful',
                'data' => $user,
                'token' => $token,
            ], Response::HTTP_OK);
        }

        return $this->errorResponse([
            'message' => 'Login failed',
            'email' => 'The provided credentials do not match our records.',
        ], 'The provided credentials do not match our records.', Response::HTTP_UNAUTHORIZED);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->invalidate();
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse([
            'message' => 'Logout successful',
        ], Response::HTTP_OK);
    }

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        if (User::where('email', $data['email'])->exists()) {
            return $this->errorResponse([
                'message' => 'Register failed',
                'email' => 'The email already exists.',
            ], 'The email already exists.', Response::HTTP_BAD_REQUEST);
        }

        $user = User::create($data);

        return $this->successResponse([
            'message' => 'Register successful',
            'data' => $user,
        ], Response::HTTP_CREATED);
    }
}
