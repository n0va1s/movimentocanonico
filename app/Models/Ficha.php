<?php

namespace App\Models;

use App\Enums\ComoSoube;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Enums\TipoSituacao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ficha extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ficha';

    protected $primaryKey = 'idt_ficha';

    public $timestamps = true;

    protected $fillable = [
        'idt_evento',
        'idt_pessoa',
        'tip_genero',
        'num_cpf_candidato',
        'nom_candidato',
        'nom_apelido',
        'dat_nascimento',
        'tel_candidato',
        'eml_candidato',
        'nom_profissao',
        'des_endereco',
        'tam_camiseta',
        'tip_como_soube',
        'tip_habilidade',
        'ind_catolico',
        'ind_toca_instrumento',
        'ind_consentimento',
        'tip_situacao',
        'ind_restricao',
        'usu_inclusao',
        'usu_alteracao',
        'txt_observacao',
    ];

    protected $casts = [
        'dat_nascimento' => 'date:Y-m-d',
        'ind_catolico' => 'boolean',
        'ind_toca_instrumento' => 'boolean',
        'ind_consentimento' => 'boolean',
        'tip_situacao' => TipoSituacao::class,
        'ind_restricao' => 'boolean',
        'tip_como_soube' => ComoSoube::class,
        'tip_habilidade' => HabilidadePrincipal::class,
        'tam_camiseta' => TamanhoCamiseta::class,
        'tip_genero' => Genero::class,
    ];

    protected static function booted()
    {
        static::creating(function ($ficha) {
            if (auth()->check()) {
                $ficha->usu_inclusao = auth()->id();
                $ficha->usu_alteracao = auth()->id();
            }
        });

        static::updating(function ($ficha) {
            if (auth()->check()) {
                $ficha->usu_alteracao = auth()->id();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'idt_ficha';
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento');
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa');
    }

    public function fichaVem()
    {
        return $this->hasOne(FichaVem::class, 'idt_ficha');
    }

    public function fichaEcc()
    {
        return $this->hasOne(FichaEcc::class, 'idt_ficha');
    }

    public function fichaSGM()
    {
        return $this->hasOne(FichaSGM::class, 'idt_ficha');
    }

    public function fichaSaude()
    {
        return $this->hasMany(FichaSaude::class, 'idt_ficha');
    }

    public function foto()
    {
        return $this->hasOne(FichaFoto::class, 'idt_ficha');
    }

    public function getDataNascimentoFormatada()
    {
        return $this->dat_nascimento
            ? $this->dat_nascimento->format('Y-m-d')
            : null;
    }
}
