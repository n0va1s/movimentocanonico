<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conta extends Model
{
    use HasFactory;

    protected $table = 'conta';

    protected $primaryKey = 'idt_conta';

    protected $fillable = [
        'idt_pessoa',
        'idt_evento',
        'val_saldo',
        'usu_inclusao',
        'usu_alteracao',
    ];

    protected $casts = [
        'val_saldo' => 'decimal:2',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa', 'idt_pessoa');
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'idt_evento', 'idt_evento');
    }

    public function transacoes(): HasMany
    {
        return $this->hasMany(Transacao::class, 'idt_conta', 'idt_conta');
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_inclusao', 'id');
    }

    public function alterador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_alteracao', 'id');
    }

    /**
     * Recalcula o saldo da conta a partir do somatório de suas transações
     * e atualiza o saldo da tabela conta.
     */
    public function recalcularSaldo(): float
    {
        $creditos = $this->transacoes()->whereIn('tip_transacao', ['D', 'P'])->sum('val_transacao');
        $debitos = $this->transacoes()->where('tip_transacao', 'C')->sum('val_transacao');
        
        $this->val_saldo = $creditos - $debitos;
        $this->save();

        return (float) $this->val_saldo;
    }
}
