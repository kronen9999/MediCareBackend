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
        Schema::create('historialadministracion', function (Blueprint $table) {
            $table->bigInteger('idHistorial')->autoIncrement()->primary();
            $table->dateTime("FechaProgramada");
            $table->dateTime("HoraAdministracion")->nullable();
            $table->enum("Estado", ['Administrado','No Administrado'])->default('No Administrado');
            $table->bigInteger("IdFamiliar");
            $table->foreign('IdFamiliar')->references('idFamiliar')->on('familiares')->onDelete('cascade');
            $table->bigInteger("IdMedicamento");
            $table->foreign('IdMedicamento')->references('IdMedicamento')->on('medicamentos')->onDelete('cascade');
            $table->bigInteger("IdPaciente");
            $table->foreign('IdPaciente')->references('IdPaciente')->on('pacientes')->onDelete('cascade');
            $table->bigInteger("IdCuidador");
            $table->foreign('IdCuidador')->references('IdCuidador')->on('cuidadores')->onDelete('cascade');
            $table->bigInteger("IdHorario");
            $table->foreign('IdHorario')->references('IdHorario')->on('horariosmedicamentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historialadministracion');
    }
};
