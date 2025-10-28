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
        Schema::disableForeignKeyConstraints();

        Schema::create('contextos', function (Blueprint $table) {
            // Clave Primaria - id_contexto BIGINT PK (Autoincremental)
            $table->id('id_contexto');

            // Atributos
            $table->string('clima_actual', 30);
            $table->float('temperatura_promedio');
            $table->string('temporada', 30);
            $table->string('transporte', 50)->nullable();
            $table->float('densidad_turistica')->nullable();
            $table->string('estado_vias', 50)->nullable();
            $table->integer('nivel_seguridad')->nullable(); // Podría ser un índice 1-10
            $table->float('recomendacion_social')->nullable(); // Puntuación de redes
            $table->boolean('servicios_disponibles'); // TRUE/FALSE para servicios básicos

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contextos');
    }
};
