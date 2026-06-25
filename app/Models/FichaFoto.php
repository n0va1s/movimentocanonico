<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaFoto extends Model
{
    use HasFactory;

    protected $table = 'ficha_foto';

    protected $primaryKey = 'idt_ficha';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'idt_ficha',
        'med_foto',
        'med_conjuge',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class, 'idt_ficha');
    }
}
