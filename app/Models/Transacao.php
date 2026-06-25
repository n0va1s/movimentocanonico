<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transacao extends Model
{
    use HasFactory;

    protected $table = 'transacao';

    protected $primaryKey = 'idt_transacao';

    protected $fillable = [
        'idt_conta',
        'idt_produto',
        'tip_transacao',
        'nom_item',
        'qtd_item',
        'val_unitario',
        'val_transacao',
        'des_transacao',
        'dat_transacao',
        'usu_inclusao',
        'usu_alteracao',
    ];

    protected $casts = [
        'val_unitario' => 'decimal:2',
        'val_transacao' => 'decimal:2',
        'qtd_item' => 'integer',
        'dat_transacao' => 'datetime',
    ];

    public function conta(): BelongsTo
    {
        return $this->belongsTo(Conta::class, 'idt_conta', 'idt_conta');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'idt_produto', 'idt_produto');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_inclusao', 'id');
    }

    public function alterador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_alteracao', 'id');
    }

    protected static function booted()
    {
        parent::boot();

        // Garante que compras possuam um produto válido associado
        static::saving(function (Transacao $transacao) {
            if ($transacao->tip_transacao === 'C' && is_null($transacao->idt_produto)) {
                throw new \InvalidArgumentException('Compras devem possuir um produto válido associado.');
            }
        });

        // Sempre que uma transação for criada
        static::created(function (Transacao $transacao) {
            $transacao->conta->recalcularSaldo();

            // Se for compra de produto cadastrado, reduz o estoque
            if ($transacao->tip_transacao === 'C' && $transacao->idt_produto) {
                $transacao->produto()->decrement('qtd_produto', $transacao->qtd_item);
            }
        });

        // Sempre que uma transação for atualizada
        static::updated(function (Transacao $transacao) {
            $transacao->conta->recalcularSaldo();
            
            // Tratamento simplificado: em sistemas financeiros transações são em sua maioria imutáveis.
            // Se houver alteração de estoque (ex: alterou quantidade), o desenvolvedor deve estornar e recriar.
        });

        // Sempre que uma transação for excluída (estornada)
        static::deleted(function (Transacao $transacao) {
            $transacao->conta->recalcularSaldo();

            // Se for compra de produto cadastrado, devolve ao estoque
            if ($transacao->tip_transacao === 'C' && $transacao->idt_produto) {
                $transacao->produto()->increment('qtd_produto', $transacao->qtd_item);
            }
        });
    }
}
