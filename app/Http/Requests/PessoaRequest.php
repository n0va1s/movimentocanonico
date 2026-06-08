<?php

namespace App\Http\Requests;

use App\Enums\EstadoCivil;
use App\Enums\Genero;
use App\Enums\HabilidadePrincipal;
use App\Enums\TamanhoCamiseta;
use App\Models\Pessoa;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class PessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('num_cpf_pessoa')) {
            $this->merge([
                'num_cpf_pessoa' => $this->input('num_cpf_pessoa') ? preg_replace('/\D/', '', $this->input('num_cpf_pessoa')) : null,
            ]);
        }
    }

    public function rules(): array
    {
        $pessoaId = $this->route('pessoa') ?? $this->pessoa;
        if ($pessoaId instanceof Pessoa) {
            $pessoaId = $pessoaId->idt_pessoa;
        }

        return [
            'num_cpf_pessoa' => [
                'required',
                'string',
                'max:20',
                new Cpf,
                Rule::unique('pessoa', 'num_cpf_pessoa')->ignore($pessoaId, 'idt_pessoa'),
            ],
            'nom_pessoa' => ['required', 'string', 'max:255'],
            'nom_apelido' => ['nullable', 'string', 'max:255'],
            'tel_pessoa' => ['nullable', 'regex:/^\(?\d{2}\)?[\s-]?\d{4,5}-?\d{4}$/'],
            'dat_nascimento' => ['required', 'date', 'before:today', 'after:1925-01-01'],
            'des_endereco' => ['nullable', 'string', 'min:10', 'max:255'],
            'eml_pessoa' => ['required', 'email', 'max:255'],
            'tam_camiseta' => ['required', new Enum(TamanhoCamiseta::class)],
            'tip_genero' => ['required', new Enum(Genero::class)],
            'tip_estado_civil' => ['nullable', new Enum(EstadoCivil::class)],
            'tip_habilidade' => ['nullable', new Enum(HabilidadePrincipal::class)],
            'idt_parceiro' => ['nullable', 'exists:pessoa,idt_pessoa'],
            'med_foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'ind_restricao' => ['nullable', 'boolean'],
            'nom_profissao' => ['nullable', 'string', 'max:255'],
            'restricoes' => ['nullable', 'array'],
            'restricoes.*' => ['boolean'],
            'complementos' => ['nullable', 'array'],
            'complementos.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            // CPF
            'num_cpf_pessoa.required' => 'O CPF é obrigatório.',
            'num_cpf_pessoa.string' => 'O CPF não precisa de pontos ou traços.',
            'num_cpf_pessoa.max' => 'O CPF não pode ter mais de 20 caracteres.',
            'num_cpf_pessoa.unique' => 'Este CPF já está cadastrado.',

            // Nome e Apelido
            'nom_pessoa.required' => 'O nome da pessoa é obrigatório.',
            'nom_pessoa.max' => 'O nome da pessoa não pode ter mais de 255 caracteres.',
            'nom_apelido.max' => 'O apelido não pode ter mais de 255 caracteres.',

            // Contato
            'tel_pessoa.regex' => 'O telefone deve estar em um formato válido. Ex: (99) 99999-9999.',
            'eml_pessoa.required' => 'O e-mail é obrigatório.',
            'eml_pessoa.email' => 'Informe um e-mail válido.',
            'eml_pessoa.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'eml_pessoa.unique' => 'Este e-mail já está sendo utilizado.',

            // Nascimento e Endereço
            'dat_nascimento.required' => 'A data de nascimento é obrigatória.',
            'dat_nascimento.date' => 'A data de nascimento deve ser uma data válida.',
            'dat_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'dat_nascimento.after' => 'A data de nascimento deve ser posterior a 01/01/1925.',
            'des_endereco.min' => 'O endereço deve conter pelo menos 10 caracteres.',
            'des_endereco.max' => 'O endereço não pode ter mais de 255 caracteres.',

            // Enums (Usando a sintaxe genérica 'enum' para simplificar)
            'tam_camiseta.required' => 'Selecione o tamanho da camiseta.',
            'tam_camiseta.enum' => 'O tamanho da camiseta selecionado é inválido.',
            'tip_genero.required' => 'Informe o gênero.',
            'tip_genero.enum' => 'O gênero selecionado é inválido.',
            'tip_estado_civil.enum' => 'O estado civil selecionado é inválido.',
            'tip_habilidade.enum' => 'A habilidade selecionada é inválida.',

            // Relacionamentos e Outros
            'idt_parceiro.exists' => 'O parceiro selecionado não existe na nossa base de dados.',
            'nom_profissao.max' => 'A profissão não pode ter mais de 255 caracteres.',
            'ind_restricao.boolean' => 'O campo de restrição deve ser verdadeiro ou falso.',

            // Foto
            'med_foto.image' => 'O arquivo deve ser uma imagem.',
            'med_foto.mimes' => 'A imagem deve estar nos formatos: jpeg, png, jpg, webp.',
            'med_foto.max' => 'A imagem não pode ser maior que 5MB.',
            'med_foto.uploaded' => 'A foto não pôde ser enviada. Verifique se o tamanho não excede o limite do servidor (normalmente 2MB) ou tente uma imagem menor.',

            // Arrays (Restrições e Complementos)
            'restricoes.array' => 'O formato das restrições é inválido.',
            'restricoes.*.boolean' => 'Cada restrição deve ser um valor booleano.',
            'complementos.array' => 'O formato dos complementos é inválido.',
            'complementos.*.string' => 'Cada complemento deve ser um texto.',
            'complementos.*.max' => 'Cada complemento não pode exceder 255 caracteres.',
        ];
    }
}
