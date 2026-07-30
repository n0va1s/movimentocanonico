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
        Schema::table('evento', function (Blueprint $table) {
            if (!Schema::hasColumn('evento', 'dat_limite_inscricao')) {
                $table->date('dat_limite_inscricao')->nullable();
            }
            if (!Schema::hasColumn('evento', 'qtd_vaga')) {
                $table->integer('qtd_vaga')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evento', function (Blueprint $table) {
            $table->dropColumn('dat_limite_inscricao');
            $table->dropColumn('qtd_vaga');
        });
    }
};
