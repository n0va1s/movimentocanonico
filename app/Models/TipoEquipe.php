<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEquipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_equipe';

    protected $primaryKey = 'idt_equipe';

    public $timestamps = true;

    protected $fillable = [
        'idt_movimento',
        'des_grupo',
        'txt_documento',
        'ind_voluntariar',
    ];

    protected $casts = [
        'ind_voluntariar' => 'boolean',
    ];

    public function movimento()
    {
        return $this->belongsTo(TipoMovimento::class, 'idt_movimento');
    }

    public function trabalhadores()
    {
        return $this->hasMany(Trabalhador::class, 'idt_equipe');
    }

    public function voluntarios()
    {
        return $this->hasMany(Voluntario::class, 'idt_equipe');
    }
}
