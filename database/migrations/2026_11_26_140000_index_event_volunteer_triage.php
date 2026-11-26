<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voluntario', fn (Blueprint $table) => $table
            ->index(['idt_evento', 'idt_pessoa', 'idt_equipe'], 'voluntario_triagem_evento_idx'));
        Schema::table('participante', fn (Blueprint $table) => $table
            ->index(['idt_evento', 'idt_pessoa'], 'participante_triagem_evento_idx'));
        Schema::table('trabalhador', fn (Blueprint $table) => $table
            ->index(['idt_evento', 'idt_pessoa', 'ind_coordenador'], 'trabalhador_triagem_evento_idx'));
        Schema::table('evento', fn (Blueprint $table) => $table
            ->index(['tip_evento', 'idt_movimento', 'dat_inicio'], 'evento_triagem_origem_idx'));
    }

    public function down(): void
    {
        Schema::table('evento', fn (Blueprint $table) => $table->dropIndex('evento_triagem_origem_idx'));
        Schema::table('trabalhador', fn (Blueprint $table) => $table->dropIndex('trabalhador_triagem_evento_idx'));
        Schema::table('participante', fn (Blueprint $table) => $table->dropIndex('participante_triagem_evento_idx'));
        Schema::table('voluntario', fn (Blueprint $table) => $table->dropIndex('voluntario_triagem_evento_idx'));
    }
};
