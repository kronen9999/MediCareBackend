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
        Schema::create('informacioncontactocuidador', function (Blueprint $table) {
            $table->bigInteger("IdCuidador")->primary()->unique()->nullable(false);
            $table->foreign('IdCuidador')->references('IdCuidador')->on('cuidadores')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('informacioncontactocuidador');
    }
};
