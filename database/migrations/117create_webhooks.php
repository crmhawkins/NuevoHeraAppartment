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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id'); // Relación con apartamento
            $table->unsignedBigInteger('webhook_category_id'); // Relación con categoría
            $table->string('event'); // Nombre del evento del webhook
            $table->string('url'); // URL del webhook
            $table->boolean('registered')->default(false); // Indica si está registrado
            $table->timestamps();

            // Llave foránea
            $table->foreign('apartamento_id')->references('id')->on('apartamentos')->onDelete('cascade');
            $table->foreign('webhook_category_id')->references('id')->on('webhook_categories')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhooks');
    }
};
