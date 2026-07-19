<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produto';

    protected $primaryKey = 'idt_produto';

    protected $fillable = [
        'idt_evento',
        'nom_produto',
        'des_produto',
        'val_preco',
        'qtd_produto',
        'ind_favorito',
        'usu_inclusao',
        'usu_alteracao',
    ];

    protected $casts = [
        'val_preco' => 'decimal:2',
        'qtd_produto' => 'integer',
        'ind_favorito' => 'boolean',
    ];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_inclusao', 'id');
    }

    public function alterador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usu_alteracao', 'id');
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'idt_evento', 'idt_evento');
    }
}
