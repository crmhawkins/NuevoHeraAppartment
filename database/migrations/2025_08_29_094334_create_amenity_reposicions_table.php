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
        Schema::create('amenity_reposicions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('amenity_id');
            $table->unsignedBigInteger('user_id'); // Usuario que hace la reposición
            
            $table->integer('cantidad_reponida');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_total', 10, 2);
            
            $table->string('proveedor')->nullable();
            $table->string('numero_factura')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_reposicion');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('amenity_id')->references('id')->on('amenities')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenity_reposicions');
    }
};
