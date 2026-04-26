<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * [2026-04-26] Separa el campo `codigo_acceso` de las reservas en dos
 * campos semanticamente distintos:
 *
 *  - codigo_portal     : PIN del portal del edificio (lo que antes vivia
 *                        en `codigo_acceso` mezclado con la clave del piso)
 *  - codigo_apartamento: clave fija del apartamento (copia de
 *                        `apartamentos.claves` en el momento de la reserva)
 *
 * Razon: el bug de los 28 huespedes (abril 2026) ocurrio porque cuando el
 * fallback de Tuya se activaba, sobrescribiamos `codigo_acceso` con el
 * `codigo_emergencia_portal` del edificio (`0001981`). La vista lo mostraba
 * como "clave del apartamento" y los huespedes intentaban abrir su puerta
 * con un codigo que solo abre el portal -> caos. La causa raiz era usar
 * un solo campo para dos conceptos distintos.
 *
 * Tras esta migracion:
 *  - codigo_acceso queda como LEGACY (no escribir mas; lectores antiguos
 *    siguen viendo el dato del portal igual que antes).
 *  - codigo_portal y codigo_apartamento son los nuevos canonicos.
 *
 * Backfill: solo poblamos `codigo_apartamento` con la clave del apartamento
 * para reservas activas (futuras o vigentes). El `codigo_portal` se rellena
 * con el `codigo_acceso` actual cuando ese codigo NO coincide con el
 * `codigo_emergencia_portal` del edificio (= no esta contaminado).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('codigo_portal', 20)->nullable()->after('codigo_acceso');
            $table->string('codigo_apartamento', 50)->nullable()->after('codigo_portal');
        });

        // Backfill: clave del apartamento para reservas activas
        // (fecha_salida >= hoy y no canceladas)
        DB::statement(<<<SQL
            UPDATE reservas r
            INNER JOIN apartamentos a ON a.id = r.apartamento_id
            SET r.codigo_apartamento = a.claves
            WHERE r.fecha_salida >= CURDATE()
              AND r.estado_id NOT IN (4, 9)
              AND a.claves IS NOT NULL
              AND a.claves <> ''
              AND r.codigo_apartamento IS NULL
        SQL);

        // Backfill codigo_portal desde codigo_acceso, solo si NO coincide con
        // el codigo_emergencia_portal del edificio (esos casos los dejamos
        // sin valor porque eran datos contaminados por el bug).
        DB::statement(<<<SQL
            UPDATE reservas r
            INNER JOIN apartamentos a ON a.id = r.apartamento_id
            INNER JOIN edificios e ON e.id = a.edificio_id
            SET r.codigo_portal = r.codigo_acceso
            WHERE r.fecha_salida >= CURDATE()
              AND r.estado_id NOT IN (4, 9)
              AND r.codigo_acceso IS NOT NULL
              AND r.codigo_acceso <> ''
              AND (e.codigo_emergencia_portal IS NULL
                   OR r.codigo_acceso <> e.codigo_emergencia_portal)
              AND r.codigo_portal IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['codigo_portal', 'codigo_apartamento']);
        });
    }
};
