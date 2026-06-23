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
            $table->foreignId('idt_pessoa_visitacao')
                ->nullable()
                ->after('idt_pessoa')
                ->constrained('pessoa', 'idt_pessoa')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ficha', function (Blueprint $table) {
            $table->dropForeign(['idt_pessoa_visitacao']);
            $table->dropColumn('idt_pessoa_visitacao');
        });
    }
};
