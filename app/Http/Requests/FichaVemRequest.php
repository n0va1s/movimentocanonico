<?php

namespace App\Http\Requests;

use App\Rules\Telefone;
use Illuminate\Validation\Validator;

class FichaVemRequest extends FichaRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [

            'idt_falar_com' => 'required|exists:tipo_responsavel,idt_responsavel',
            'des_onde_estuda' => 'required|string|max:255',
            'des_mora_quem' => 'required|string|max:255',

            'nom_pai' => 'nullable|string|max:255',
            'tel_pai' => ['nullable', new Telefone],
            'eml_pai' => 'nullable|email|max:50',

            'nom_mae' => 'nullable|string|max:255',
            'tel_mae' => ['nullable', new Telefone],
            'eml_mae' => 'nullable|email|max:50',

            'nom_responsavel' => 'nullable|string|max:150',
            'tel_responsavel' => ['nullable', new Telefone],
            'eml_responsavel' => 'nullable|email|max:50',

            'ind_batizado' => 'required|boolean',
            'ind_primeira_comunhao' => 'required|boolean',
            'ind_crismado' => 'required|boolean',
            'nom_paroquia' => 'nullable|string|max:150',
        ]);
    }

    /**
     * Validações adicionais após as regras básicas.
     * Exige que ao menos um responsável (mãe, pai ou responsável) tenha o nome preenchido.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $temMae = filled($this->input('nom_mae'));
            $temPai = filled($this->input('nom_pai'));
            $temResponsavel = filled($this->input('nom_responsavel'));

            if (! $temMae && ! $temPai && ! $temResponsavel) {
                $validator->errors()->add(
                    'responsaveis',
                    'Informe ao menos um responsável: dados da mãe, pai ou responsável.'
                );
            }
        });
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'idt_falar_com.required' => 'Informe com quem devemos falar em caso de necessidade.',
            'idt_falar_com.exists' => 'O responsável selecionado é inválido.',

            'des_onde_estuda.required' => 'Informe onde o candidato estuda.',
            'des_onde_estuda.string' => 'A instituição de ensino deve ser um texto.',
            'des_onde_estuda.max' => 'A instituição de ensino deve ter no máximo 255 caracteres.',

            'des_mora_quem.required' => 'Informe com quem o candidato mora.',
            'des_mora_quem.string' => 'A informação sobre com quem mora deve ser um texto.',
            'des_mora_quem.max' => 'A informação sobre com quem mora deve ter no máximo 255 caracteres.',

            'nom_pai.string' => 'O nome do pai deve ser um texto.',
            'nom_pai.max' => 'O nome do pai deve ter no máximo 255 caracteres.',
            'tel_pai.string' => 'O telefone do pai deve ser um texto.',
            'tel_pai.max' => 'O telefone do pai deve ter no máximo 20 caracteres.',
            'eml_pai.email' => 'O e-mail do pai deve ser um endereço válido.',
            'eml_pai.max' => 'O e-mail do pai deve ter no máximo 50 caracteres.',

            'nom_mae.string' => 'O nome da mãe deve ser um texto.',
            'nom_mae.max' => 'O nome da mãe deve ter no máximo 255 caracteres.',
            'tel_mae.string' => 'O telefone da mãe deve ser um texto.',
            'tel_mae.max' => 'O telefone da mãe deve ter no máximo 20 caracteres.',
            'eml_mae.email' => 'O e-mail da mãe deve ser um endereço válido.',
            'eml_mae.max' => 'O e-mail da mãe deve ter no máximo 50 caracteres.',

            'nom_responsavel.string' => 'O nome do responsável deve ser um texto.',
            'nom_responsavel.max' => 'O nome do responsável deve ter no máximo 150 caracteres.',
            'tel_responsavel.string' => 'O telefone do responsável deve ser um texto.',
            'tel_responsavel.max' => 'O telefone do responsável deve ter no máximo 15 caracteres.',
            'eml_responsavel.email' => 'O e-mail do responsável deve ser um endereço válido.',
            'eml_responsavel.max' => 'O e-mail do responsável deve ter no máximo 50 caracteres.',

            'ind_batizado.required' => 'Informe se o candidato é batizado.',
            'ind_batizado.boolean' => 'O valor para batizado deve ser válido (sim ou não).',

            'ind_primeira_comunhao.required' => 'Informe se o candidato fez a primeira comunhão.',
            'ind_primeira_comunhao.boolean' => 'O valor para primeira comunhão deve ser válido (sim ou não).',

            'ind_crismado.required' => 'Informe se o candidato é crismado.',
            'ind_crismado.boolean' => 'O valor para crisma deve ser válido (sim ou não).',

            'nom_paroquia.string' => 'O nome da paróquia deve ser um texto.',
            'nom_paroquia.max' => 'O nome da paróquia deve ter no máximo 150 caracteres.',
        ]);
    }
}
