<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Converte todos os usuários com papel 'visit' para 'user'
        DB::table('users')->where('role', 'visit')->update(['role' => 'user']);

        // Converte todos os usuários com papel 'espec' para 'dirig'
        DB::table('users')->where('role', 'espec')->update(['role' => 'dirig']);

        // Converte todos os usuários com papel 'sales' para 'user'
        DB::table('users')->where('role', 'sales')->update(['role' => 'user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte os usuários com papel 'dirig' de volta para 'espec'
        // No-op (mudança destrutiva e definitiva de roles)
    }
};
