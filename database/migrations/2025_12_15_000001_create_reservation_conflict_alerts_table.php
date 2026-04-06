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
        Schema::create('reservation_conflict_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('apartamento_id');
            $table->string('conflict_key')->unique();
            $table->json('reserva_ids');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('apartamento_id')
                ->references('id')
                ->on('apartamentos')
                ->cascadeOnDelete();

            $table->index('apartamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_conflict_alerts');
    }
};


