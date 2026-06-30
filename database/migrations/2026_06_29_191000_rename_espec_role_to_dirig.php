<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Converte todos os usuários com papel 'espec' para 'dirig'
        DB::table('users')->where('role', 'espec')->update(['role' => 'dirig']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverte os usuários com papel 'dirig' de volta para 'espec'
        DB::table('users')->where('role', 'dirig')->update(['role' => 'espec']);
    }
};
