<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PessoaSaude extends Model
{
    use HasFactory;

    protected $table = 'pessoa_saude';

    public $timestamps = true;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'idt_pessoa',
        'idt_restricao',
        'ind_remedio_regular',
        'txt_complemento',
    ];

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'idt_pessoa');
    }

    public function restricao()
    {
        return $this->belongsTo(TipoRestricao::class, 'idt_restricao');
    }
}
