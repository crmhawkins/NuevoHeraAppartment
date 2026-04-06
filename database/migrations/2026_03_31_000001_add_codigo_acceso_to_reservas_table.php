<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('codigo_acceso', 6)->nullable()->after('token');
            $table->string('ttlock_pin_id')->nullable()->after('codigo_acceso');
            $table->tinyInteger('codigo_enviado_cerradura')->default(0)->after('ttlock_pin_id');
            $table->tinyInteger('codigo_acceso_enviado')->default(0)->after('codigo_enviado_cerradura');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['codigo_acceso', 'ttlock_pin_id', 'codigo_enviado_cerradura', 'codigo_acceso_enviado']);
        });
    }
};
