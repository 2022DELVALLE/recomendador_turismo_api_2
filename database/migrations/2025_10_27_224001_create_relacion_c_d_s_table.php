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

        Schema::create('relacion_contexto_destino', function (Blueprint $table) {

            // Claves Foráneas (FK) que componen la Clave Primaria
            $table->unsignedBigInteger('id_contexto'); // FK -> Contexto.id_contexto
            $table->unsignedBigInteger('id_destino'); // FK -> Destino.id_destino

            // Atributos de la Arista
            $table->string('impacto_clima', 50)->nullable(); // Ej: 'Óptimo', 'Negativo', 'Irrelevante'
            $table->float('peso_contexto')->default(1.0); // Valor numérico para el GNN
            $table->boolean('es_accesible'); // Si el contexto hace que el destino sea accesible (e.g., buen estado de vías)

            $table->timestamps();

            // Definición de Clave Primaria Compuesta (Evita duplicados)
            $table->primary(['id_contexto', 'id_destino']);

            // Definición de Claves Foráneas
            $table->foreign('id_contexto')->references('id_contexto')->on('contextos')->onDelete('cascade');
            $table->foreign('id_destino')->references('id_destino')->on('destinos')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relacion_c_d_s');
    }
};
