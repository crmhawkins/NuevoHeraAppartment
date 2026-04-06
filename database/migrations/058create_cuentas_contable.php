<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cuentas_contable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_grupo_id')->nullable();
            $table->string('numero', 254);
            $table->string('nombre', 254);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cuentas_contable');
    }
};
