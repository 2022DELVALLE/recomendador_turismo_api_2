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

        Schema::create('interaccion_usuario_evento', function (Blueprint $table) {
            // Clave Primaria - id_ue BIGINT PK (Autoincremental)
            $table->id('id_ue');

            // Claves Foráneas (FK)
            $table->unsignedBigInteger('id_usuario'); // FK -> Usuario.id_usuario
            $table->unsignedBigInteger('id_evento'); // FK -> Evento_Festividad.id_evento

            // Atributos
            $table->boolean('asistencia'); // Si asistió o no
            $table->date('fecha_participacion');
            $table->float('gasto_evento')->nullable();
            $table->float('valoracion_evento')->nullable(); // Puntuación (ej: 1.0 a 5.0)
            $table->text('comentario_evento')->nullable();
            $table->float('sentimiento_evento')->nullable(); // Puntuación de sentimiento

            $table->timestamps();

            // Definición de Claves Foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_evento')->references('id_evento')->on('evento_festividades')->onDelete('cascade');

            // Índice para evitar duplicados si un usuario solo puede participar una vez en el mismo evento
            $table->unique(['id_usuario', 'id_evento']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaccion_u_e_s');
    }
};
