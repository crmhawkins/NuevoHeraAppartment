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
            $table->boolean('consentimiento_finalizacion')->default(false)->after('observacion');
            $table->text('motivo_consentimiento')->nullable()->after('consentimiento_finalizacion');
            $table->timestamp('fecha_consentimiento')->nullable()->after('motivo_consentimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_limpieza', function (Blueprint $table) {
            $table->dropColumn(['consentimiento_finalizacion', 'motivo_consentimiento', 'fecha_consentimiento']);
        });
    }
};
