<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona as colunas específicas da ficha SGM (Segue-me) à tabela ficha_sgm
     * e garante a existência da coluna `phone` na tabela users.
     */
    public function up(): void
    {
        Schema::table('ficha_sgm', function (Blueprint $table) {
            $table->dropColumn([
                'des_mora_quem',
            ]);
        });

        Schema::table('ficha_sgm', function (Blueprint $table) {

            // ── Filiação ──────────────────────────────────────────────────────
            // Todos opcionais no formulário (sem atributo required)
            $table->string('eml_mae', 100)->nullable();

            $table->string('eml_pai', 100)->nullable();

            $table->string('nom_falar_com', 150)->nullable();
            $table->string('tel_falar_com', 20)->nullable();

            // ── Dados pessoais ────────────────────────────────────────────────
            $table->string('des_naturalidade', 255);

            // ── Escolaridade ──────────────────────────────────────────────────
            $table->string('tip_escolaridade', 1);
            // tip_escolaridade_situacao: Enum EscolaridadeSituacao (C, O, T, I) — opcional
            $table->string('tip_escolaridade_situacao', 1);
            $table->string('des_curso', 255)->nullable();
            $table->string('nom_instituicao', 255)->nullable();

            // ── Religião ──────────────────────────────────────────────────────
            // tip_religiao: Enum Religiao (C, E, S, A, O, J, I, N, T) — opcional
            $table->string('tip_religiao', 1);
            $table->string('nom_paroquia', 255)->nullable();
            // Sacramentos: booleanos com default false; sem nullable (valor sempre definido)
            $table->boolean('ind_batismo')->default(false);
            $table->boolean('ind_eucaristia')->default(false);
            $table->boolean('ind_crisma')->default(false);
            $table->string('des_participa_movimento', 255)->nullable();

            // ── Quem convidou ─────────────────────────────────────────────────
            // Todos opcionais no formulário (sem atributo required)
            $table->string('nom_convidou', 255)->nullable();
            $table->string('tel_convidou', 20)->nullable();
            $table->string('end_convidou', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha_sgm', function (Blueprint $table) {
            $table->dropColumn([
                // Filiação
                'nom_mae',
                'tel_mae',
                'eml_mae',
                'nom_pai',
                'tel_pai',
                'eml_pai',
                'nom_falar_com',
                'tel_falar_com',
                // Dados pessoais
                'des_naturalidade',
                // Escolaridade
                'tip_escolaridade',
                'tip_escolaridade_situacao',
                'des_curso',
                'nom_instituicao',
                // Religião
                'tip_religiao',
                'nom_paroquia',
                'ind_batismo',
                'ind_eucaristia',
                'ind_crisma',
                'des_participa_movimento',
                // Quem convidou
                'nom_convidou',
                'tel_convidou',
                'end_convidou',
            ]);
        });
    }
};
