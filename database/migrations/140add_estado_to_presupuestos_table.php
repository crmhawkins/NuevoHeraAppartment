<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('presupuestos')) return; // [2026-04-30] idempotente
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('estado')->after('total')->default('pendiente');
        });
    }

    public function down()
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
