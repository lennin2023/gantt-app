<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

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

        $user = User::with('role')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Nombre del dispositivo (útil para identificar tokens en la DB)
        $deviceName = $request->input('device_name', 'api-token');

        // Definir abilities según el rol del usuario
        $abilities = $this->resolveAbilities($user);

        // Crear token Sanctum
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
        // Revocar solo el token usado en esta request
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

    /**
     * Asigna abilities al token según el rol.
     * Puedes expandir esto según tus necesidades.
     */
    private function resolveAbilities(User $user): array
    {
        return match (true) {
            $user->isSuperAdmin() => ['*'],          // acceso total
            $user->isAdmin() => ['*'],          // acceso total
            $user->isStaff() => [               // acceso limitado
                'projects:read',
                // 'projects:write',
                'tasks:read',
                // 'tasks:write',
                'milestones:read',
                // 'milestones:write',
            ],
            default => ['projects:read', 'tasks:read'],
        };
    }
}
