<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Esta tabela armazena apenas os dados exclusivos do ECC:
     *   - Dados do cônjuge
     *   - Informações comuns do casal
     *   - Filhos (tabela separada ficha_ecc_filho)
     */
    public function up(): void
    {
        Schema::table('ficha_ecc', function (Blueprint $table) {

            // ── Cônjuge ──────────────────────────────────────────────────────
            $table->foreignId('idt_pessoa')->nullable()
                ->constrained('pessoa', 'idt_pessoa')->nullOnDelete(); // pessoa criada apos aprovacao
            $table->string('num_cpf_conjuge', 20)->unique();
            $table->string('tip_genero_conjuge', 3)->nullable();
            $table->string('eml_conjuge', 255)->nullable()->after('tel_conjuge');
            $table->string('nom_profissao_conjuge', 255)->nullable()->after('eml_conjuge');
            $table->boolean('ind_catolico_conjuge')->default(false)->after('nom_profissao_conjuge');
            $table->string('tip_habilidade_conjuge', 1)->nullable()->after('ind_catolico_conjuge');

            // ── Informações comuns do casal ───────────────────────────────────
            $table->string('tip_estado_civil', 3)->nullable()->after('tam_camiseta_conjuge');
            $table->string('nom_paroquia', 150)->nullable()->after('tip_estado_civil');
            $table->date('dat_casamento')->nullable()->after('nom_paroquia');
            $table->unsignedTinyInteger('qtd_filhos')->default(0)->after('dat_casamento');
        });

        // ── Foto do cônjuge em pessoa_foto ────────────────────────────────────
        Schema::create('ficha_foto', function (Blueprint $table) {
            $table->foreignId('idt_ficha')
                ->constrained('ficha', 'idt_ficha')
                ->onDelete('cascade');
            $table->string('med_foto'); // armazenar no filesystem
            $table->string('med_conjuge')->nullable();
            $table->timestamps();
            $table->primary(['idt_ficha']);
        });

        // -- Inclusão de informações comuns na ficha e pessoa
        Schema::table('ficha', function (Blueprint $table) {
            $table->string('num_cpf_candidato', 20)->nullable()->unique()->after('idt_pessoa');
            $table->string('nom_profissao')->nullable();
            $table->string('tip_habilidade', 1)->nullable();
        });

        Schema::table('pessoa', function (Blueprint $table) {
            $table->string('num_cpf_pessoa', 20)->nullable()->unique()->after('idt_pessoa');
            $table->string('nom_profissao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ficha_ecc', function (Blueprint $table) {
            $table->dropColumn([
                'cpf_conjuge',
                'tip_genero_conjuge',
                'eml_conjuge',
                'nom_profissao_conjuge',
                'ind_catolico_conjuge',
                'tip_habilidade_conjuge',
                'tip_estado_civil',
                'nom_paroquia',
                'dat_casamento',
                'qtd_filhos',
            ]);
        });

        Schema::dropIfExists('ficha_foto');

        Schema::table('ficha', function (Blueprint $table) {
            $table->dropColumn('cpf_candidato');
            $table->dropColumn('nom_profissao');
            $table->dropColumn('tip_habilidade');
        });

        Schema::table('pessoa', function (Blueprint $table) {
            $table->dropColumn('cpf_pessoa');
            $table->dropColumn('nom_profissao');
        });
    }
};
