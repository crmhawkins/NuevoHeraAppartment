<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-19] Sistema de fallback para cerraduras digitales.
 *
 * Cuando la cerradura de un edificio (Tuya o TTLock) deja de aceptar
 * nuevos codigos, activamos un "modo fallback" por-edificio y por-proveedor
 * que hace que las reservas reciban un codigo de emergencia fijo en lugar
 * del PIN unico habitual.
 *
 * - modo_fallback_tuya_activo / _ttlock_activo: flags independientes
 * - fallos_consecutivos_*: contador interno, se resetea al primer exito
 * - codigo_emergencia_portal: el PIN estatico que se usa en modo fallback
 *   (ej. 0001981 para Hawkins Suites). Debe ser de 7 digitos para pasar
 *   la validacion Tuya X7 aunque el sistema este caido — si la cerradura
 *   vuelve a responder, el PIN siguee funcionando.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('edificios', function (Blueprint $t) {
            $t->string('codigo_emergencia_portal', 10)->nullable()->after('clave');

            $t->boolean('fallback_tuya_activo')->default(false)->after('codigo_emergencia_portal');
            $t->timestamp('fallback_tuya_activado_at')->nullable()->after('fallback_tuya_activo');
            $t->unsignedSmallInteger('fallos_consecutivos_tuya')->default(0)->after('fallback_tuya_activado_at');

            $t->boolean('fallback_ttlock_activo')->default(false)->after('fallos_consecutivos_tuya');
            $t->timestamp('fallback_ttlock_activado_at')->nullable()->after('fallback_ttlock_activo');
            $t->unsignedSmallInteger('fallos_consecutivos_ttlock')->default(0)->after('fallback_ttlock_activado_at');
        });
    }

    public function down(): void
    {
        Schema::table('edificios', function (Blueprint $t) {
            $t->dropColumn([
                'codigo_emergencia_portal',
                'fallback_tuya_activo', 'fallback_tuya_activado_at', 'fallos_consecutivos_tuya',
                'fallback_ttlock_activo', 'fallback_ttlock_activado_at', 'fallos_consecutivos_ttlock',
            ]);
        });
    }
};
