<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UsuarioSeeder::class);
        $this->call(DestinoSeeder::class);
        $this->call(ContextoSeeder::class);
        $this->call(EventoFestividadSeeder::class);
        $this->call(EmbeddingSeeder::class);
        $this->call(InteraccionUDSeeder::class);
        $this->call(InteraccionUCSeeder::class);
        $this->call(InteraccionUESeeder::class);
        $this->call(RelacionCDSeeder::class);
        $this->call(RelacionDESeeder::class);
        $this->call(RelacionDDSeeder::class);
        $this->call(ReseñaTextoSeeder::class);
        $this->call(PrediccionRatingSeeder::class);

    }
}
