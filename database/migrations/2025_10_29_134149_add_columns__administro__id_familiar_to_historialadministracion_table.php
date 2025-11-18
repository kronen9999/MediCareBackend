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
        Schema::table('historialadministracion', function (Blueprint $table) {
            $table->string("Administro")->nullable()->after("Estado");
            $table->bigInteger("IdFamiliar")->nullable(false)->after("Administro");
            $table->foreign("IdFamiliar")->references("IdFamiliar")->on("familiares")->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historialadministracion', function (Blueprint $table) {
            //
        });
    }
};
