<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participante extends Model
{
    use HasFactory;

    protected $table = 'participante';

    protected $primaryKey = 'idt_participante';

    public $timestamps = true;

    protected $fillable = [
        'idt_pessoa',
        'idt_evento',
        'tip_cor_troca',
        'ind_taxa_pagou',
        'ind_presente',
    ];

    protected $casts = [
        'ind_taxa_pagou' => 'boolean',
        'ind_presente' => 'boolean',
    ];

    protected static function booted()
    {
        parent::boot();

        static::created(function (Participante $participante) {
            $tipEvento = $participante->evento->tip_evento;
            $pontos = (($tipEvento instanceof \App\Enums\TipoEvento ? $tipEvento->value : $tipEvento) === 'D') ? 3 : 1;
            Gamificacao::create([
                'idt_pessoa' => $participante->idt_pessoa,
                'qtd_pontos' => $pontos,
                'des_motivo' => 'Participou do evento: '.$participante->evento->des_evento,
                'origem_id' => $participante->idt_participante,
                'origem_type' => get_class($participante),
            ]);
        });
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento');
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa');
    }
}
