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
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->unsignedBigInteger('zona_comun_id')->nullable()->after('apartamento_id');
            $table->string('tipo_limpieza')->default('apartamento')->after('zona_comun_id'); // apartamento, zona_comun
            
            $table->foreign('zona_comun_id')->references('id')->on('zona_comuns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropForeign(['zona_comun_id']);
            $table->dropColumn(['zona_comun_id', 'tipo_limpieza']);
        });
    }
};
