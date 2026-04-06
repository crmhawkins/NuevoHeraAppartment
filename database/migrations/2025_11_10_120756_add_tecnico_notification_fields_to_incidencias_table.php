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
        Schema::table('incidencias', function (Blueprint $table) {
            $table->timestamp('tecnico_notificado_at')->nullable()->after('fecha_resolucion');
            $table->text('tecnicos_notificados')->nullable()->after('tecnico_notificado_at')->comment('JSON array con IDs de técnicos notificados');
            $table->enum('metodo_notificacion', ['whatsapp', 'email', 'ambos'])->nullable()->after('tecnicos_notificados');
            $table->foreignId('tecnico_asignado_id')->nullable()->after('metodo_notificacion')->constrained('reparacion')->onDelete('set null');
            
            $table->index('tecnico_notificado_at');
            $table->index('tecnico_asignado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            $table->dropForeign(['tecnico_asignado_id']);
            $table->dropIndex(['tecnico_notificado_at']);
            $table->dropIndex(['tecnico_asignado_id']);
            $table->dropColumn([
                'tecnico_notificado_at',
                'tecnicos_notificados',
                'metodo_notificacion',
                'tecnico_asignado_id'
            ]);
        });
    }
};
