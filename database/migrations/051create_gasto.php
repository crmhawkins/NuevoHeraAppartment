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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->tinyInteger('is_apartamento')->nullable();
            $table->string('title')->nullable();
            $table->double('quantity',10,2)->nullable();
            $table->date('date')->nullable();
            $table->string('factura_foto')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->foreign('categoria_id')->references('id')->on('categoria_gastos');
            $table->foreign('bank_id')->references('id')->on('bank_accounts');
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
