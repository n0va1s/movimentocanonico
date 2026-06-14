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
        // Tabela de Produtos do Mercadinho
        Schema::create('produto', function (Blueprint $table) {
            $table->id('idt_produto');
            $table->string('nom_produto', 100);
            $table->string('des_produto', 255)->nullable();
            $table->decimal('val_preco', 10, 2);
            $table->integer('qtd_produto')->default(0); // Estoque
            $table->boolean('ind_favorito')->default(false);
            $table->foreignId('usu_inclusao')->constrained('users', 'id');
            $table->foreignId('usu_alteracao')->nullable()->constrained('users', 'id');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela de Contas Correntes por Evento
        Schema::create('conta', function (Blueprint $table) {
            $table->id('idt_conta');
            $table->foreignId('idt_pessoa')
                ->constrained('pessoa', 'idt_pessoa')
                ->onDelete('cascade');
            $table->foreignId('idt_evento')
                ->constrained('evento', 'idt_evento')
                ->onDelete('cascade');
            $table->decimal('val_saldo', 10, 2)->default(0.00);
            $table->foreignId('usu_inclusao')->constrained('users', 'id');
            $table->foreignId('usu_alteracao')->nullable()->constrained('users', 'id');
            $table->timestamps();

            // Uma pessoa possui apenas uma conta por evento
            $table->unique(['idt_pessoa', 'idt_evento'], 'unique_conta_pessoa_evento');
        });

        // Tabela de Transações Unificada (compras, depósitos, quitação)
        Schema::create('transacao', function (Blueprint $table) {
            $table->id('idt_transacao');
            $table->foreignId('idt_conta')
                ->constrained('conta', 'idt_conta')
                ->onDelete('cascade');
            $table->foreignId('idt_produto')
                ->nullable()
                ->constrained('produto', 'idt_produto')
                ->onDelete('restrict');
            $table->char('tip_transacao', 1); // C - Compra, D - Depósito/Crédito antecipado, P - Pagamento/Quitação
            $table->string('nom_item', 100)->nullable(); // Congela o nome do produto ou guarda item avulso
            $table->integer('qtd_item')->nullable(); // Quantidade (nulo para depósitos)
            $table->decimal('val_unitario', 10, 2)->nullable(); // Valor unitário (nulo para depósitos)
            $table->decimal('val_transacao', 10, 2); // Valor total movimentado
            $table->string('des_transacao', 255)->nullable(); // Observações/Detalhamento
            $table->timestamp('dat_transacao');
            $table->foreignId('usu_inclusao')->constrained('users', 'id');
            $table->foreignId('usu_alteracao')->nullable()->constrained('users', 'id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacao');
        Schema::dropIfExists('conta');
        Schema::dropIfExists('produto');
    }
};
