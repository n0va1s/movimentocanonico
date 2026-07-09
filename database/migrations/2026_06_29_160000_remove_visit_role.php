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
        // Converte todos os usuários com papel 'visit' para 'user'
        DB::table('users')->where('role', 'visit')->update(['role' => 'user']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op (não é estritamente reversível sem perda de informação anterior, mas a role está sendo excluída)
    }
};
