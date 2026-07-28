<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermoAceite extends Model
{
    use HasFactory;

    protected $table = 'termo_aceite';

    protected $primaryKey = 'idt_termo_aceite';

    protected $fillable = [
        'idt_usuario',
        'idt_evento',
        'tip_termo',
        'dat_aceite',
        'des_ip',
    ];

    protected function casts(): array
    {
        return [
            'dat_aceite' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idt_usuario', 'id');
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'idt_evento', 'idt_evento');
    }
}
