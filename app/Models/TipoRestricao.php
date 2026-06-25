<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoRestricao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_restricao';

    protected $primaryKey = 'idt_restricao';

    public $timestamps = true;

    protected $fillable = [
        'des_restricao',
        'tip_restricao',
    ];

    public function fichas()
    {
        return $this->hasMany(FichaSaude::class, 'idt_restricao');
    }

    public function pessoas()
    {
        return $this->hasMany(PessoaSaude::class, 'idt_restricao');
    }

    public function getTipo(): string
    {
        return match ($this->tip_restricao) {
            'ALE' => 'Alergia',
            'INT' => 'Intolerância',
            'MED' => 'Medicamento',
            'CUT' => 'Cutânea',
            'PNE' => 'Necessidade Especial',
            'VEG' => 'Vegetarianismo',
            'RES' => 'Respiratório',
            default => $this->tip_restricao,
        };
    }

    public function getCor(): string
    {
        return match ($this->tip_restricao) {
            'ALE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'INT' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'MED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'CUT' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'PNE' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'VEG' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'RES' => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-zinc-700 dark:text-gray-200',
        };
    }
}
