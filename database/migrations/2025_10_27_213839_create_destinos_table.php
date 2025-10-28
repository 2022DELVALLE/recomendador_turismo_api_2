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

        Schema::create('destinos', function (Blueprint $table) {
            // Clave Primaria - id_destino BIGINT PK (Autoincremental)
            // Esto reemplaza a $table->integer('id_destino')->primary()
            $table->id('id_destino');

            // Atributos
            $table->string('nombre_destino', 100);
            $table->string('categoria', 50);
            $table->string('subcategoria', 50)->nullable();
            $table->float('latitud');
            $table->float('longitud');
            $table->float('altitud')->nullable();
            $table->string('dificultad_acceso', 20)->nullable();
            $table->integer('afluencia_promedio')->nullable();
            $table->float('costo_promedio')->nullable();
            $table->float('tiempo_visita_promedio')->nullable();
            $table->text('etiquetas_tematicas')->nullable();
            $table->string('temporada_alta', 50)->nullable();

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destinos');
    }
};
