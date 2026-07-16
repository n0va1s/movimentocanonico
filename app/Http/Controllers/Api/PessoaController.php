<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PessoaResource;
use App\Models\Evento;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PessoaController extends Controller
{
    /**
     * Lista todas as pessoas vinculadas a um evento específico (trabalhador ou participante).
     * Suporta filtros incrementais de updated_at por meio de 'data_inicio' e 'data_fim'.
     * Utiliza paginação e Route Model Binding.
     *
     * @param Request $request
     * @param Evento $evento
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, Evento $evento): AnonymousResourceCollection
    {
        $request->validate([
            'data_inicio' => 'nullable|date',
            'data_fim' => 'nullable|date',
        ]);

        $query = Pessoa::where(function ($q) use ($evento) {
            $q->whereHas('trabalhadores', function ($sub) use ($evento) {
                $sub->where('idt_evento', $evento->idt_evento);
            })->orWhereHas('participantes', function ($sub) use ($evento) {
                $sub->where('idt_evento', $evento->idt_evento);
            });
        })
        ->with([
            'trabalhadores' => function ($q) use ($evento) {
                $q->where('idt_evento', $evento->idt_evento)->with(['equipe']);
            },
            'participantes' => function ($q) use ($evento) {
                $q->where('idt_evento', $evento->idt_evento);
            }
        ]);

        if ($request->filled('data_inicio')) {
            $query->where('pessoa.updated_at', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->where('pessoa.updated_at', '<=', $request->input('data_fim'));
        }

        return PessoaResource::collection($query->paginate(15));
    }

    /**
     * Busca os dados de uma única pessoa e verifica se ela possui vínculo no evento.
     * Utiliza Route Model Binding.
     *
     * @param Evento $evento
     * @param Pessoa $pessoa
     * @return PessoaResource|JsonResponse
     */
    public function show(Evento $evento, Pessoa $pessoa): PessoaResource|JsonResponse
    {
        $temVinculo = $pessoa->trabalhadores()->where('idt_evento', $evento->idt_evento)->exists() 
                   || $pessoa->participantes()->where('idt_evento', $evento->idt_evento)->exists();

        if (!$temVinculo) {
            return response()->json([
                'mensagem' => 'Pessoa não encontrada ou sem vínculo com o evento informado.'
            ], 404);
        }

        $pessoa->load([
            'trabalhadores' => function ($q) use ($evento) {
                $q->where('idt_evento', $evento->idt_evento)->with(['equipe']);
            },
            'participantes' => function ($q) use ($evento) {
                $q->where('idt_evento', $evento->idt_evento);
            }
        ]);

        return new PessoaResource($pessoa);
    }
}
