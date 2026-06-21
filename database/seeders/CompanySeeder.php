<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['id' => 1,  'name' => 'ONNESTA',           'is_active' => true],
            ['id' => 2,  'name' => 'LA PROTECTORA',     'is_active' => true],
            ['id' => 3,  'name' => 'SABSA',             'is_active' => true],
            ['id' => 4,  'name' => 'COBERTURA MÉDICA',  'is_active' => true],
            ['id' => 5,  'name' => 'GRANDIA',           'is_active' => true],
            ['id' => 6,  'name' => 'KUBRO',             'is_active' => true],
            ['id' => 7,  'name' => 'CONSORCIO MÉDICO',  'is_active' => true],
            ['id' => 8,  'name' => 'AVIZORA',           'is_active' => true],
            ['id' => 9,  'name' => 'SEMEFA',            'is_active' => true],
            ['id' => 10, 'name' => 'PROTECTA SECURITY', 'is_active' => true],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(['id' => $company['id']], $company);
        }
    }
}
