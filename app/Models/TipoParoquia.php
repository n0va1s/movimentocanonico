<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoParoquia extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_paroquia';

    protected $primaryKey = 'idt_paroquia';

    protected $fillable = [
        'nom_paroquia',
        'nom_paroco',
        'eml_paroquia',
        'tel_paroquia',
        'des_chave_pix',
    ];

    public function movimentos(): HasMany
    {
        return $this->hasMany(TipoMovimento::class, 'idt_paroquia', 'idt_paroquia');
    }
}
