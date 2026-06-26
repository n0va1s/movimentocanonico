<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;

class MercadinhoController extends Controller
{
    /**
     * Busca uma pessoa pelo ID e retorna seus dados formatados e vínculos com eventos.
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function buscarPessoa(mixed $id): JsonResponse
    {
        // 1 & 2. Busca com eager loading para evitar N+1
        $pessoa = Pessoa::with([
            'trabalhadores.evento',
            'trabalhadores.equipe',
            'participantes.evento'
        ])->find($id);

        // 3. Retorna 404 caso não seja encontrado
        if (!$pessoa) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Pessoa não encontrada no banco de dados.'
            ], 404);
        }

        // Mapeia e unifica os vínculos de eventos (participantes e trabalhadores)
        $vinculosEventos = [];

        // Adiciona vínculos como participante
        foreach ($pessoa->participantes as $participante) {
            $vinculosEventos[] = [
                'id_evento' => $participante->idt_evento,
                'nome_evento' => $participante->evento?->des_evento,
                'perfil' => 'participante',
                'equipe' => null,
                'cor_troca' => $participante->tip_cor_troca,
            ];
        }

        // Adiciona vínculos como trabalhador
        foreach ($pessoa->trabalhadores as $trabalhador) {
            $vinculosEventos[] = [
                'id_evento' => $trabalhador->idt_evento,
                'nome_evento' => $trabalhador->evento?->des_evento,
                'perfil' => 'trabalhador',
                // A relação equipe mapeia para a model TipoEquipe, que usa des_grupo como nome do grupo/equipe
                'equipe' => $trabalhador->equipe?->des_grupo,
                'cor_troca' => null,
            ];
        }

        // Retorna os dados formatados conforme a estrutura plana solicitada
        return response()->json([
            'sucesso' => true,
            'cliente' => [
                'id_pessoa' => $pessoa->idt_pessoa,
                'nome' => $pessoa->nom_pessoa,
                'apelido' => $pessoa->nom_apelido,
                'cpf' => $pessoa->num_cpf_pessoa,
                'telefone' => $pessoa->tel_pessoa,
                'email' => $pessoa->eml_pessoa,
                'data_nascimento' => $pessoa->getDataNascimentoFormatada(),
                'sexo' => $pessoa->tip_genero?->value ?? $pessoa->tip_genero,
                'endereco' => $pessoa->des_endereco,
            ],
            'vinculos_eventos' => $vinculosEventos
        ], 200);
    }
}
