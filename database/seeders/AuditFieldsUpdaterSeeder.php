<?php

namespace Database\Seeders;

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
        $masterUserId = 1;

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
