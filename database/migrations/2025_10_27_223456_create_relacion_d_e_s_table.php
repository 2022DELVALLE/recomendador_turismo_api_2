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

        Schema::create('relacion_destino_evento', function (Blueprint $table) {

            // Claves Foráneas (FK) que componen la Clave Primaria
            $table->unsignedBigInteger('id_destino'); // FK -> Destino.id_destino
            $table->unsignedBigInteger('id_evento'); // FK -> Evento_Festividad.id_evento

            // Atributos de la Arista
            $table->string('tipo_vinculo', 50); // Ej: 'Ubicación Principal', 'Relacionado Cercano', 'Transporte Directo'
            $table->float('impacto_turistico')->nullable(); // Ej: 0.0 a 1.0 (cuánto afecta el evento al destino)

            $table->timestamps();

            // Definición de Clave Primaria Compuesta (Evita duplicados)
            $table->primary(['id_destino', 'id_evento']);

            // Definición de Claves Foráneas
            $table->foreign('id_destino')->references('id_destino')->on('destinos')->onDelete('cascade');
            $table->foreign('id_evento')->references('id_evento')->on('evento_festividades')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relacion_d_e_s');
    }
};
