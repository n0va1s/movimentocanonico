<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaSaude extends Model
{
    use HasFactory;

    protected $table = 'ficha_saude';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'idt_ficha',
        'idt_restricao',
        'txt_complemento',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'idt_ficha');
    }

    public function restricao()
    {
        return $this->belongsTo(TipoRestricao::class, 'idt_restricao');
    }
}
