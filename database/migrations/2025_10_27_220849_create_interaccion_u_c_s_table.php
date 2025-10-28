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

        Schema::create('interaccion_usuario_contexto', function (Blueprint $table) {
            // Clave Primaria - id_uc BIGINT PK (Autoincremental)
            $table->id('id_uc');

            // Claves Foráneas (FK)
            $table->unsignedBigInteger('id_usuario'); // FK -> Usuario.id_usuario
            $table->unsignedBigInteger('id_contexto'); // FK -> Contexto.id_contexto

            // Atributos
            $table->string('clima_visita', 30)->nullable();
            $table->string('transporte_usado', 30)->nullable();
            $table->integer('seguridad_percibida')->nullable(); // Índice 1-10
            $table->boolean('servicios_utilizados'); // TRUE/FALSE

            $table->timestamps();

            // Definición de Claves Foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_contexto')->references('id_contexto')->on('contextos')->onDelete('cascade');

            // Índice para evitar duplicados, aunque podría haber múltiples interacciones en el mismo contexto
            // Lo dejamos como único para simplificar la primera implementación
            $table->unique(['id_usuario', 'id_contexto']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaccion_u_c_s');
    }
};
