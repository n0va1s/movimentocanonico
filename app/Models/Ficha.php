<?php

namespace App\Models;

use App\Enums\ComoSoube;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Enums\TipoSituacao;
use App\Services\CpfService;
use App\Services\PhoneService;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'idt_pessoa_visitacao',
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
        'idt_pessoa_visitacao' => 'integer',
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

    public function visitador()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa_visitacao', 'idt_pessoa');
    }

    public function getDataNascimentoFormatada()
    {
        return $this->dat_nascimento
            ? $this->dat_nascimento->format('Y-m-d')
            : null;
    }

    public function getIndAprovadoAttribute(): bool
    {
        return $this->tip_situacao === TipoSituacao::APROVADA;
    }

    protected function numCpfCandidato(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CpfService::format($value),
            set: fn (?string $value) => CpfService::clean($value),
        );
    }

    protected function telCandidato(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }

    public function getEditRoute(): string
    {
        $movimentoId = (int) ($this->evento?->idt_movimento);
        return match ($movimentoId) {
            TipoMovimento::ECC => route('ecc.edit', $this->idt_ficha),
            TipoMovimento::VEM => route('vem.edit', $this->idt_ficha),
            TipoMovimento::SGM => route('sgm.edit', $this->idt_ficha),
            default => '#',
        };
    }

    public function getShowRoute(): string
    {
        $movimentoId = (int) ($this->evento?->idt_movimento);
        return match ($movimentoId) {
            TipoMovimento::ECC => route('ecc.show', $this->idt_ficha),
            TipoMovimento::VEM => route('vem.show', $this->idt_ficha),
            TipoMovimento::SGM => route('sgm.show', $this->idt_ficha),
            default => '#',
        };
    }

    public function getResponsavelInfoAttribute(): ?array
    {
        if ($this->fichaVem) {
            if (!empty($this->fichaVem->nom_responsavel)) {
                return [
                    'nome' => $this->fichaVem->nom_responsavel,
                    'telefone' => $this->fichaVem->tel_responsavel ?: 'Não informado',
                    'tipo' => 'Responsável'
                ];
            }
            if (!empty($this->fichaVem->nom_mae)) {
                return [
                    'nome' => $this->fichaVem->nom_mae,
                    'telefone' => $this->fichaVem->tel_mae ?: 'Não informado',
                    'tipo' => 'Mãe'
                ];
            }
            if (!empty($this->fichaVem->nom_pai)) {
                return [
                    'nome' => $this->fichaVem->nom_pai,
                    'telefone' => $this->fichaVem->tel_pai ?: 'Não informado',
                    'tipo' => 'Pai'
                ];
            }
        }

        if ($this->fichaSGM) {
            if (!empty($this->fichaSGM->nom_falar_com)) {
                return [
                    'nome' => $this->fichaSGM->nom_falar_com,
                    'telefone' => $this->fichaSGM->tel_falar_com ?: 'Não informado',
                    'tipo' => 'Responsável'
                ];
            }
            if (!empty($this->fichaSGM->nom_mae)) {
                return [
                    'nome' => $this->fichaSGM->nom_mae,
                    'telefone' => $this->fichaSGM->tel_mae ?: 'Não informado',
                    'tipo' => 'Mãe'
                ];
            }
            if (!empty($this->fichaSGM->nom_pai)) {
                return [
                    'nome' => $this->fichaSGM->nom_pai,
                    'telefone' => $this->fichaSGM->tel_pai ?: 'Não informado',
                    'tipo' => 'Pai'
                ];
            }
        }

        return null;
    }
}
