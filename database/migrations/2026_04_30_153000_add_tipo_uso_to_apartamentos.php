<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * [2026-04-30] Distingue apartamentos comerciables de zonas comunes y tests.
 *
 * El dev anterior dio de alta zonas comunes (escalera, oficina, lavanderia)
 * como apartamentos para que las limpiadoras pudiesen asignarlas. Eso hace
 * que aparezcan en listados, en Revenue, en selectores de reservas... lo
 * cual es incorrecto.
 *
 * Solución mínima: 1 columna `tipo_uso` con tres valores:
 *   - 'apartamento' (default): se reserva por Channex, entra en Revenue, aparece en listados.
 *   - 'zona_comun':  area común que limpieza necesita ver pero NO es comerciable.
 *   - 'test':        registros de prueba del dev anterior que no son ninguna de las dos.
 *
 * El backfill aplica 'zona_comun' a IDs 16-20 y 'test' a 22-23 segun lo
 * confirmado con el cliente. Si el ID no existe se ignora (idempotente).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('apartamentos')) return;

        if (!Schema::hasColumn('apartamentos', 'tipo_uso')) {
            Schema::table('apartamentos', function (Blueprint $table) {
                $table->enum('tipo_uso', ['apartamento', 'zona_comun', 'test'])
                    ->default('apartamento')
                    ->after('nombre')
                    ->comment('apartamento = comerciable; zona_comun = area común; test = pruebas dev anterior');
            });
        }

        // Backfill: zonas comunes (16-20) y tests (22-23). UPDATE idempotente.
        DB::table('apartamentos')->whereIn('id', [16, 17, 18, 19, 20])->update(['tipo_uso' => 'zona_comun']);
        DB::table('apartamentos')->whereIn('id', [22, 23])->update(['tipo_uso' => 'test']);
    }

    public function down(): void
    {
        if (Schema::hasTable('apartamentos') && Schema::hasColumn('apartamentos', 'tipo_uso')) {
            Schema::table('apartamentos', function (Blueprint $table) {
                $table->dropColumn('tipo_uso');
            });
        }
    }
};
