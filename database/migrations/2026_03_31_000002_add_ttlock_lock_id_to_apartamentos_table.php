<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('ttlock_lock_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('apartamentos', function (Blueprint $table) {
            $table->dropColumn('ttlock_lock_id');
        });
    }
};
