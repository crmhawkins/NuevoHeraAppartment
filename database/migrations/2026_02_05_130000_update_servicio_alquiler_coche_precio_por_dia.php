<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Precio Alquiler de coche: 115 € por día.
     */
    public function up(): void
    {
        DB::table('servicios')
            ->where('slug', 'alquiler-de-coche')
            ->update(['precio' => 115.00, 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('servicios')
            ->where('slug', 'alquiler-de-coche')
            ->update(['precio' => 45.00, 'updated_at' => now()]);
    }
};
