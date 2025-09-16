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
        Schema::create('informacioncontactopacientes', function (Blueprint $table) {
         $table->bigInteger("IdPaciente")->primary()->unique()->nullable(false);
            $table->foreign('IdPaciente')->references('IdPaciente')->on('pacientes')->onDelete('cascade')->onUpdate('cascade');
            $table->string('Direccion', 250)->nullable();
            $table->string('Telefono1', 10)->nullable();
            $table->string('Telefono2', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informacioncontactopacientes');
    }
};
