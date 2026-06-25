<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensagem extends Model
{
    use HasFactory;

    protected $table = 'mensagem';
    protected $primaryKey = 'idt_mensagem';

    protected $fillable = [
        'idt_evento',
        'usu_inclusao',
        'nom_campanha',
        'txt_mensagem',
        'tip_destinatario',
        'qtd_impactados',
    ];

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'idt_evento', 'idt_evento');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usu_inclusao', 'id');
    }

    public function envios()
    {
        return $this->hasMany(MensagemEnvio::class, 'idt_mensagem', 'idt_mensagem');
    }

    /**
     * Substitui placeholders e resolve blocos Spintax (ex: {Olá|Oi|Bom dia}).
     */
    public static function formatar(string $template, array $data): string
    {
        // 1. Substituir placeholders básicos
        $placeholders = [
            '{nome}' => $data['nome'] ?? '',
            '{apelido}' => $data['apelido'] ?? '',
            '{evento}' => $data['evento'] ?? '',
            '{participante}' => $data['participante'] ?? '',
            '{responsavel_nome}' => $data['responsavel_nome'] ?? '',
        ];
        $formatted = str_replace(array_keys($placeholders), array_values($placeholders), $template);

        // 2. Processar Spintax: seleciona aleatoriamente entre opções separadas por "|"
        return preg_replace_callback('/\{([^{}]+)\}/', function ($match) {
            if (str_contains($match[1], '|')) {
                $parts = explode('|', $match[1]);
                return trim($parts[array_rand($parts)]);
            }
            return $match[0];
        }, $formatted);
    }
}
