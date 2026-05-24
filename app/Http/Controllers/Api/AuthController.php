<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Login: valida credenciales y devuelve un token Sanctum.
     *
     * POST /api/auth/login
     * Body: { "email": "...", "password": "...", "device_name": "postman" }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = $this->authService->validateCredentials(
            $request->input('email'),
            $request->input('password'),
        );

        $deviceName = $request->input('device_name', 'api-token');
        $abilities = $this->authService->resolveAbilities($user);
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->slug,
            ],
        ], 'Login exitoso');
    }

    /**
     * Logout: revoca el token actual.
     *
     * POST /api/auth/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada correctamente');
    }

    /**
     * Logout de todos los dispositivos: revoca TODOS los tokens del usuario.
     *
     * POST /api/auth/logout-all
     * Header: Authorization: Bearer {token}
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(null, 'Sesión cerrada en todos los dispositivos');
    }

    /**
     * Me: devuelve el usuario autenticado actual.
     *
     * GET /api/auth/me
     * Header: Authorization: Bearer {token}
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->slug,
        ]);
    }
}
