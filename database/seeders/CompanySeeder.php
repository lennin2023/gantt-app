<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@gantt.test'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        Company::create([
            'name' => 'Default Company',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
    }
}
