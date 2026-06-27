<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExportacaoDadosController extends Controller
{
    /**
     * Lista todas as pessoas vinculadas a um evento específico (trabalhador ou participante).
     * Suporta filtros incrementais de updated_at por meio de 'data_inicio' e 'data_fim'.
     *
     * @param Request $request
     * @param mixed $id_evento
     * @return JsonResponse
     */
    public function index(Request $request, mixed $id_evento): JsonResponse
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
        ]);

        $query = Pessoa::where(function ($q) use ($id_evento) {
            $q->whereHas('trabalhadores', function ($sub) use ($id_evento) {
                $sub->where('idt_evento', $id_evento);
            })->orWhereHas('participantes', function ($sub) use ($id_evento) {
                $sub->where('idt_evento', $id_evento);
            });
        })
        ->with([
            'trabalhadores' => function ($q) use ($id_evento) {
                $q->where('idt_evento', $id_evento)->with(['evento', 'equipe']);
            },
            'participantes' => function ($q) use ($id_evento) {
                $q->where('idt_evento', $id_evento)->with(['evento']);
            }
        ]);

        if ($request->filled('data_inicio')) {
            $query->where('pessoa.updated_at', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->where('pessoa.updated_at', '<=', $request->input('data_fim'));
        }

        $pessoas = $query->get();

        $resultado = $pessoas->map(fn (Pessoa $pessoa) => $this->formatarPessoa($pessoa, (int) $id_evento));

        return response()->json([
            'sucesso' => true,
            'total' => $resultado->count(),
            'dados' => $resultado,
        ], 200);
    }

    /**
     * Busca os dados de uma única pessoa e verifica se ela possui vínculo no evento.
     *
     * @param mixed $id_evento
     * @param mixed $id_pessoa
     * @return JsonResponse
     */
    public function show(mixed $id_evento, mixed $id_pessoa): JsonResponse
    {
        $pessoa = Pessoa::where('idt_pessoa', $id_pessoa)
            ->where(function ($q) use ($id_evento) {
                $q->whereHas('trabalhadores', function ($sub) use ($id_evento) {
                    $sub->where('idt_evento', $id_evento);
                })->orWhereHas('participantes', function ($sub) use ($id_evento) {
                    $sub->where('idt_evento', $id_evento);
                });
            })
            ->with([
                'trabalhadores' => function ($q) use ($id_evento) {
                    $q->where('idt_evento', $id_evento)->with(['evento', 'equipe']);
                },
                'participantes' => function ($q) use ($id_evento) {
                    $q->where('idt_evento', $id_evento)->with(['evento']);
                }
            ])
            ->first();

        if (!$pessoa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Pessoa não encontrada ou sem vínculo com o evento informado.'
            ], 404);
        }

        return response()->json($this->formatarPessoa($pessoa, (int) $id_evento), 200);
    }

    /**
     * Converte e formata os dados de Pessoa para a estrutura JSON exigida pelo sistema externo.
     *
     * @param Pessoa $pessoa
     * @param int $id_evento
     * @return array
     */
    private function formatarPessoa(Pessoa $pessoa, int $id_evento): array
    {
        $trabalhador = $pessoa->trabalhadores->first();
        $participante = $pessoa->participantes->first();

        $perfil = null;
        $equipe = null;
        $corTroca = null;

        if ($trabalhador) {
            $perfil = 'trabalhador';
            $equipe = $trabalhador->equipe?->des_grupo;
        } elseif ($participante) {
            $perfil = 'participante';
            $corTroca = $participante->tip_cor_troca;
        }

        return [
            'id_pessoa' => $pessoa->idt_pessoa,
            'nome' => $pessoa->nom_pessoa,
            'apelido' => $pessoa->nom_apelido,
            'cpf' => $pessoa->num_cpf_pessoa,
            'telefone' => $pessoa->tel_pessoa,
            'email' => $pessoa->eml_pessoa,
            'data_nascimento' => $pessoa->getDataNascimentoFormatada(),
            'sexo' => $pessoa->tip_genero?->value ?? $pessoa->tip_genero,
            'endereco' => $pessoa->des_endereco,
            'contexto_evento' => [
                'id_evento' => $id_evento,
                'perfil' => $perfil,
                'equipe' => $equipe,
                'cor_troca' => $corTroca,
            ]
        ];
    }
}
