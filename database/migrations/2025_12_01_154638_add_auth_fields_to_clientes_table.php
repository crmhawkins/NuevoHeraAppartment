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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email_secundario');
            $table->rememberToken()->nullable()->after('password');
            $table->timestamp('password_set_at')->nullable()->after('remember_token');
            $table->string('stripe_customer_id')->nullable()->after('password_set_at');
            $table->json('stripe_payment_methods')->nullable()->after('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'remember_token',
                'password_set_at',
                'stripe_customer_id',
                'stripe_payment_methods',
            ]);
        });
    }
};
