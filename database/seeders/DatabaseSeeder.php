<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(5)->create();
        Schema::disableForeignKeyConstraints();
        $this->call([
            DominiosSeeder::class,
            EventoSeeder::class,
            PessoaSeeder::class,
            FichaSeeder::class,
            EnvolvidoSeeder::class,
            ContatoSeeder::class,
            ProdutoSeeder::class,
        ]);
        Schema::enableForeignKeyConstraints();
    }
}
