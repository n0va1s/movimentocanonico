<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pessoa', function (Blueprint $table) {
            $table->date('dat_nascimento')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('senha_temporaria_expira_em')->nullable();
        });

        Schema::table('evento', function (Blueprint $table) {
            $table->foreignId('idt_encontro_origem')->nullable()->constrained('evento', 'idt_evento')->nullOnDelete();
        });

        Schema::create('candidatura_voluntario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idt_evento')->constrained('evento', 'idt_evento')->cascadeOnDelete();
            $table->foreignId('idt_pessoa')->constrained('pessoa', 'idt_pessoa')->cascadeOnDelete();
            $table->boolean('ind_publica')->default(false);
            $table->json('equipes_anteriores')->nullable();
            $table->string('instrumento', 120)->nullable();
            $table->boolean('ind_canta')->default(false);
            $table->text('observacoes')->nullable();
            $table->text('motivo_nao_selecao')->nullable();
            $table->timestamps();
            $table->unique(['idt_evento', 'idt_pessoa'], 'candidatura_evento_pessoa_unique');
        });

        Schema::create('coordenador_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idt_evento')->constrained('evento', 'idt_evento')->cascadeOnDelete();
            $table->foreignId('idt_equipe')->constrained('tipo_equipe', 'idt_equipe')->cascadeOnDelete();
            $table->foreignId('idt_pessoa')->nullable()->constrained('pessoa', 'idt_pessoa')->nullOnDelete();
            $table->string('nome');
            $table->string('apelido')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->unique(['idt_evento', 'idt_equipe']);
            $table->unique(['idt_evento', 'idt_pessoa']);
            $table->unique(['idt_evento', 'telefone']);
        });
    }

    public function down(): void
    {
        if (DB::table('pessoa')->whereNull('dat_nascimento')->exists()) {
            throw new RuntimeException('Há pessoas sem data de nascimento. A coluna precisa continuar aceitando valores nulos.');
        }

        Schema::dropIfExists('coordenador_evento');
        Schema::dropIfExists('candidatura_voluntario');
        Schema::table('evento', fn (Blueprint $table) => $table->dropConstrainedForeignId('idt_encontro_origem'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('senha_temporaria_expira_em'));
        Schema::table('pessoa', fn (Blueprint $table) => $table->date('dat_nascimento')->nullable(false)->change());
    }
};
