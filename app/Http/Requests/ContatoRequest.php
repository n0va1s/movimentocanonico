<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContatoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom_contato' => 'required|string|max:255',
            'eml_contato' => 'nullable|email|max:255',
            'tel_contato' => 'required|string|max:20',
            'txt_mensagem' => 'required|string|max:1000',
            'idt_movimento' => 'required|exists:tipo_movimento,idt_movimento',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'nom_contato.required' => 'O nome é obrigatório.',
            'nom_contato.max' => 'O nome não pode ter mais de 255 caracteres.',
            'eml_contato.email' => 'Informe um e-mail válido.',
            'eml_contato.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'tel_contato.required' => 'O telefone é obrigatório.',
            'tel_contato.max' => 'O telefone não pode ter mais de 20 caracteres.',
            'txt_mensagem.required' => 'A mensagem é obrigatória.',
            'txt_mensagem.max' => 'A mensagem não pode ter mais de 1000 caracteres.',
            'idt_movimento.required' => 'O movimento é obrigatório.',
            'idt_movimento.exists' => 'O movimento selecionado não é válido.',
        ];
    }
}
