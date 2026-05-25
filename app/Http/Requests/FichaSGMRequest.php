<?php

namespace App\Http\Requests;

use App\Enums\Escolaridade;
use App\Enums\EscolaridadeSituacao;
use App\Enums\Religiao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class FichaSGMRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Responsável em caso de emergência
            'idt_falar_com' => 'required|exists:tipo_responsavel,idt_responsavel',

            // Filiação
            'nom_mae' => 'nullable|string|max:255',
            'tel_mae' => 'nullable|string|max:20',
            'eml_mae' => 'nullable|email|max:100',
            'nom_pai' => 'nullable|string|max:255',
            'tel_pai' => 'nullable|string|max:20',
            'eml_pai' => 'nullable|email|max:100',
            'nom_falar_com' => 'nullable|string|max:150',
            'tel_falar_com' => 'nullable|string|max:20',

            // Dados pessoais SGM
            'des_naturalidade' => 'required|string|max:255',
            'med_foto' => 'nullable|image|max:10240',

            // Escolaridade
            'tip_escolaridade' => ['required', new Enum(Escolaridade::class)],
            'tip_escolaridade_situacao' => ['required', new Enum(EscolaridadeSituacao::class)],
            'des_curso' => 'nullable|string|max:255',
            'nom_instituicao' => 'nullable|string|max:255',

            // Religião
            'tip_religiao' => ['required', new Enum(Religiao::class)],
            'nom_paroquia' => 'nullable|string|max:255',
            'ind_batismo' => 'nullable|boolean',
            'ind_eucaristia' => 'nullable|boolean',
            'ind_crisma' => 'nullable|boolean',
            'des_participa_movimento' => 'nullable|string|max:255',

            // Quem convidou
            'nom_convidou' => 'nullable|string|max:255',
            'tel_convidou' => 'nullable|string|max:20',
            'end_convidou' => 'nullable|string|max:255',
        ];
    }

    /**
     * Exige que ao menos mãe ou pai tenha o nome preenchido.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $temMae = filled($this->input('nom_mae'));
            $temPai = filled($this->input('nom_pai'));

            if (! $temMae && ! $temPai) {
                $validator->errors()->add(
                    'filiacao',
                    'Informe ao menos um dos pais: dados da mãe ou dados do pai.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'idt_falar_com.required' => 'Informe com quem devemos falar em caso de necessidade.',
            'des_naturalidade.required' => 'Informe a naturalidade do candidato.',
            'eml_mae.email' => 'Informe um e-mail válido para a mãe.',
            'eml_pai.email' => 'Informe um e-mail válido para o pai.',
            'med_foto.image' => 'A foto deve ser uma imagem.',
            'med_foto.max' => 'A foto não pode ter mais de 10MB.',
            'med_foto.uploaded' => 'A foto não pôde ser enviada. Verifique se o tamanho não excede o limite do servidor (normalmente 2MB) ou tente uma imagem menor.',
        ];
    }
}
