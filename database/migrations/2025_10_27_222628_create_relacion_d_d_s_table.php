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

        Schema::create('relacion_destino_destino', function (Blueprint $table) {

            // Claves Foráneas (FK) que componen la Clave Primaria
            $table->unsignedBigInteger('id_destino_origen'); // FK -> Destino.id_destino
            $table->unsignedBigInteger('id_destino_relacionado'); // FK -> Destino.id_destino

            // Atributos de la Arista
            $table->string('tipo_relacion', 50); // Ej: 'Proximidad', 'Misma Categoría', 'Co-visitado'
            $table->float('peso_relacion')->default(1.0); // Valor numérico para el GNN
            $table->text('descripcion')->nullable();

            $table->timestamps();

            // Definición de Clave Primaria Compuesta (Evita duplicados)
            $table->primary(['id_destino_origen', 'id_destino_relacionado']);

            // Definición de Claves Foráneas
            $table->foreign('id_destino_origen')->references('id_destino')->on('destinos')->onDelete('cascade');
            $table->foreign('id_destino_relacionado')->references('id_destino')->on('destinos')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relacion_d_d_s');
    }
};
