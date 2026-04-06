<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
        });
    }

    public function down()
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn('website');
            $table->dropColumn('email');
            $table->dropColumn('phone');
        });
    }
};

