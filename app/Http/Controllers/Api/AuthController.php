<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OAT;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    #[OAT\Post(
        path: '/auth/login',
        tags: ['Autenticación'],
        summary: 'Iniciar sesión',
        description: 'Valida credenciales y devuelve un token Sanctum',
        requestBody: new OAT\RequestBody(
            required: true,
            content: new OAT\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OAT\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OAT\Property(property: 'password', type: 'string', example: 'password'),
                    new OAT\Property(property: 'device_name', type: 'string', example: 'postman'),
                ]
            )
        ),
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OAT\JsonContent(
                    properties: [
                        new OAT\Property(property: 'data', type: 'object',
                            properties: [
                                new OAT\Property(property: 'token', type: 'string'),
                                new OAT\Property(property: 'token_type', type: 'string'),
                                new OAT\Property(property: 'user', type: 'object'),
                            ]
                        ),
                        new OAT\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OAT\Response(response: 422, description: 'Error de validación'),
            new OAT\Response(response: 429, description: 'Rate limit excedido'),
        ]
    )]
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

    #[OAT\Post(
        path: '/auth/logout',
        tags: ['Autenticación'],
        summary: 'Cerrar sesión',
        description: 'Revoca el token actual',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Sesión cerrada'),
            new OAT\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada correctamente');
    }

    #[OAT\Post(
        path: '/auth/logout-all',
        tags: ['Autenticación'],
        summary: 'Cerrar todas las sesiones',
        description: 'Revoca todos los tokens del usuario',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(response: 200, description: 'Sesiones cerradas'),
            new OAT\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(null, 'Sesión cerrada en todos los dispositivos');
    }

    #[OAT\Get(
        path: '/auth/me',
        tags: ['Autenticación'],
        summary: 'Obtener usuario actual',
        description: 'Devuelve el usuario autenticado',
        security: [['sanctum' => []]],
        responses: [
            new OAT\Response(
                response: 200,
                description: 'Usuario autenticado',
                content: new OAT\JsonContent(
                    properties: [
                        new OAT\Property(property: 'data', type: 'object',
                            properties: [
                                new OAT\Property(property: 'id', type: 'integer'),
                                new OAT\Property(property: 'name', type: 'string'),
                                new OAT\Property(property: 'email', type: 'string'),
                                new OAT\Property(property: 'role', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OAT\Response(response: 401, description: 'No autenticado'),
        ]
    )]
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
