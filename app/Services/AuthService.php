<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function resolveAbilities(User $user): array
    {
        return match (true) {
            $user->isSuperAdmin() => ['*'],
            $user->isAdmin() => ['*'],
            $user->isStaff() => [
                'projects:read',
                'tasks:read',
                'milestones:read',
            ],
            default => ['projects:read', 'tasks:read'],
        };
    }

    public function attemptLogin(string $email, string $password): ?User
    {
        $user = User::with('role')->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function validateCredentials(string $email, string $password): User
    {
        $user = $this->attemptLogin($email, $password);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return $user;
    }
}
