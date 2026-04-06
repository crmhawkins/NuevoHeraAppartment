<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('property_id')->constrained('apartamentos')->onDelete('cascade');
            $table->integer('count_of_rooms');
            $table->integer('occ_adults');
            $table->integer('occ_children')->default(0);
            $table->integer('occ_infants')->default(0);
            $table->integer('default_occupancy');
            $table->text('facilities')->nullable();
            $table->string('room_kind')->default('room');
            $table->integer('capacity')->nullable();
            $table->text('description')->nullable();
            $table->json('photos')->nullable();
            $table->string('id_channex')->nullable(); // ID generado en Channex
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_types');
    }
};
