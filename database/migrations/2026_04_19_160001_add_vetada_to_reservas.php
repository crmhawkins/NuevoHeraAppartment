<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-19] Marca en reservas para saber si caen bajo un veto activo.
 *
 * - vetada: bandera rapida (true = esta reserva esta bloqueada por veto).
 * - veto_detectado_at: cuando se detecto el match.
 *
 * Usamos columnas propias (en vez de solo consultar clientes_vetados en cada
 * sitio) porque el match se dispara al llegar el DNI/telefono del huesped en
 * el check-in publico, y a partir de ahi todo (MIR, claves, cerradura) tiene
 * que respetar el bloqueo sin volver a consultar.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $t) {
            $t->boolean('vetada')->default(false)->after('estado_id');
            $t->timestamp('veto_detectado_at')->nullable()->after('vetada');
            $t->unsignedBigInteger('veto_id')->nullable()->after('veto_detectado_at');

            $t->index('vetada');
            $t->index('veto_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $t) {
            $t->dropIndex(['vetada']);
            $t->dropIndex(['veto_id']);
            $t->dropColumn(['vetada', 'veto_detectado_at', 'veto_id']);
        });
    }
};
