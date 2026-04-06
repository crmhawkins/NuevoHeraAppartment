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
        //holidays_additions
        Schema::create('holidays_additions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->double('quantity_before',8,2)->nullable();
            $table->double('quantity_to_add',8,2)->nullable();
            $table->double('quantity_now',8,2)->nullable();
            $table->tinyInteger('manual')->nullable();
            $table->tinyInteger('holiday_petition')->nullable();

            $table->foreign('admin_user_id')->references('id')->on('users');

            $table->timestamps();
            $table->softDeletes();
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
