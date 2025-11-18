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
            $table->string("NombreM")->nullable(false);
            $table->string("NombreP")->nullable(false);
            $table->string("Dosis")->nullable(false);
            $table->string("UnidadDosis")->nullable(false);
            $table->string("Notas")->nullable();
            $table->enum("Estado", ['Administrado','No Administrado','Cancelado'])->default('No Administrado');
            $table->bigInteger("IdCuidador")->nullable();
            $table->foreign('IdCuidador')->references('IdCuidador')->on('cuidadores')->onDelete('cascade');
            $table->bigInteger("IdHorario")->nullable();
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
