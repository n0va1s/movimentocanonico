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
        Schema::create('mensagem', function (Blueprint $table) {
            $table->id('idt_mensagem');
            $table->foreignId('idt_evento')
                ->constrained('evento', 'idt_evento')
                ->onDelete('cascade');
            $table->foreignId('usu_inclusao')
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->string('nom_campanha', 150);
            $table->text('txt_mensagem');
            $table->char('tip_destinatario', 1); // P - Participante, R - Responsável
            $table->integer('qtd_impactados')->default(0);
            $table->timestamps();
        });

        Schema::create('mensagem_envio', function (Blueprint $table) {
            $table->id('idt_mensagem_envio');
            $table->foreignId('idt_mensagem')
                ->constrained('mensagem', 'idt_mensagem')
                ->onDelete('cascade');
            $table->string('nom_destinatario', 150);
            $table->string('tel_destinatario', 20);
            $table->string('nom_responsavel', 150)->nullable();
            $table->boolean('ind_enviado')->default(false);
            $table->timestamp('dat_envio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensagem_envio');
        Schema::dropIfExists('mensagem');
    }
};
