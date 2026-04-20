<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [2026-04-19] Sistema de veto de clientes ("derecho de admision").
 *
 * Guarda un registro por cliente vetado con su num_identificacion y/o
 * telefono. La comprobacion se hace por match O (cualquiera de los dos),
 * asi que si el cliente vuelve a reservar con DNI o telefono coincidente
 * (aunque cambie email o nombre), el sistema lo detecta.
 *
 * Un veto puede levantarse (levantado_at != null) sin perder el historico
 * para auditoria.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes_vetados', function (Blueprint $t) {
            $t->id();
            $t->string('num_identificacion', 50)->nullable();
            $t->string('telefono', 30)->nullable();
            $t->unsignedBigInteger('cliente_id_original')->nullable();
            $t->text('motivo')->nullable();
            $t->unsignedBigInteger('vetado_por_user_id')->nullable();
            $t->timestamp('vetado_at')->useCurrent();
            $t->timestamp('levantado_at')->nullable();
            $t->unsignedBigInteger('levantado_por_user_id')->nullable();
            $t->text('notas_internas')->nullable();
            $t->timestamps();

            $t->index('num_identificacion');
            $t->index('telefono');
            $t->index('levantado_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_vetados');
    }
};
