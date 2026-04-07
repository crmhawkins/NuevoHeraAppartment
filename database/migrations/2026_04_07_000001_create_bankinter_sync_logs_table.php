<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bankinter_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('cuenta_alias', 50);
            $table->timestamp('fecha_sync');
            $table->integer('total_filas')->default(0);
            $table->integer('procesados')->default(0);
            $table->integer('duplicados')->default(0);
            $table->integer('errores')->default(0);
            $table->integer('ingresos_creados')->default(0);
            $table->integer('gastos_creados')->default(0);
            $table->string('archivo')->nullable();
            $table->enum('status', ['success', 'error', 'running'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('cuenta_alias');
            $table->index('fecha_sync');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bankinter_sync_logs');
    }
};
