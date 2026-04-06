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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->string('apellido1')->nullable();
            $table->string('apellido2')->nullable();
            $table->string('nacionalidad')->nullable();
            $table->string('tipo_documento')->nullable();
            $table->string('tipo_documento_str')->nullable();
            $table->string('num_identificacion')->nullable();
            $table->date('fecha_expedicion_doc')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo')->nullable();
            $table->string('sexo_str')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('identificador')->nullable();
            $table->string('idiomas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
