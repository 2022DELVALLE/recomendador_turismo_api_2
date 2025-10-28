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

        Schema::create('evento_festividades', function (Blueprint $table) {
            // Clave Primaria - id_evento BIGINT PK (Autoincremental)
            $table->id('id_evento');

            // Atributos
            $table->string('nombre_evento', 100);
            $table->string('tipo_evento', 50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // Clave Foránea: lugar_asociado INT FK -> Destino.id_destino
            $table->unsignedBigInteger('lugar_asociado');

            $table->integer('afluencia_esperada')->nullable();
            $table->float('costo_entrada')->nullable();
            $table->integer('valor_cultural')->nullable(); // Índice de 1 a 10
            $table->text('palabras_clave')->nullable();

            $table->timestamps();

            // Definición de la Clave Foránea
            $table->foreign('lugar_asociado')->references('id_destino')->on('destinos')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_festividads');
    }
};
