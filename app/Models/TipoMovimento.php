<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMovimento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_movimento';

    protected $primaryKey = 'idt_movimento';

    public $timestamps = true;

    const ECC = 1;

    const VEM = 2;

    const SegueMe = 3;

    const SGM = 3;

    protected $fillable = [
        'nom_movimento',
        'des_sigla',
        'dat_inicio',
    ];

    protected $casts = [
        'dat_inicio' => 'date',
    ];

    public function eventos()
    {
        return $this->hasMany(Evento::class, 'idt_movimento');
    }

    public function equipes()
    {
        return $this->hasMany(TipoEquipe::class, 'idt_movimento');
    }

    /**
     * Accessor para formatar a data de início
     */
    public function getDataInicioFormatada()
    {
        return $this->dat_inicio ? $this->dat_inicio->format('d/m/Y') : null;
    }
}
