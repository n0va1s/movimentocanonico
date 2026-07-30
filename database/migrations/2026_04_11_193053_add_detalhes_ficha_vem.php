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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->default('61988776655'); // para ajudar na carga do forms
            }
        });

        Schema::table('ficha_vem', function (Blueprint $table) {
            if (!Schema::hasColumn('ficha_vem', 'eml_mae')) {
                $table->string('eml_mae', 50)->nullable(); // para enviar a ficha para confirmacao
            }
            if (!Schema::hasColumn('ficha_vem', 'eml_pai')) {
                $table->string('eml_pai', 50)->nullable(); // para enviar a ficha para confirmacao
            }
            if (!Schema::hasColumn('ficha_vem', 'nom_responsavel')) {
                $table->string('nom_responsavel', 150)->nullable(); // caso nao more com os pais
            }
            if (!Schema::hasColumn('ficha_vem', 'tel_responsavel')) {
                $table->string('tel_responsavel', 20)->nullable();
            }
            if (!Schema::hasColumn('ficha_vem', 'eml_responsavel')) {
                $table->string('eml_responsavel', 50)->nullable(); // para enviar a ficha para confirmacao
            }
            if (!Schema::hasColumn('ficha_vem', 'ind_batizado')) {
                $table->boolean('ind_batizado')->default(false); // batizado ou não
            }
            if (!Schema::hasColumn('ficha_vem', 'ind_primeira_comunhao')) {
                $table->boolean('ind_primeira_comunhao')->default(false); // primeira comunhão ou não
            }
            if (!Schema::hasColumn('ficha_vem', 'ind_crismado')) {
                $table->boolean('ind_crismado')->default(false); // crismado ou não
            }
            if (!Schema::hasColumn('ficha_vem', 'nom_paroquia')) {
                $table->string('nom_paroquia', 150)->nullable(); // nome da paroquia que frequenta
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('ficha_vem', function (Blueprint $table) {
            $table->dropColumn([
                'eml_mae',
                'eml_pai',
                'nom_responsavel',
                'tel_responsavel',
                'eml_responsavel',
                'ind_batizado',
                'ind_primeira_comunhao',
                'ind_crismado',
                'nom_paroquia',
            ]);
        });
    }
};
