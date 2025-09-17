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
        Schema::table('cuidadores', function (Blueprint $table) {
            $table->string('CodigoVerificacion', 10)->nullable()->after('Contrasena');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuidadores', function (Blueprint $table) {
            //
        });
    }
};
