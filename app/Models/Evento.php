<?php

namespace App\Models;

use App\Enums\FaixaEtaria;
use App\Enums\TipoEvento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'evento';

    protected $primaryKey = 'idt_evento';

    public $timestamps = true;

    protected $fillable = [
        'idt_movimento',
        'des_evento',
        'num_evento',
        'dat_inicio',
        'dat_termino',
        'dat_limite_inscricao',
        'qtd_vaga',
        'val_camiseta',
        'val_trabalhador',
        'val_venista',
        'val_entrada',
        'val_receita',
        'val_despesa',
        'tip_evento',
        'tip_faixa_etaria',
        'txt_informacao',
        'txt_relatorio',
        'med_foto',
        'med_logo',
    ];

    protected $casts = [
        'idt_movimento' => 'integer',
        'dat_limite_inscricao' => 'date',
        'qtd_vaga' => 'integer',
        'dat_inicio' => 'date',
        'dat_termino' => 'date',
        'tip_faixa_etaria' => FaixaEtaria::class,
        'tip_evento' => TipoEvento::class,
    ];

    /**
     * Define o relacionamento de um evento com um tipo de movimento.
     */
    public function movimento()
    {
        return $this->belongsTo(TipoMovimento::class, 'idt_movimento');
    }

    /**
     * Define o relacionamento de um evento com as suas fichas.
     */
    public function fichas()
    {
        return $this->hasMany(Ficha::class, 'idt_evento');
    }

    /**
     * Define o relacionamento de um evento com os seus participantes.
     */
    public function participantes()
    {
        return $this->hasMany(Participante::class, 'idt_evento');
    }

    /**
     * Define o relacionamento de um evento com os seus voluntários.
     */
    public function voluntarios()
    {
        return $this->hasMany(Voluntario::class, 'idt_evento');
    }

    /**
     * Define o relacionamento de um evento com os seus trabalhadores.
     */
    public function trabalhadores()
    {
        return $this->hasMany(Trabalhador::class, 'idt_evento');
    }

    /**
     * Define o relacionamento de um evento com a sua foto.
     */
    public function foto()
    {
        return $this->hasOne(EventoFoto::class, 'idt_evento');
    }

    /**
     * Define o relacionamento de um evento com a sua logo.
     */
    public function logo()
    {
        return $this->hasOne(EventoFoto::class, 'idt_evento');
    }

    /**
     * Scope para busca insensível a maiúsculas e minúsculas nos campos des_evento e num_evento.
     *
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $search)
    {
        if ($search) {
            $lowerSearch = strtolower($search);

            return $query->where(function (Builder $q) use ($lowerSearch) {
                $q->whereRaw('LOWER(des_evento) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(num_evento) LIKE ?', ["%{$lowerSearch}%"]);
            });
        }

        return $query;
    }

    /**
     * Scope para buscar eventos por ID de movimento.
     *
     * @return Builder
     */
    public function scopeMovimento(Builder $query, ?int $idt_movimento)
    {
        if ($idt_movimento) {
            return $query->where('idt_movimento', $idt_movimento);
        }

        return $query;
    }

    /**
     * Retorna collection de eventos por tipo de movimento e tipo de evento
     *
     * @param  int  $tipMovimento  // Constantes da classe TipoMovimento
     * @param  string  $tipEvento  // E - evento anual, P - pós-encontro, D - desafio
     * @return int|null $limite // quantidade de linha retornadas
     */
    public static function getByTipo(int $tipMovimento, string $tipEvento, ?int $limite = null)
    {
        $query = self::where('idt_movimento', $tipMovimento)
            ->where('tip_evento', $tipEvento)
            ->orderBy('dat_inicio', 'asc');

        if ($limite) {
            return $query->limit($limite)->get();
        }

        return $query->get();
    }

    public function getDataInicioFormatada()
    {
        return $this->dat_inicio
            ? $this->dat_inicio->format('d/m/Y')
            : null;
    }

    public function getDataTerminoFormatada()
    {
        return $this->dat_termino
            ? $this->dat_termino->format('d/m/Y')
            : null;
    }
}
