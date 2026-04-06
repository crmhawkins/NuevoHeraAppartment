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
        Schema::table('invoices', function (Blueprint $table) {
            
            $table->string('reference')->unique()->nullable();
            $table->unsignedBigInteger('reference_autoincrement_id')->unique()->nullable();
            
            $table->foreign('reference_autoincrement_id')->references('id')->on('invoices_reference');

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
