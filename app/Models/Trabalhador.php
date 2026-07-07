<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabalhador extends Model
{
    use HasFactory;

    protected $table = 'trabalhador';

    protected $primaryKey = 'idt_trabalhador';

    public $timestamps = true;

    protected $fillable = [
        'idt_pessoa',
        'idt_evento',
        'idt_equipe',
        'ind_coordenador',
        'ind_recomendado',
        'ind_lideranca',
        'ind_destaque',
        'ind_avaliacao',
        'ind_primeira_vez',
        'ind_camiseta_pediu',
        'ind_camiseta_pagou',
        'ind_taxa_pagou',
        'ind_presente',
    ];

    protected $casts = [
        'ind_coordenador' => 'boolean',
        'ind_recomendado' => 'boolean',
        'ind_lideranca' => 'boolean',
        'ind_destaque' => 'boolean',
        'ind_avaliacao' => 'boolean',
        'ind_primeira_vez' => 'boolean',
        'ind_camiseta_pediu' => 'boolean',
        'ind_camiseta_pagou' => 'boolean',
        'ind_taxa_pagou' => 'boolean',
        'ind_presente' => 'boolean',
    ];

    protected static function booted()
    {
        parent::boot();
        static::created(function ($trabalhador) {
            $pontos = $trabalhador->ind_coordenador ? 4 : 2;
            Gamificacao::create([
                'idt_pessoa' => $trabalhador->idt_pessoa,
                'qtd_pontos' => $pontos,
                'des_motivo' => 'Trabalhou no evento: '.$trabalhador->evento->des_evento,
                'origem_id' => $trabalhador->idt_trabalhador,
                'origem_type' => get_class($trabalhador),
            ]);
        });
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento')->withTrashed();
    }

    public function equipe()
    {
        return $this->belongsTo(TipoEquipe::class, 'idt_equipe')->withTrashed();
    }

    /**
     * Scope para retornar os trabalhadores de um evento específico
     */
    public function scopeEvento(Builder $query, ?int $idt_evento): Builder
    {
        if ($idt_evento) {
            return $query->where('idt_evento', $idt_evento);
        }

        return $query;
    }
}
