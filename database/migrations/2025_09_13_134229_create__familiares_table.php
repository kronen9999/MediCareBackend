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
        Schema::create('familiares', function (Blueprint $table) {
            $table->bigInteger('IdFamiliar')->autoIncrement()->primary();
            $table->string('Nombre', 100)->nullable();
            $table->string('ApellidoP', 100)->nullable();
            $table->string('ApellidoM', 100)->nullable();
            $table->string('CorreoE',250)->nullable(false);
            $table->string('Usuario',50)->nullable();
            $table->string('Contrasena',250)->nullable(false);
            $table->string('TokenAcceso',50)->nullable(false);
            $table->tinyInteger('UsuarioVerificado')->default(0);
            $table->string('CodigoVerificacion',10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('familiares');
    }
};
