<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checklist_photo_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade'); // Relación con el checklist
            $table->string('nombre'); // Nombre de la foto requerida
            $table->string('descripcion')->nullable(); // Descripción o características adicionales de la foto
            $table->integer('cantidad')->default(1); // Cantidad de fotos necesarias
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checklist_photo_requirements');
    }
};
