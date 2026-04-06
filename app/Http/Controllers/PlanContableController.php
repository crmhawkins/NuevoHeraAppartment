<?php

// App\Http\Controllers\PlanContableController.php
namespace App\Http\Controllers;

use App\Models\GrupoContable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PlanContableController extends Controller
{
    public function index()
    {
        $grupos = GrupoContable::with('subGrupos.cuentas.subCuentas.cuentasHijas')->orderBy('numero', 'asc')->get();
        return view('admin.contabilidad.planContable.index', compact('grupos'));
    }
    // Nueva función para generar el JSON con la misma estructura jerárquica
    public function json()
    {
        $grupos = GrupoContable::with('subGrupos.cuentas.subCuentas.cuentasHijas')
            ->orderBy('numero', 'asc')
            ->get();

        // Mapeamos la estructura para convertirla en un array adecuado para JSON
        $planContable = $grupos->map(function ($grupo) {
            return [
                'numero' => $grupo->numero,
                'nombre' => $grupo->nombre,
                'nivel' => 'Grupo',
                'subGrupos' => $grupo->subGrupos->map(function ($subGrupo) {
                    return [
                        'numero' => $subGrupo->numero,
                        'nombre' => $subGrupo->nombre,
                        'nivel' => 'SubGrupo',
                        'cuentas' => $subGrupo->cuentas->map(function ($cuenta) {
                            return [
                                'numero' => $cuenta->numero,
                                'nombre' => $cuenta->nombre,
                                'nivel' => 'Cuenta',
                                'subCuentas' => $cuenta->subCuentas->map(function ($subCuenta) {
                                    return [
                                        'numero' => $subCuenta->numero,
                                        'nombre' => $subCuenta->nombre,
                                        'nivel' => 'SubCuenta',
                                        'cuentasHijas' => $subCuenta->cuentasHijas->map(function ($cuentaHija) {
                                            return [
                                                'numero' => $cuentaHija->numero,
                                                'nombre' => $cuentaHija->nombre,
                                                'nivel' => 'SubCuenta Hija',
                                            ];
                                        })
                                    ];
                                })
                            ];
                        })
                    ];
                })
            ];
        });
        // Convertimos el array en JSON
        $jsonContent = $planContable->toJson(JSON_PRETTY_PRINT);

        // Definimos el nombre del archivo
        $fileName = 'plan_contable.json';

        // Forzamos la descarga del archivo
        return Response::make($jsonContent, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
        // Devolvemos la respuesta en formato JSON
        return response()->json($planContable);
    }
}