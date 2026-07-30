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
        Schema::table('pessoa', function (Blueprint $table) {
            if (!Schema::hasColumn('pessoa', 'idt_parceiro')) {
                $table->foreignId('idt_parceiro')->nullable()->constrained('pessoa', 'idt_pessoa')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pessoa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('idt_parceiro');
            $table->dropColumn('idt_parceiro');
        });
    }
};
