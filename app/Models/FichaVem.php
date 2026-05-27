<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaVem extends Model
{
    use HasFactory;

    protected $table = 'ficha_vem';

    protected $primaryKey = 'idt_ficha';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idt_ficha',
        'idt_falar_com',
        'des_onde_estuda',
        'des_mora_quem',
        'nom_pai',
        'eml_pai',
        'tel_pai',
        'nom_mae',
        'tel_mae',
        'eml_mae',
        'nom_responsavel',
        'tel_responsavel',
        'eml_responsavel',
        'ind_batizado',
        'ind_primeira_comunhao',
        'ind_crismado',
        'nom_paroquia',
    ];

    protected $casts = [
        'ind_batizado' => 'boolean',
        'ind_primeira_comunhao' => 'boolean',
        'ind_crismado' => 'boolean',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'idt_ficha');
    }

    public function tipoResponsavel()
    {
        return $this->belongsTo(TipoResponsavel::class, 'idt_falar_com');
    }
}
