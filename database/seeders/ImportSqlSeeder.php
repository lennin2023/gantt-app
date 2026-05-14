<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportSqlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ruta donde se encuentran tus archivos SQL
        $path = database_path('seeders/sql/');

        // Obtener todos los archivos .sql en esa carpeta
        $files = File::files($path);

        foreach ($files as $file) {
            $sql = File::get($file->getRealPath());

            // Importar el archivo .sql
            DB::unprepared($sql);

            // Mostrar un mensaje en consola
            $this->command->info('Archivo importado: '.$file->getFilename());
        }
    }
}
