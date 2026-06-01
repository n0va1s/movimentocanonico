<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoFoto extends Model
{
    use HasFactory;

    protected $table = 'evento_foto';

    protected $primaryKey = 'idt_evento';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'idt_evento',
        'med_foto',
        'med_logo',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento');
    }
}
