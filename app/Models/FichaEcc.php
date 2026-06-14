<?php

namespace App\Models;

use App\Enums\EstadoCivil;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Services\CpfService;
use App\Services\PhoneService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaEcc extends Model
{
    use HasFactory;

    protected $table = 'ficha_ecc';

    protected $primaryKey = 'idt_ficha';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idt_ficha',
        'idt_pessoa',
        // Cônjuge
        'num_cpf_conjuge',
        'nom_conjuge',
        'nom_apelido_conjuge',
        'tip_genero_conjuge',
        'dat_nascimento_conjuge',
        'tel_conjuge',
        'eml_conjuge',
        'nom_profissao_conjuge',
        'ind_catolico_conjuge',
        'tip_habilidade_conjuge',
        'tam_camiseta_conjuge',
        // Informações comuns do casal
        'tip_estado_civil',
        'nom_paroquia',
        'dat_casamento',
        'qtd_filhos',
    ];

    protected $casts = [
        // Cônjuge
        'dat_nascimento_conjuge' => 'date:Y-m-d',
        'ind_catolico_conjuge' => 'boolean',
        'tip_genero_conjuge' => Genero::class,
        'tip_habilidade_conjuge' => HabilidadePrincipal::class,
        'tam_camiseta_conjuge' => TamanhoCamiseta::class,
        // Casal
        'tip_estado_civil' => EstadoCivil::class,
        'dat_casamento' => 'date:Y-m-d',
        'qtd_filhos' => 'integer',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'idt_ficha');
    }

    public function filhos()
    {
        return $this->hasMany(FichaEccFilho::class, 'idt_ficha');
    }

    protected function numCpfConjuge(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CpfService::format($value),
            set: fn (?string $value) => CpfService::clean($value),
        );
    }

    protected function telConjuge(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }
}
