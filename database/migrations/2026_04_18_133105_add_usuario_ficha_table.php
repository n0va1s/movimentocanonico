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
        Schema::table('ficha', function (Blueprint $table) {
            if (!Schema::hasColumn('ficha', 'usu_inclusao')) {
                $table->foreignId('usu_inclusao')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('ficha', 'usu_alteracao')) {
                $table->foreignId('usu_alteracao')->nullable()->constrained('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha', function (Blueprint $table) {
            $table->dropColumn('usu_inclusao');
            $table->dropColumn('usu_alteracao');
        });
    }
};
