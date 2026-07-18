<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PessoaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trabalhador = $this->trabalhadores->first();
        $participante = $this->participantes->first();

        return [
            'id' => $this->idt_pessoa,
            'nome' => $this->nom_pessoa,
            'apelido' => $this->nom_apelido,
            'cpf' => $this->num_cpf_pessoa,
            'telefone' => $this->tel_pessoa,
            'email' => $this->eml_pessoa,
            'nascimento' => $this->getDataNascimentoFormatada(),
            'sexo' => $this->tip_genero?->value ?? $this->tip_genero,
            'endereco' => $this->des_endereco,
            'evento' => [
                'id' => $trabalhador?->idt_evento ?? $participante?->idt_evento,
                'perfil' => $trabalhador ? 'trabalhador' : ($participante ? 'participante' : null),
                'equipe' => $trabalhador?->equipe?->des_grupo,
                'troca' => $participante?->tip_cor_troca,
            ]
        ];
    }
}
