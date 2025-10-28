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

        Schema::create('interaccion_usuario_destino', function (Blueprint $table) {
            // Clave Primaria - id_interaccion BIGINT PK (Autoincremental)
            $table->id('id_interaccion');

            // Claves Foráneas (FK)
            $table->unsignedBigInteger('id_usuario'); // FK -> Usuario.id_usuario
            $table->unsignedBigInteger('id_destino'); // FK -> Destino.id_destino

            // Atributos
            $table->float('rating'); // 1.0 a 5.0
            $table->date('fecha_visita');
            $table->float('duracion_visita')->nullable(); // Horas
            $table->float('gasto_estimado')->nullable();
            $table->text('comentario')->nullable();
            $table->float('sentimiento')->nullable(); // Puntuación de sentimiento (ej: -1.0 a 1.0)
            $table->string('medio_transporte', 30)->nullable();

            $table->timestamps();

            // Definición de Claves Foráneas
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('id_destino')->references('id_destino')->on('destinos')->onDelete('cascade');

            // Índice para evitar duplicados y acelerar consultas U-P
            $table->unique(['id_usuario', 'id_destino']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaccion_u_d_s');
    }
};
