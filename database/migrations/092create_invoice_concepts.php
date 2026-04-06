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
        Schema::create('invoice_concepts', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->string('title')->nullable();
            $table->text('concept')->nullable();
            $table->integer('units')->nullable();
            $table->double('base', 10,2)->nullable();
            $table->double('iva', 10, 2)->nullable();
            $table->double('descuento', 10, 2)->nullable();
            $table->double('total', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
