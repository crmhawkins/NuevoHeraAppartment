<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GrupoContable;
use App\Models\SubGrupoContable;
use App\Models\CuentasContable;
use App\Models\SubCuentaContable;
use App\Models\SubCuentaHijo;

class PlanContableSeeder extends Seeder
{
    public function run()
    {
        $path = storage_path('app/public/plan_contable.csv');
        $file = fopen($path, 'r');
        
        // Omitir la cabecera del archivo CSV
        fgetcsv($file);

        while (($record = fgetcsv($file)) !== false) {
            // Asumiendo que 'Cuenta' es la primera columna y 'Descripcion' la segunda
            $numero = $record[0];
            $descripcion = $record[1];

            if (strlen($numero) == 1) {
                GrupoContable::create([
                    'numero' => $numero,
                    'nombre' => $descripcion,
                    'descripcion' => $descripcion
                ]);
            } elseif (strlen($numero) == 2) {
                $grupo_id = GrupoContable::where('numero', substr($numero, 0, 1))->first()->id;
                SubGrupoContable::create([
                    'grupo_id' => $grupo_id,
                    'numero' => $numero,
                    'nombre' => $descripcion,
                    'descripcion' => $descripcion
                ]);
            } elseif (strlen($numero) == 3) {
                $sub_grupo_id = SubGrupoContable::where('numero', substr($numero, 0, 2))->first()->id;
                CuentasContable::create([
                    'sub_grupo_id' => $sub_grupo_id,
                    'numero' => $numero,
                    'nombre' => $descripcion,
                    'descripcion' => $descripcion
                ]);
            } elseif (strlen($numero) == 4) {
                $cuenta_id = CuentasContable::where('numero', substr($numero, 0, 3))->first()->id;
                SubCuentaContable::create([
                    'cuenta_id' => $cuenta_id,
                    'numero' => $numero,
                    'nombre' => $descripcion,
                    'descripcion' => $descripcion
                ]);
            } elseif (strlen($numero) == 5) {
                $sub_cuenta_id = SubCuentaContable::where('numero', substr($numero, 0, 4))->first()->id;
                SubCuentaHijo::create([
                    'sub_cuenta_id' => $sub_cuenta_id,
                    'numero' => $numero,
                    'nombre' => $descripcion,
                    'descripcion' => $descripcion
                ]);
            }
        }

        fclose($file);
    }
}
