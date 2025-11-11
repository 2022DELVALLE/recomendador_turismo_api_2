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
        Schema::create('ruta_guardada', function (Blueprint $table) {
            $table->id('id_ruta_guardada'); // Crea la columna 'id_ruta_guardada' y la establece como PK
            $table->unsignedBigInteger('id_usuario');
            $table->string('nombre_ruta', 100);
            $table->json('destinos_json'); // Almacena el array de destinos
            $table->decimal('afinidad_total', 5, 2);
            $table->json('filtros_aplicados')->nullable();
            $table->dateTime('fecha_guardado')->useCurrent();

            // Definición de la clave foránea
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruta_guardada');
    }
};
