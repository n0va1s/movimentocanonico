<?php

namespace App\Http\Requests;

use App\Models\Ficha;
use App\Rules\Cpf;
use App\Rules\Telefone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FichaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('num_cpf_candidato')) {
            $this->merge([
                'num_cpf_candidato' => $this->input('num_cpf_candidato') ? preg_replace('/\D/', '', $this->input('num_cpf_candidato')) : null,
            ]);
        }
    }

    public function rules(): array
    {
        $fichaId = $this->route('ficha') ?? $this->route('ecc') ?? $this->route('sgm') ?? $this->route('vem') ?? $this->ficha;
        if ($fichaId instanceof Ficha) {
            $fichaId = $fichaId->idt_ficha;
        }

        return [
            'idt_evento' => 'required|exists:evento,idt_evento',
            'tip_genero' => 'required|string|max:3',
            'num_cpf_candidato' => [
                'nullable',
                'string',
                'max:20',
                new Cpf,
                function ($attribute, $value, $fail) use ($fichaId) {
                    if (!$value) return;

                    $eventoId = $this->input('idt_evento');
                    if (!$eventoId) return;

                    $evento = \App\Models\Evento::find($eventoId);
                    if (!$evento) return;

                    $exists = \App\Models\Ficha::where('num_cpf_candidato', $value)
                        ->where('idt_ficha', '!=', $fichaId ?? 0)
                        ->where('tip_situacao', '!=', \App\Enums\TipoSituacao::CANCELADA)
                        ->whereHas('evento', function ($query) use ($evento) {
                            $query->where('idt_movimento', $evento->idt_movimento);
                        })
                        ->exists();

                    if ($exists) {
                        $fail('Já existe ficha ativa cadastrada pra este CPF neste movimento.');
                    }
                },
            ],
            'nom_candidato' => 'required|string|max:255',
            'nom_apelido' => 'nullable|string|max:255',
            'dat_nascimento' => 'required|date',
            'tel_candidato' => ['nullable', new Telefone],
            'eml_candidato' => 'required|email|max:255',
            'nom_profissao' => 'nullable|string|max:150',
            'des_endereco' => 'required|string|max:255',
            'tam_camiseta' => 'required|string|max:3',
            'tip_como_soube' => 'nullable|string|max:3',
            'tip_habilidade' => 'nullable|string|max:1',
            'ind_catolico' => 'nullable|boolean',
            'ind_toca_instrumento' => 'nullable|boolean',
            'ind_consentimento' => 'required|accepted',
            'ind_restricao' => 'required|boolean',
            'txt_observacao' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            // Evento
            'idt_evento.required' => 'O evento é obrigatório.',
            'idt_evento.exists' => 'O evento selecionado não existe.',

            // Gênero
            'tip_genero.required' => 'O gênero é obrigatório.',
            'tip_genero.string' => 'O gênero deve ser um texto.',
            'tip_genero.max' => 'O gênero deve ter no máximo 3 caracteres.',

            // CPF
            'num_cpf_candidato.string' => 'O CPF do candidato deve ser um texto.',
            'num_cpf_candidato.max' => 'O CPF do candidato deve ter no máximo 20 caracteres.',
            'num_cpf_candidato.unique' => 'Já existe ficha cadastrada pra este CPF.',

            // Nome e Apelido
            'nom_candidato.required' => 'O nome do candidato é obrigatório.',
            'nom_candidato.string' => 'O nome do candidato deve ser um texto.',
            'nom_candidato.max' => 'O nome do candidato deve ter no máximo 255 caracteres.',
            'nom_apelido.string' => 'O apelido deve ser um texto.',
            'nom_apelido.max' => 'O apelido deve ter no máximo 255 caracteres.',

            // Nascimento e Contato
            'dat_nascimento.required' => 'A data de nascimento é obrigatória.',
            'dat_nascimento.date' => 'A data de nascimento deve ser uma data válida.',
            'tel_candidato.string' => 'O telefone deve ser um texto.',
            'tel_candidato.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'eml_candidato.required' => 'O e-mail é obrigatório.',
            'eml_candidato.email' => 'Informe um endereço de e-mail válido.',
            'eml_candidato.max' => 'O e-mail não pode ultrapassar 255 caracteres.',

            // Profissão e Endereço
            'nom_profissao.string' => 'A profissão deve ser um texto.',
            'nom_profissao.max' => 'A profissão deve ter no máximo 150 caracteres.',
            'des_endereco.required' => 'O endereço é obrigatório.',
            'des_endereco.string' => 'O endereço deve ser um texto.',
            'des_endereco.max' => 'O endereço deve ter no máximo 255 caracteres.',

            // Atributos Específicos
            'tam_camiseta.required' => 'O tamanho da camiseta é obrigatório.',
            'tam_camiseta.string' => 'O tamanho da camiseta deve ser um texto.',
            'tam_camiseta.max' => 'O tamanho da camiseta deve ter no máximo 3 caracteres.',
            'tip_como_soube.string' => 'O campo "Como soube" deve ser um texto.',
            'tip_como_soube.max' => 'O campo "Como soube" deve ter no máximo 3 caracteres.',
            'tip_habilidade.string' => 'A habilidade deve ser um texto.',
            'tip_habilidade.max' => 'A habilidade deve ter no máximo 1 caractere.',

            // Booleano / Flags
            'ind_catolico.boolean' => 'O campo "É católico?" deve ser verdadeiro ou falso.',
            'ind_toca_instrumento.boolean' => 'O campo "Toca instrumento?" deve ser verdadeiro ou falso.',
            'ind_restricao.required' => 'Informe se há alguma restrição.',
            'ind_restricao.boolean' => 'O campo restrição deve ser verdadeiro ou falso.',

            // Consentimento
            'ind_consentimento.required' => 'O consentimento é obrigatório.',
            'ind_consentimento.accepted' => 'Você deve aceitar os termos de consentimento para prosseguir.',

            // Observações
            'txt_observacao.string' => 'As observações devem ser um texto válido.',
        ];
    }
}
