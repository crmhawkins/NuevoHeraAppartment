<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bankinter_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 64)->unique();
            $table->string('label', 255)->nullable();
            $table->string('user', 255);
            $table->text('password')->comment('Cifrada con cast encrypted (APP_KEY)');
            $table->string('iban', 34)->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('bank_id')->references('id')->on('bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bankinter_credentials');
    }
};
