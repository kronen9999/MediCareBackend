<?php

use Brick\Math\BigInteger;
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
        Schema::create('cuidadores', function (Blueprint $table) {
            $table->BigInteger('IdCuidador')->autoIncrement()->primary();
            $table->string('Nombre',100)->nullable(false);
            $table->string('ApellidoP',100)->nullable(false);
            $table->string('ApellidoM',100)->nullable();
            $table->string('CorreoE',100)->unique()->nullable();
            $table->string('Usuario',50)->unique()->nullable(false);
            $table->string('Contrasena',255)->nullable(false);
            $table->string('TokenAcceso',50)->unique()->nullable(false);
            $table->BigInteger('IdFamiliar');
            $table->foreign('IdFamiliar')->references('IdFamiliar')->on('familiares')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuidadores');
    }
};
