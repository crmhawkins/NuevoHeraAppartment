<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->boolean('mir_enviado')->default(false)->after('enviado_webpol')->comment('Indica si la reserva ha sido enviada a MIR');
            $table->string('mir_estado', 50)->nullable()->after('mir_enviado')->comment('Estado del envío a MIR (enviado, error, pendiente)');
            $table->text('mir_respuesta')->nullable()->after('mir_estado')->comment('Respuesta completa de la API MIR');
            $table->timestamp('mir_fecha_envio')->nullable()->after('mir_respuesta')->comment('Fecha y hora del envío a MIR');
            $table->string('mir_codigo_referencia', 100)->nullable()->after('mir_fecha_envio')->comment('Código de referencia devuelto por MIR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn([
                'mir_enviado',
                'mir_estado',
                'mir_respuesta',
                'mir_fecha_envio',
                'mir_codigo_referencia'
            ]);
        });
    }
};
