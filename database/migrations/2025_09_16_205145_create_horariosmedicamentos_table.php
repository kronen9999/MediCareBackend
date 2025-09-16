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
        Schema::create('horariosmedicamentos', function (Blueprint $table) {
            $table->bigInteger("IdHorario")->primary()->autoIncrement();
            $table->dateTime("HoraPrimeraDosis")->nullable(false);
            $table->integer("IntervaloHoras")->nullable(false);
            $table->integer("Dosis")->nullable(false);
            $table->string("UnidaDosis")->nullable(false);
            $table->text("Notas")->nullable();
            $table->bigInteger("IdMedicamento");
            $table->foreign("IdMedicamento")->references("IdMedicamento")->on("medicamentos")->onDelete("cascade");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horariosmedicamentos');
    }
};
