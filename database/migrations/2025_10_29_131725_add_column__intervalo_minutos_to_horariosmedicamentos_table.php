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
        Schema::table('horariosmedicamentos', function (Blueprint $table) {
            $table->integer('IntervaloMinutos')->nullable()->default(0)->after('IntervaloHoras');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horariosmedicamentos', function (Blueprint $table) {
            //
        });
    }
};
