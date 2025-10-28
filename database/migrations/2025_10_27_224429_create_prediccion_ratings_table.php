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

        Schema::create('prediccion_rating', function (Blueprint $table) {
            // Clave Primaria - id_prediccion BIGINT PK (Autoincremental)
            $table->id('id_prediccion');

            // Claves Foráneas (FK) - La predicción es siempre para un Usuario y un Destino
            $table->unsignedBigInteger('id_usuario'); // FK -> Usuario.id_usuario
            $table->unsignedBigInteger('id_destino'); // FK -> Destino.id_destino

            // Atributos de Predicción
            $table->float('rating_predicho'); // Valor predicho (ej: 4.75)
            $table->float('confianza')->nullable(); // Nivel de confianza o desviación estándar de la predicción
            $table->string('modelo_usado', 50); // Ej: 'GCN', 'GraphSAGE', 'LightGCN'
            $table->timestamp('fecha_prediccion');
            $table->text('factores_contextuales')->nullable(); // JSON o texto de los factores de contexto usados (C)

            $table->timestamps();

            // Definición de Claves Foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_destino')->references('id_destino')->on('destinos')->onDelete('cascade');

            // Índice para asegurar que solo haya una predicción activa por par Usuario-Destino
            $table->unique(['id_usuario', 'id_destino']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediccion_ratings');
    }
};
