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
        Schema::dropIfExists('users');

        Schema::create('usuarios', function (Blueprint $table) {
            // Clave Primaria - id_usuario BIGINT PK (Autoincremental)
            // CAMBIO: Usamos id() en lugar de integer()->primary()
            $table->id('id_usuario');

            // Atributos
            $table->string('nombre', 100);
            $table->integer('edad')->nullable();
            $table->string('genero', 10)->nullable();
            $table->string('origen_geografico', 50)->nullable();
            $table->string('tipo_turista', 50)->nullable();
            $table->float('nivel_gasto')->nullable();
            $table->text('preferencias_texto')->nullable();
            $table->string('dispositivo_acceso', 30)->nullable();

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
