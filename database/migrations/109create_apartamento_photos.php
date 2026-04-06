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
        Schema::create('apartamento_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartamento_id')->constrained()->onDelete('cascade');
            $table->string('url');
            $table->integer('position')->nullable();
            $table->string('author')->nullable();
            $table->string('kind')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartamento_photos');
    }
};
