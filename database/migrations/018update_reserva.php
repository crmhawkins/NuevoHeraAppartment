<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->tinyInteger('codigo_acceso_entregado')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
        });
    }
};
