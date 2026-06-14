<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Services\PhoneService;
use App\Enums\EscolaridadeSituacao;
use App\Enums\Religiao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaSGM extends Model
{
    use HasFactory;

    protected $table = 'ficha_sgm';

    protected $primaryKey = 'idt_ficha';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idt_ficha',
        'idt_falar_com',
        // Filiação
        'nom_mae',
        'tel_mae',
        'eml_mae',
        'nom_pai',
        'tel_pai',
        'eml_pai',
        'nom_falar_com',
        'tel_falar_com',
        // Dados pessoais SGM
        'des_naturalidade',
        // Escolaridade
        'tip_escolaridade',
        'tip_escolaridade_situacao',
        'des_curso',
        'nom_instituicao',
        // Religião
        'tip_religiao',
        'nom_paroquia',
        'ind_batismo',
        'ind_eucaristia',
        'ind_crisma',
        'des_participa_movimento',
        // Quem convidou
        'nom_convidou',
        'tel_convidou',
        'end_convidou',
    ];

    protected $casts = [
        'tip_religiao' => Religiao::class,
        'tip_escolaridade_situacao' => EscolaridadeSituacao::class,
        'ind_batismo' => 'boolean',
        'ind_eucaristia' => 'boolean',
        'ind_crisma' => 'boolean',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'idt_ficha');
    }

    public function tipoResponsavel()
    {
        return $this->belongsTo(TipoResponsavel::class, 'idt_falar_com', 'idt_responsavel');
    }

    protected function telMae(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }

    protected function telPai(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }

    protected function telFalarCom(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }

    protected function telConvidou(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => PhoneService::format($value),
            set: fn (?string $value) => PhoneService::clean($value),
        );
    }
}
