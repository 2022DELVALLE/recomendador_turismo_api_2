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
        Schema::create('transporte_disponible', function (Blueprint $table) {
            $table->id('id_transporte');
            $table->string('tipo_transporte', 50)->unique()->comment('Ej: Taxi, Bus Urbano, Colectivo, Mototaxi');
            $table->decimal('costo_base_minimo', 8, 2)->default(0.00)->comment('Costo mínimo de referencia');
            $table->string('horario_disponibilidad', 100)->nullable()->comment('Ej: 24/7 o 6:00 - 22:00');
            $table->boolean('activo')->default(true)->comment('Indica si el servicio está operativo actualmente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transporte_disponibles');
    }
};
