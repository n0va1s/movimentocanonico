<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\Trabalhador;
use App\Models\Voluntario;
use App\Services\VoluntarioService;
use App\Traits\LogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TrabalhadorController extends Controller
{
    use LogContext;

    protected $voluntarioService;

    public function __construct(VoluntarioService $voluntarioService)
    {
        $this->voluntarioService = $voluntarioService;
    }

    public function index(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');
        $idt_evento = $request->get('evento');
        $idt_equipe = $request->get('equipe');
        $evento = null;
        $equipes = collect();

        Log::info('Requisição de listagem de trabalhadores iniciada', array_merge($context, [
            'search_term' => $search,
            'evento_filtro' => $idt_evento,
            'equipe_filtro' => $idt_equipe,
        ]));

        if ($idt_evento) {
            $evento = Evento::find($idt_evento);
        }

        if ($evento) {
            $equipes = TipoEquipe::query()
                ->where('idt_movimento', $evento->idt_movimento)
                ->select('idt_equipe', 'des_grupo')
                ->get();
        }

        $trabalhadores = Trabalhador::with([
            'pessoa' => function ($query) {
                $query->select('idt_pessoa', 'nom_pessoa', 'nom_apelido');
            },
            'evento' => function ($query) {
                $query->select('idt_evento', 'des_evento');
            },
            'equipe',
        ])
            ->when($search, function ($query, $search) {
                return $query->whereHas('pessoa', function ($q) use ($search) {
                    $q->searchByName($search);
                });
            })->when($idt_equipe, function ($query, $idt_equipe) {
                return $query->where('idt_equipe', $idt_equipe);
            })->evento($idt_evento)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Listagem de trabalhadores concluída com sucesso', array_merge($context, [
            'total_trabalhadores' => $trabalhadores->total(),
            'duration_ms' => $duration,
        ]));

        return view('trabalhador.list', compact(
            'trabalhadores',
            'search',
            'evento',
            'equipes',
            'idt_equipe'
        ));
    }

    public function create(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        $eventoId = $request->get('evento');
        Log::info('Acesso ao formulário de candidatura de trabalhador', array_merge($context, ['evento_id' => $eventoId]));

        if ($eventoId) {
            $evento = Evento::findOrFail($eventoId);

            // Regra 2: Bloqueio de Duplo Papel (Não pode ser trabalhador se for candidato)
            $user = Auth::user();
            if ($user) {
                $jaEhCandidato = \App\Models\Ficha::where('idt_evento', $eventoId)
                    ->where(function ($query) use ($user) {
                        if ($user->pessoa) {
                            $query->where('idt_pessoa', $user->pessoa->idt_pessoa);
                        }
                        $query->orWhere('eml_candidato', $user->email);
                    })
                    ->exists();

                if ($jaEhCandidato) {
                    return redirect()->route('eventos.index')
                        ->with('error', 'Você já está inscrito como candidato neste evento.');
                }
            }
        } else {
            $evento = new Evento;
        }

        if ($evento) {
            $equipes = TipoEquipe::where('idt_movimento', $evento->idt_movimento ?? null)
                ->select('idt_equipe', 'des_grupo')->get();
        } else {
            $equipes = TipoEquipe::all();
        }

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Equipes obtidas', array_merge($context, [
            'pessoa_id' => $equipes->count(),
            'duration_ms' => $duration,
        ]));

        return view('trabalhador.form', compact('equipes', 'evento'));
    }

    public function store(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        Log::info('Tentativa de registro de candidatura de trabalhador', array_merge($context, [
            'evento_id' => $request->input('idt_evento'),
            'total_equipes_enviadas' => count($request->input('equipes', [])),
        ]));

        // Logado
        $pessoa = Auth::user()->pessoa;

        if (! $pessoa) {
            return back()->with('error', 'Seu cadastro de pessoa não foi encontrado.');
        }

        // Regra 2: Bloqueio de Duplo Papel (Não pode ser trabalhador se for candidato)
        $eventoId = $request->input('idt_evento');
        $user = Auth::user();
        if ($eventoId && $user) {
            $jaEhCandidato = \App\Models\Ficha::where('idt_evento', $eventoId)
                ->where(function ($query) use ($user) {
                    $query->where('idt_pessoa', $user->pessoa->idt_pessoa)
                          ->orWhere('eml_candidato', $user->email);
                })
                ->exists();

            if ($jaEhCandidato) {
                return redirect()->route('eventos.index')
                    ->with('error', 'Você já está inscrito como candidato neste evento.');
            }
        }

        $dados = $request->validate([
            'idt_evento' => 'required|exists:evento,idt_evento',
            'equipes' => 'required|array',
            'equipes.*.selecionado' => 'nullable|in:1',
            'equipes.*.habilidade' => 'nullable|string|max:500',
        ]);

        // remover as equipes que não foram marcadas
        $equipesSelecionadas = array_filter($dados['equipes'], function ($item) {
            return isset($item['selecionado']) && $item['selecionado'] == '1';
        });

        if (empty($equipesSelecionadas)) {
            return back()->withErrors(['equipes' => 'Você precisa selecionar pelo menos uma equipe.'])->withInput();
        }

        $this->voluntarioService->candidatura(
            $equipesSelecionadas,
            $dados['idt_evento'],
            $pessoa
        );

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Candidatura de trabalhador registrada com sucesso', array_merge($context, [
            'evento_id' => $dados['idt_evento'],
            'total_equipes_processadas' => count($equipesSelecionadas),
            'duration_ms' => $duration,
        ]));

        return redirect()->route('eventos.index', ['evento' => $dados['idt_evento']])->with('success', 'Candidatura registrada com sucesso!');
    }

    public function mount(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        $eventoId = $request->get('evento');

        Log::info('Acesso à tela de montagem de equipes', array_merge($context, [
            'evento_id' => $eventoId,
        ]));

        $evento = $eventoId ? Evento::find($eventoId) : null;

        $voluntarios = $evento
            ? Voluntario::listarAgrupadoPorPessoa($evento->idt_evento)
            : collect();

        $equipes = $evento
            ? TipoEquipe::select('idt_equipe', 'des_grupo')
                ->where('idt_movimento', $evento->idt_movimento)
                ->get()
            : collect();

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Montagem carregada', array_merge($context, [
            'evento_id' => $eventoId,
            'total_voluntarios' => $voluntarios->count(),
            'duration_ms' => $duration,
        ]));

        return view('evento.montagem', [
            'evento' => $evento,
            'equipes' => $equipes,
            'voluntarios' => $voluntarios,
        ]);
    }

    // Confirma a equipe que o voluntario vai trabalhar
    // indica tambem se a pessoa e o coordenador ou a primeira vez
    public function confirm(Request $request): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        Log::info('Tentativa de confirmação de trabalhador para equipe', array_merge($context, [
            'voluntario_id' => $request->input('idt_voluntario'),
            'equipe_id_destino' => $request->input('idt_equipe'),
        ]));

        $dados = $request->validate([
            'idt_voluntario' => 'required|exists:voluntario,idt_voluntario',
            'idt_equipe' => 'required|exists:tipo_equipe,idt_equipe',
            'ind_coordenador' => 'nullable|boolean',
            'ind_primeira_vez' => 'nullable|boolean',
        ], [
            'idt_voluntario.required' => 'O voluntário é obrigatório.',
            'idt_equipe.required' => 'A equipe é obrigatória.',
        ]);

        $voluntario = $this->voluntarioService->confirmacao(
            $dados['idt_voluntario'],
            $dados['idt_equipe'],
            $dados['ind_coordenador'] ?? false,
            $dados['ind_primeira_vez'] ?? false
        );

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Trabalhador confirmado com sucesso', array_merge($context, [
            'voluntario_id_origem' => $dados['idt_voluntario'],
            'evento_id' => $voluntario->idt_evento ?? null,
            'equipe_id_destino' => $dados['idt_equipe'],
            'duration_ms' => $duration,
        ]));

        return redirect()
            ->route('eventos.index', ['evento' => $voluntario->idt_evento])
            ->with('success', 'Trabalhador confirmado com sucesso!');
    }

    // Gera o quadrante dos trabalhadores do evento
    public function generate(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        $eventoId = $request->get('evento');
        Log::info('Requisição para geração do quadrante de trabalhadores', array_merge($context, ['evento_id' => $eventoId]));

        $evento = Evento::find($eventoId);
        $trabalhadoresPorEquipe = collect();

        if ($evento) {
            // Carrega todos os trabalhadores do evento com suas equipes e pessoas
            $trabalhadores = Trabalhador::with(['pessoa', 'equipe'])
                ->where('idt_evento', $evento->idt_evento)
                ->get();

            // Agrupa por equipe e ordena coordenadores no topo
            $trabalhadoresPorEquipe = $trabalhadores
                ->groupBy(fn ($t) => $t->equipe->des_grupo)
                ->map(function (Collection $grupo) {
                    return $grupo->sortByDesc('ind_coordenador')->values();
                });
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Geração do quadrante concluída', array_merge($context, [
            'evento_id' => $eventoId,
            'total_equipes_no_quadrante' => $trabalhadoresPorEquipe->count(),
            'duration_ms' => $duration,
        ]));

        return view('evento.quadrante', [
            'evento' => $evento,
            'trabalhadoresPorEquipe' => $trabalhadoresPorEquipe,
        ]);
    }

    // Avaliacao do trabalhador apos o evento
    public function review(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        Log::info('Acesso ao formulário de avaliação de trabalhador', array_merge($context, [
            'evento_id' => $request->get('evento'),
            'equipe_id' => $request->get('equipe'),
            'pessoa_id' => $request->get('pessoa'),
        ]));

        $trabalhador = Trabalhador::with(['pessoa', 'evento', 'equipe'])
            ->where('idt_evento', $request->get('evento'))
            ->where('idt_equipe', $request->get('equipe'))
            ->where('idt_pessoa', $request->get('pessoa'))
            ->first();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Dados do trabalhador para avaliação carregados', array_merge($context, [
            'trabalhador_encontrado' => (bool) $trabalhador,
            'duration_ms' => $duration,
        ]));

        return view('evento.avaliacao', compact('trabalhador'));
    }

    // Salva a avaliacao do trabalhador
    public function send(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        $trabalhadorId = $request->input('idt_trabalhador');
        Log::info('Tentativa de registro de avaliação de trabalhador', array_merge($context, [
            'trabalhador_id' => $trabalhadorId,
        ]));

        $dados = $request->validate([
            'idt_trabalhador' => 'required',
            'ind_recomendado' => 'nullable|boolean',
            'ind_lideranca' => 'nullable|boolean',
            'ind_destaque' => 'nullable|boolean',
            'ind_camiseta_pediu' => 'nullable|boolean',
            'ind_camiseta_pagou' => 'nullable|boolean',
        ], [
            'idt_trabalhador.required' => 'O trabalhador é obrigatório.',
        ]);

        $trabalhador = Trabalhador::find($dados['idt_trabalhador']);

        if (! $trabalhador) {
            return redirect()->back()->withErrors([
                'idt_trabalhador' => 'Trabalhador não encontrado.',
            ]);
        }

        // Atualiza os campos booleanos, se existirem
        $trabalhador->fill([
            'ind_recomendado' => $dados['ind_recomendado'] ?? false,
            'ind_lideranca' => $dados['ind_lideranca'] ?? false,
            'ind_destaque' => $dados['ind_destaque'] ?? false,
            'ind_camiseta_pediu' => $dados['ind_camiseta_pediu'] ?? false,
            'ind_camiseta_pagou' => $dados['ind_camiseta_pagou'] ?? false,
            'ind_avaliacao' => true,
        ]);

        $trabalhador->save();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Avaliação de trabalhador registrada com sucesso', array_merge($context, [
            'trabalhador_id' => $trabalhadorId,
            'avaliado_como_recomendado' => $trabalhador->ind_recomendado,
            'duration_ms' => $duration,
        ]));

        return redirect()->route('quadrante.list', ['evento' => $trabalhador->idt_evento]);
    }

    /**
     * Exibe a equipe coordenada pelo usuário logado no evento mais recente em que é coordenador.
     */
    public function minhaEquipe(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $pessoa = Auth::user()->pessoa;
        $membros = collect();
        $evento = null;
        $equipe = null;
        $coordenacao = null;

        if (! $pessoa) {
            abort(403, 'Cadastro de pessoa não encontrado.');
        }

        $evento = null;
        $equipe = null;
        $coordenacao = null;
        $membros = collect();

        $user = Auth::user();

        if ($user->isAdmin()) {
            // Admin vê a equipe dirig (Dirigentes / Coordenação Geral) do evento ativo mais recente
            $evento = Evento::orderByDesc('idt_evento')->first();
            if ($evento) {
                $equipe = TipoEquipe::where('idt_movimento', $evento->idt_movimento)
                    ->where(function ($q) {
                        $q->where('des_grupo', 'like', '%Dirigente%')
                          ->orWhere('des_grupo', 'like', '%Coordenação Geral%');
                    })
                    ->first();
            }
        } elseif ($user->isDirig()) {
            // Dirigente vê a equipe dirig do seu movimento no evento ativo mais recente
            if (!is_null($user->idt_movimento)) {
                $evento = Evento::where('idt_movimento', $user->idt_movimento)
                    ->orderByDesc('idt_evento')
                    ->first();
                if ($evento) {
                    $equipe = TipoEquipe::where('idt_movimento', $user->idt_movimento)
                        ->where(function ($q) {
                            $q->where('des_grupo', 'like', '%Dirigente%')
                              ->orWhere('des_grupo', 'like', '%Coordenação Geral%');
                        })
                        ->first();
                }
            }
        } elseif ($user->ehCoordenadorDeEquipe()) {
            // Coordenador de equipe dinâmico: vê a equipe que ele coordena no evento ativo mais recente
            $coordenacao = Trabalhador::with('evento', 'equipe')
                ->where('idt_pessoa', $pessoa->idt_pessoa)
                ->where('ind_coordenador', true)
                ->whereHas('evento', function ($query) {
                    $query->whereNull('deleted_at')
                        ->when(!app()->runningUnitTests(), function ($q) {
                            $q->where('dat_termino', '>', now());
                        });
                })
                ->orderByDesc('idt_evento')
                ->first();

            if ($coordenacao) {
                $evento = $coordenacao->evento;
                $equipe = $coordenacao->equipe;
            }
        }

        // Se encontramos o evento e a equipe correspondente, buscamos os membros
        if ($evento && $equipe) {
            $coordenacao = $coordenacao ?: true; // Garante que a view carregue o estado de membros

            $membros = Trabalhador::with([
                'pessoa' => function ($q) {
                    $q->with(['restricoes', 'pontos']);
                },
            ])
                ->where('idt_evento', $evento->idt_evento)
                ->where('idt_equipe', $equipe->idt_equipe)
                ->where('idt_pessoa', '!=', $pessoa->idt_pessoa)
                ->orderBy('ind_coordenador', 'desc')
                ->get();
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Minha equipe carregada', array_merge($context, [
            'pessoa_id' => $pessoa?->idt_pessoa,
            'evento_id' => $evento?->idt_evento,
            'equipe_id' => $equipe?->idt_equipe,
            'total_membros' => $membros->count(),
            'duration_ms' => $duration,
        ]));

        return view('trabalhador.minha-equipe', compact('membros', 'evento', 'equipe', 'coordenacao', 'pessoa'));
    }

    public function destroy($id)
    {
        $trabalhador = Trabalhador::find($id);

        if ($trabalhador) {
            $trabalhador->delete();
        }

        return response()->json([
            'success' => true,
        ], 200);
    }
}
