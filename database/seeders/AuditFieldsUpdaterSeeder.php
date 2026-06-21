<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Company;
use App\Models\ProjectRole;
use App\Models\ProjectStatus;
use App\Models\Role;
use App\Models\TaskRole;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditFieldsUpdaterSeeder extends Seeder
{
    public function run(): void
    {
        // Buscamos al primer Super Admin real que exista en la base de datos
        $masterUser = User::where('role_id', RoleEnum::SUPER_ADMIN->value)->first();

        // Si por alguna razón rarísima en el test no hay ninguno, usamos el primero que haya
        if (! $masterUser) {
            $masterUser = User::first();
        }

        // Si la tabla está absolutamente vacía, creamos uno de emergencia
        if (! $masterUser) {
            $masterUser = User::factory()->create([
                'role_id' => RoleEnum::SUPER_ADMIN->value,
            ]);
        }

        $masterUserId = $masterUser->id;

        User::where('id', '!=', $masterUserId)->update([
            'created_by' => $masterUserId,
            'updated_by' => $masterUserId,
        ]);

        Role::query()->update([
            'created_by' => $masterUserId,
        ]);

        Company::query()->update([
            'created_by' => $masterUserId,
            'updated_by' => $masterUserId,
        ]);

        ProjectStatus::query()->update([
            'created_by' => $masterUserId,
        ]);

        TaskStatus::query()->update([
            'created_by' => $masterUserId,
        ]);

        ProjectRole::query()->update([
            'created_by' => $masterUserId,
        ]);

        TaskRole::query()->update([
            'created_by' => $masterUserId,
        ]);
    }
}
