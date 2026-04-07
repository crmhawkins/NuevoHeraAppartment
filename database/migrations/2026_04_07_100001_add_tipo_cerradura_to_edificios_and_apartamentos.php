<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Edificios: tipo cerradura principal + ID en Tuyalaravel
        Schema::table('edificios', function (Blueprint $table) {
            $table->string('tipo_cerradura_principal', 20)->default('manual')->after('metodo_entrada');
            $table->unsignedBigInteger('tuyalaravel_building_id')->nullable()->after('tipo_cerradura_principal');
        });

        // Apartamentos: tipo cerradura + ID lock en Tuyalaravel
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->string('tipo_cerradura', 20)->default('manual')->after('ttlock_lock_id');
            $table->unsignedBigInteger('tuyalaravel_lock_id')->nullable()->after('tipo_cerradura');
        });

        // Ampliar codigo_acceso de string(6) a string(10) para PINs offline TTLock (hasta 8 dígitos)
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('codigo_acceso', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('edificios', function (Blueprint $table) {
            $table->dropColumn(['tipo_cerradura_principal', 'tuyalaravel_building_id']);
        });

        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn(['tipo_cerradura', 'tuyalaravel_lock_id']);
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->string('codigo_acceso', 6)->nullable()->change();
        });
    }
};
