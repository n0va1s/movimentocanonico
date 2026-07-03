<?php

namespace App\Http\Requests;

use App\Models\Ficha;
use App\Rules\Cpf;
use App\Rules\Telefone;
use Illuminate\Validation\Rule;

class FichaEccRequest extends FichaRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $mergeData = [];

        if ($this->has('num_cpf_conjuge')) {
            $mergeData['num_cpf_conjuge'] = $this->input('num_cpf_conjuge') ? preg_replace('/\D/', '', $this->input('num_cpf_conjuge')) : null;
        }

        if ($this->has('filhos') && is_array($this->input('filhos'))) {
            $filhos = $this->input('filhos');
            foreach ($filhos as $index => $filho) {
                if (isset($filho['num_cpf_filho'])) {
                    $filhos[$index]['num_cpf_filho'] = $filho['num_cpf_filho'] ? preg_replace('/\D/', '', $filho['num_cpf_filho']) : null;
                }
            }
            $mergeData['filhos'] = $filhos;
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    public function rules(): array
    {
        $fichaId = $this->route('ficha') ?? $this->route('ecc') ?? $this->route('sgm') ?? $this->route('vem') ?? $this->ficha;
        if ($fichaId instanceof Ficha) {
            $fichaId = $fichaId->idt_ficha;
        }

        return array_merge(parent::rules(), [

            // ── Foto do cônjuge (upload) ──────────────────────────────────────
            'med_conjuge' => 'nullable|image|max:4096',

            // ── Cônjuge (tabela ficha_ecc) ────────────────────────────────────
            'num_cpf_conjuge' => [
                'required',
                'string',
                'max:20',
                new Cpf,
                function ($attribute, $value, $fail) use ($fichaId) {
                    if (!$value) return;

                    $eventoId = $this->input('idt_evento');
                    if (!$eventoId) return;

                    $evento = \App\Models\Evento::find($eventoId);
                    if (!$evento) return;

                    $exists = \App\Models\FichaEcc::where('num_cpf_conjuge', $value)
                        ->where('idt_ficha', '!=', $fichaId ?? 0)
                        ->whereHas('ficha', function ($query) use ($evento) {
                            $query->where('tip_situacao', '!=', \App\Enums\TipoSituacao::CANCELADA)
                                  ->whereHas('evento', function ($q) use ($evento) {
                                      $q->where('idt_movimento', $evento->idt_movimento);
                                  });
                        })
                        ->exists();

                    if ($exists) {
                        $fail($this->messages()['num_cpf_conjuge.unique'] ?? 'Este CPF de cônjuge já está cadastrado em uma ficha ativa neste movimento.');
                    }
                },
            ],
            'nom_conjuge' => 'required|string|max:255',
            'nom_apelido_conjuge' => 'nullable|string|max:100',
            'tip_genero_conjuge' => 'required|string|max:3',
            'dat_nascimento_conjuge' => 'required|date',
            'tel_conjuge' => ['nullable', new Telefone],
            'eml_conjuge' => 'nullable|email|max:255',
            'nom_profissao_conjuge' => 'nullable|string|max:150',
            'ind_catolico_conjuge' => 'nullable|boolean',
            'tip_habilidade_conjuge' => 'nullable|string|max:3',
            'tam_camiseta_conjuge' => 'required|string|max:3',

            // ── Informações comuns do casal (tabela ficha_ecc) ───────────────
            'tip_estado_civil' => 'required|string|max:3',
            'nom_paroquia' => 'nullable|string|max:150',
            'dat_casamento' => 'nullable|date',
            'qtd_filhos' => 'nullable|integer|min:0|max:20',

            // ── Filhos (array dinâmico → tabela ficha_ecc_filho) ─────────────
            'filhos' => 'nullable|array|max:20',
            'filhos.*.num_cpf_filho' => [
                'nullable',
                'string',
                'max:20',
                new Cpf,
            ],
            'filhos.*.nom_filho' => 'nullable|string|max:255',
            'filhos.*.dat_nascimento_filho' => 'nullable|date',
            'filhos.*.eml_filho' => 'nullable|email|max:255',
            'filhos.*.tel_filho' => ['nullable', new Telefone],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            // ── Foto do Cônjuge ──
            'med_conjuge.image' => 'O arquivo da foto do cônjuge deve ser uma imagem.',
            'med_conjuge.max' => 'A foto do cônjuge não pode ultrapassar 4MB.',

            'num_cpf_conjuge.required' => 'O CPF do cônjuge é obrigatório.',
            'num_cpf_conjuge.string' => 'O CPF do cônjuge deve ser um texto.',
            'num_cpf_conjuge.max' => 'O CPF do cônjuge deve ter no máximo 20 caracteres.',
            'num_cpf_conjuge.unique' => 'Este CPF de cônjuge já está cadastrado.',

            'nom_conjuge.required' => 'O nome do cônjuge é obrigatório.',
            'nom_conjuge.string' => 'O nome do cônjuge deve ser um texto.',
            'nom_conjuge.max' => 'O nome do cônjuge não pode exceder 255 caracteres.',

            'nom_apelido_conjuge.string' => 'O apelido do cônjuge deve ser um texto.',
            'nom_apelido_conjuge.max' => 'O apelido do cônjuge não pode exceder 100 caracteres.',

            'tip_genero_conjuge.required' => 'O gênero do cônjuge é obrigatório.',
            'tip_genero_conjuge.string' => 'O gênero do cônjuge deve ser um texto.',
            'tip_genero_conjuge.max' => 'O gênero do cônjuge deve ter no máximo 3 caracteres.',

            'dat_nascimento_conjuge.required' => 'A data de nascimento do cônjuge é obrigatória.',
            'dat_nascimento_conjuge.date' => 'A data de nascimento do cônjuge deve ser uma data válida.',

            'tel_conjuge.string' => 'O telefone do cônjuge deve ser um texto.',
            'tel_conjuge.max' => 'O telefone do cônjuge não pode exceder 20 caracteres.',

            'eml_conjuge.email' => 'Informe um e-mail válido para o cônjuge.',
            'eml_conjuge.max' => 'O e-mail do cônjuge não pode exceder 255 caracteres.',

            'nom_profissao_conjuge.string' => 'A profissão do cônjuge deve ser um texto.',
            'nom_profissao_conjuge.max' => 'A profissão do cônjuge não pode exceder 150 caracteres.',

            'ind_catolico_conjuge.boolean' => 'O campo sobre religião católica do cônjuge deve ser sim ou não.',

            'tip_habilidade_conjuge.string' => 'A habilidade do cônjuge deve ser um texto.',
            'tip_habilidade_conjuge.max' => 'A habilidade do cônjuge deve ter no máximo 3 caracteres.',

            'tam_camiseta_conjuge.required' => 'O tamanho da camiseta do cônjuge é obrigatório.',
            'tam_camiseta_conjuge.string' => 'O tamanho da camiseta do cônjuge deve ser um texto.',
            'tam_camiseta_conjuge.max' => 'O tamanho da camiseta do cônjuge deve ter no máximo 3 caracteres.',

            // ── Informações Comuns do Casal ──
            'tip_estado_civil.required' => 'O estado civil é obrigatório.',
            'tip_estado_civil.string' => 'O estado civil deve ser um texto.',
            'tip_estado_civil.max' => 'O estado civil deve ter no máximo 3 caracteres.',

            'nom_paroquia.string' => 'O nome da paróquia deve ser um texto.',
            'nom_paroquia.max' => 'O nome da paróquia não pode exceder 150 caracteres.',

            'dat_casamento.date' => 'A data do casamento deve ser uma data válida.',

            'qtd_filhos.integer' => 'A quantidade de filhos deve ser um número inteiro.',
            'qtd_filhos.min' => 'A quantidade de filhos não pode ser negativa.',
            'qtd_filhos.max' => 'O limite máximo para o campo filhos é de 20.',

            // ── Filhos (Array Dinâmico) ──
            'filhos.array' => 'O formato dos dados de filhos é inválido.',
            'filhos.max' => 'É permitido cadastrar no máximo 20 filhos.',

            'filhos.*.num_cpf_filho.string' => 'O CPF do filho deve ser um texto.',
            'filhos.*.num_cpf_filho.max' => 'O CPF do filho não pode exceder 14 caracteres.',

            'filhos.*.nom_filho.string' => 'O nome do filho deve ser um texto.',
            'filhos.*.nom_filho.max' => 'O nome do filho não pode exceder 255 caracteres.',

            'filhos.*.dat_nascimento_filho.date' => 'A data de nascimento do filho deve ser uma data válida.',

            'filhos.*.eml_filho.email' => 'O e-mail informado para o filho é inválido.',
            'filhos.*.eml_filho.max' => 'O e-mail do filho não pode exceder 255 caracteres.',

            'filhos.*.tel_filho.string' => 'O telefone do filho deve ser um texto.',
            'filhos.*.tel_filho.max' => 'O telefone do filho não pode exceder 20 caracteres.',
        ]);
    }
}
