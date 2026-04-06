<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_cuentas_contable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuenta_id')->nullable();
            $table->string('numero', 255);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_cuentas_contable');
    }
};
