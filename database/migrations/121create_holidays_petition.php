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
        //holidays_petition
        Schema::create('holidays_petition', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->foreignId('holidays_status_id')->nullable();
            $table->date('from')->nullable();
            $table->date('to')->nullable();
            $table->double('total_days',8,2)->nullable();
            $table->tinyInteger('half_day')->nullable();

            $table->foreign('admin_user_id')->references('id')->on('users');
            $table->foreign('holidays_status_id')->references('id')->on('holidays_status');

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
