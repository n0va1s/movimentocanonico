<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voluntario extends Model
{
    use HasFactory;

    protected $table = 'voluntario';

    protected $primaryKey = 'idt_voluntario';

    public $timestamps = true;

    protected $fillable = [
        'idt_pessoa',
        'idt_evento',
        'idt_equipe',
        'idt_trabalhador', // para saber quem ja foi confirmado como trabalhador
        'txt_habilidade',
    ];

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento');
    }

    public function equipe()
    {
        return $this->belongsTo(TipoEquipe::class, 'idt_equipe')->withTrashed();
    }

    public function scopePendentes($query)
    {
        return $query->whereNull('idt_trabalhador');
    }

    public function scopeEfetivados($query)
    {
        return $query->whereNotNull('idt_trabalhador');
    }

    public function equipesInteresse()
    {
        return $this->hasMany(Voluntario::class, 'idt_pessoa', 'idt_pessoa')
            ->where('idt_evento', $this->idt_evento)
            ->where('idt_voluntario', '!=', $this->idt_voluntario);
    }

    public function emOutraEquipe(): bool
    {
        return Trabalhador::where('idt_pessoa', $this->idt_pessoa)
            ->where('idt_evento', $this->idt_evento)
            ->exists();
    }

    public static function listarAgrupadoPorPessoa(int $idt_evento)
    {
        // A ideia é selecionar as pessoas que são voluntárias para o evento
        // e ainda não foram confirmadas como trabalhadores.
        // Depois, carregar as equipes e habilidades diretamente para cada pessoa.

        return self::where('idt_evento', $idt_evento)
            ->pendentes()
            ->with(['pessoa', 'equipe'])
            ->get()
            ->groupBy('idt_pessoa')
            ->map(function ($itens) {
                // Agrupa por pessoa, pego os dados da pessoa do primeiro registro
                $primeiro = $itens->first();

                return (object) [
                    'pessoa' => $primeiro->pessoa,
                    // Mapeia apenas os dados necessários das equipes escolhidas
                    'equipes' => $itens->map(fn ($v) => (object) [
                        'idt_equipe' => $v->idt_equipe,
                        'des_grupo' => $v->equipe?->des_grupo ?? 'Equipe Indisponível',
                        'txt_habilidade' => $v->txt_habilidade,
                        'idt_voluntario' => $v->idt_voluntario,
                    ])->values(),
                ];
            })
            ->values();
    }

    public static function listarEquipesSelecionadas(int $idt_evento, int $idt_pessoa)
    {
        return self::where('idt_evento', $idt_evento)
            ->where('idt_pessoa', $idt_pessoa)
            ->pendentes()
            ->with('equipe:idt_equipe,des_grupo')
            ->get()
            ->pluck('equipe')
            ->filter();
    }
}
