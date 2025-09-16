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
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->bigInteger("IdMedicamento")->autoIncrement()->primary();
            $table->string("NombreM", 100)->nullable(false);
            $table->string("DescripcionM", 255)->nullable();
            $table->string("Tipo Medicamento", 100)->nullable();
            $table->bigInteger("IdPaciente")->nullable(false);
            $table->foreign("IdPaciente")->references("IdPaciente")->on("pacientes")->onDelete("cascade");
            $table->tinyInteger("MedicamentoActivo")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
