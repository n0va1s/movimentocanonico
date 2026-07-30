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
        Schema::table('trabalhador', function (Blueprint $table) {
            if (!Schema::hasColumn('trabalhador', 'ind_termo_lgpd_aceito')) {
                $table->boolean('ind_termo_lgpd_aceito')->default(false)->after('ind_presente');
            }
            if (!Schema::hasColumn('trabalhador', 'dat_termo_lgpd_aceito')) {
                $table->timestamp('dat_termo_lgpd_aceito')->nullable()->after('ind_termo_lgpd_aceito');
            }
            if (!Schema::hasColumn('trabalhador', 'des_ip_termo_lgpd')) {
                $table->string('des_ip_termo_lgpd', 45)->nullable()->after('dat_termo_lgpd_aceito');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trabalhador', function (Blueprint $table) {
            $table->dropColumn([
                'ind_termo_lgpd_aceito',
                'dat_termo_lgpd_aceito',
                'des_ip_termo_lgpd',
            ]);
        });
    }
};
