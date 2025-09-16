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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->bigInteger('IdPaciente')->autoIncrement()->primary();
            $table->string('Nombre', 100)->nullable(false);
            $table->string('ApellidoP', 100)->nullable(false);
            $table->string('ApellidoM', 100)->nullable();
            $table->bigInteger('IdFamiliar');
            $table->foreign('IdFamiliar')->references('IdFamiliar')->on('familiares')->onDelete('cascade');
            $table->bigInteger('IdCuidador')->nullable();
            $table->foreign('IdCuidador')->references('IdCuidador')->on('cuidadores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
