<?php

namespace App\Models;

use App\Services\CpfService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FichaEccFilho extends Model
{
    use HasFactory;

    protected $table = 'ficha_ecc_filho';

    protected $primaryKey = 'idt_filho';

    protected $fillable = [
        'idt_ficha',
        'idt_pessoa',
        'num_cpf_filho',
        'nom_filho',
        'dat_nascimento_filho',
        'eml_filho',
        'tel_filho',
    ];

    protected $casts = [
        'dat_nascimento_filho' => 'date:Y-m-d',
    ];

    public function fichaEcc()
    {
        return $this->belongsTo(FichaEcc::class, 'idt_ficha');
    }

    public function getDataNascimentoFormatada()
    {
        return $this->dat_nascimento_filho
            ? $this->dat_nascimento_filho->format('Y-m-d')
            : null;
    }

    protected function numCpfFilho(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CpfService::format($value),
            set: fn (?string $value) => CpfService::clean($value),
        );
    }
}
