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

        Schema::create('reseña_texto', function (Blueprint $table) {
            // Clave Primaria - id_reseña BIGINT PK (Autoincremental)
            $table->id('id_reseña');

            // Clave Foránea (FK) 1:1 con la interacción U-D
            $table->unsignedBigInteger('id_interaccion')->unique(); // FK -> Interaccion_Usuario_Destino.id_interaccion

            // Atributos de la Reseña
            $table->text('contenido_original');
            $table->float('puntuacion_sentimiento')->nullable(); // -1.0 a 1.0 (calculado)
            $table->string('lenguaje', 10)->nullable();
            $table->json('topicos_extraidos')->nullable(); // Palabras clave o tópicos

            $table->timestamps();

            // Definición de la Clave Foránea
            // Usamos ON DELETE CASCADE: Si se elimina la Interacción, se elimina la reseña.
            $table->foreign('id_interaccion')->references('id_interaccion')->on('interaccion_usuario_destino')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseña_textos');
    }
};
