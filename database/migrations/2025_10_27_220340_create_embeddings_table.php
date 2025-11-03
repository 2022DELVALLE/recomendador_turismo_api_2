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

        Schema::create('embeddings', function (Blueprint $table) {
            // Clave Primaria - id_embedding BIGINT PK (Autoincremental)
            $table->id('id_embedding');

            // Atributos de Referencia
            $table->string('tipo_nodo', 20); // U, P, C, E
            $table->unsignedBigInteger('id_referencia'); // ID del nodo referenciado

            // Atributos del Vector
            $table->text('vector_embedding'); // Almacena el vector como un string/JSON (serializado)
            $table->date('fecha_generacion');

            $table->timestamps();

            // Índice para búsquedas rápidas por nodo (IMPORTANTE: Se cambió a index simple)
            // Esto permite múltiples entradas para el mismo usuario (U₀, U₁, U₂, etc.)
            $table->index(['tipo_nodo', 'id_referencia']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
