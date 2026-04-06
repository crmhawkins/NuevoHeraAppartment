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
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->string('currency')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->string('property_type')->nullable();
            $table->text('description')->nullable();
            $table->text('important_information')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'country',
                'state',
                'city',
                'address',
                'zip_code',
                'latitude',
                'longitude',
                'timezone',
                'property_type',
                'description',
                'important_information',
            ]);
        });
    }
};
