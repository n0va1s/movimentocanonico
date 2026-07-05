<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'idt_paroquia',
        'nom_movimento',
        'des_sigla',
        'dat_inicio',
        'ind_inscricao_aberta',
        'med_logo',
    ];

    protected $casts = [
        'dat_inicio' => 'date',
        'ind_inscricao_aberta' => 'boolean',
    ];

    public function paroquia(): BelongsTo
    {
        return $this->belongsTo(TipoParoquia::class, 'idt_paroquia', 'idt_paroquia');
    }

    public function equipes(): HasMany
    {
        return $this->hasMany(TipoEquipe::class, 'idt_movimento', 'idt_movimento');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class, 'idt_movimento');
    }



    /**
     * Accessor para formatar a data de início
     */
    public function getDataInicioFormatada()
    {
        return $this->dat_inicio ? $this->dat_inicio->format('d/m/Y') : null;
    }
}
