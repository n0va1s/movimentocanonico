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
        Schema::create('ficha_ecc_filho', function (Blueprint $table) {
            $table->id('idt_filho');
            $table->foreignId('idt_ficha')
                ->constrained('ficha', 'idt_ficha')
                ->onDelete('cascade');
            $table->foreignId('idt_pessoa')->nullable()
                ->constrained('pessoa', 'idt_pessoa')->nullOnDelete(); // pessoa criada apos aprovacao
            $table->string('num_cpf_filho', 20)->nullable()->unique();
            $table->string('nom_filho', 255);
            $table->date('dat_nascimento_filho');
            $table->string('eml_filho', 255)->nullable();
            $table->string('tel_filho', 20)->nullable();
            $table->timestamps();

            $table->index('idt_ficha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ficha_ecc_filho');
    }
};
