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
        Schema::table('apartamento_photos', function (Blueprint $table) {
            $table->string('path')->nullable()->after('url')->comment('Ruta en storage');
            $table->boolean('is_primary')->default(false)->after('position')->comment('Foto principal');
            $table->index('is_primary');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartamento_photos', function (Blueprint $table) {
            $table->dropIndex(['is_primary']);
            $table->dropIndex(['position']);
            $table->dropColumn(['path', 'is_primary']);
        });
    }
};
