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
            // Agregar columnas si no existen
            if (!Schema::hasColumn('incidencias', 'titulo')) {
                $table->string('titulo')->after('id');
            }
            if (!Schema::hasColumn('incidencias', 'descripcion')) {
                $table->text('descripcion')->after('titulo');
            }
            if (!Schema::hasColumn('incidencias', 'tipo')) {
                $table->enum('tipo', ['apartamento', 'zona_comun'])->after('descripcion');
            }
            if (!Schema::hasColumn('incidencias', 'apartamento_id')) {
                $table->unsignedBigInteger('apartamento_id')->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('incidencias', 'zona_comun_id')) {
                $table->unsignedBigInteger('zona_comun_id')->nullable()->after('apartamento_id');
            }
            if (!Schema::hasColumn('incidencias', 'empleada_id')) {
                $table->unsignedBigInteger('empleada_id')->after('zona_comun_id');
            }
            if (!Schema::hasColumn('incidencias', 'apartamento_limpieza_id')) {
                $table->unsignedBigInteger('apartamento_limpieza_id')->nullable()->after('empleada_id');
            }
            if (!Schema::hasColumn('incidencias', 'prioridad')) {
                $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media')->after('apartamento_limpieza_id');
            }
            if (!Schema::hasColumn('incidencias', 'estado')) {
                $table->enum('estado', ['pendiente', 'en_proceso', 'resuelta', 'cerrada'])->default('pendiente')->after('prioridad');
            }
            if (!Schema::hasColumn('incidencias', 'fotos')) {
                $table->json('fotos')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('incidencias', 'solucion')) {
                $table->text('solucion')->nullable()->after('fotos');
            }
            if (!Schema::hasColumn('incidencias', 'admin_resuelve_id')) {
                $table->unsignedBigInteger('admin_resuelve_id')->nullable()->after('solucion');
            }
            if (!Schema::hasColumn('incidencias', 'fecha_resolucion')) {
                $table->timestamp('fecha_resolucion')->nullable()->after('admin_resuelve_id');
            }
            if (!Schema::hasColumn('incidencias', 'observaciones_admin')) {
                $table->text('observaciones_admin')->nullable()->after('fecha_resolucion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidencias', function (Blueprint $table) {
            // Remover columnas agregadas
            $table->dropColumn([
                'titulo', 'descripcion', 'tipo', 'apartamento_id', 'zona_comun_id',
                'empleada_id', 'apartamento_limpieza_id', 'prioridad', 'estado',
                'fotos', 'solucion', 'admin_resuelve_id', 'fecha_resolucion', 'observaciones_admin'
            ]);
        });
    }
};
