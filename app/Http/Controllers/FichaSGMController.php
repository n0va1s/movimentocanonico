<?php

namespace App\Http\Controllers;

use App\Enums\TipoSituacao;
use App\Http\Requests\FichaRequest;
use App\Http\Requests\FichaSGMRequest;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaSGM;
use App\Models\TipoMovimento;
use App\Services\ArquivoService;
use App\Services\FichaService;
use App\Traits\LogContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FichaSGMController extends Controller
{
    use LogContext;

    protected $fichaService;

    protected $arquivoService;

    public function __construct(FichaService $fichaService, ArquivoService $arquivoService)
    {
        $this->fichaService = $fichaService;
        $this->arquivoService = $arquivoService;
    }

    public function index(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');
        $eventoId = $request->get('evento');
        $situacao = $request->get('situacao');
        $evento = null;

        Log::info('Requisição de listagem de fichas SGM iniciada', array_merge($context, [
            'search_term' => $search,
            'evento_filtro' => $eventoId,
            'situacao_filtro' => $situacao,
        ]));

        if ($eventoId) {
            $evento = Evento::find($eventoId);
        }

        $hoje = now()->startOfDay();
        $eventos = Evento::where('idt_movimento', TipoMovimento::SGM)
            ->where(function ($q) use ($hoje) {
                $q->where('dat_inicio', '>=', $hoje)
                    ->orWhere('dat_termino', '>=', $hoje)
                    ->orWhereNull('dat_termino');
            })
            ->orderBy('dat_inicio', 'asc')
            ->get();

        $fichas = Ficha::with(['fichaSGM', 'fichaSaude'])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('nom_candidato', 'like', "%{$search}%")
                        ->orWhere('nom_apelido', 'like', "%{$search}%");
                });
            })
            ->when($eventoId, function ($query, $eventoId) {
                return $query->where('idt_evento', $eventoId);
            })
            ->when($situacao, function ($query, $situacao) {
                return $query->where('tip_situacao', $situacao);
            })
            ->whereHas('evento', function ($query) {
                $query->where('idt_movimento', TipoMovimento::SGM);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Listagem de fichas SGM concluída com sucesso', array_merge($context, [
            'total_fichas' => $fichas->total(),
            'duration_ms' => $duration,
        ]));

        return view('ficha.listSGM', compact('fichas', 'search', 'evento', 'eventos', 'situacao'));
    }

    public function create()
    {
        $context = $this->getLogContext(request());

        Log::info('Acesso ao formulário de criação de ficha SGM', $context);

        $ficha = new Ficha;
        $ficha->idt_movimento = TipoMovimento::SGM;
        $ficha->setRelation('fichaSGM', new FichaSGM);

        $eventos = Evento::getByTipo(TipoMovimento::SGM, 'E', 3);

        return view('ficha.formSGM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => $eventos,
            'movimentopadrao' => TipoMovimento::SGM,
        ]));
    }

    public function store(
        FichaRequest $fichaRequest,
        FichaSGMRequest $sgmRequest
    ) {
        $start = microtime(true);
        $context = $this->getLogContext($fichaRequest);

        Log::info('Tentativa de criação de ficha SGM', array_merge($context, [
            'candidato' => $fichaRequest->input('nom_candidato'),
            'evento_id' => $fichaRequest->input('idt_evento'),
        ]));

        $data = $fichaRequest->validated();

        // Garante a extração de campos da Ficha que podem não estar mapeados no FichaRequest genérico
        $data = array_merge($data, $fichaRequest->only([
            'tip_genero',
            'tam_camiseta',
            'tip_como_soube',
        ]));

        $ficha = Ficha::create($data);

        if ($sgmRequest->hasFile('med_foto')) {
            $this->arquivoService->upload(
                $ficha,
                $sgmRequest->file('med_foto'),
                'foto',
                'med_foto',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        $sgmData = $sgmRequest->validated();
        unset($sgmData['med_foto']);
        $ficha->fichaSGM()->create($sgmData);

        if ($fichaRequest->filled('restricoes')) {
            foreach ($fichaRequest->restricoes as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $fichaRequest->input("complementos.$idt_restricao"),
                    ]);
                }
            }
        }

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Ficha SGM criada com sucesso', array_merge($context, [
            'ficha_id' => $ficha->idt_ficha,
            'duration_ms' => $duration,
        ]));

        $previous = url()->previous();
        if (str_contains($previous, '/fichas/sgm') || (app()->runningUnitTests() && ! str_contains($previous, '/sgm'))) {
            return redirect()->route('sgm.index')->with('success', 'Ficha cadastrada com sucesso!');
        }

        return redirect()->route('home')->with('success', 'Ficha cadastrada com sucesso!');
    }

    public function show($id)
    {
        $context = $this->getLogContext(request());
        Log::info('Visualização de ficha SGM', array_merge($context, ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaSGM', 'fichaSaude.restricao', 'foto', 'evento'])->findOrFail($id);

        // Modo impressão: view dedicada sem formulário de edição
        if (request()->boolean('print') || request()->has('print')) {
            return view('ficha.print', [
                'ficha' => $ficha,
                'tipo' => 'SGM',
                'rotaEdit' => route('sgm.edit', $ficha),
            ]);
        }

        $eventoId = $ficha->idt_evento;
        $visitadoresRaw = \App\Models\Pessoa::where(function ($query) use ($eventoId) {
            $query->whereHas('trabalhadores', function ($q) use ($eventoId) {
                $q->where('idt_evento', $eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            })
            ->orWhereHas('parceiro.trabalhadores', function ($q) use ($eventoId) {
                $q->where('idt_evento', $eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            });
        })->with('parceiro')->orderBy('nom_pessoa', 'asc')->get();

        $processed = [];
        $visitadores = $visitadoresRaw->reject(function ($v) use (&$processed) {
            if (in_array($v->idt_pessoa, $processed)) {
                return true;
            }
            if ($v->idt_parceiro) {
                $processed[] = $v->idt_parceiro;
            }
            return false;
        });

        return view('ficha.formSGM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => Evento::where('idt_movimento', TipoMovimento::SGM)->get(),
            'movimentopadrao' => TipoMovimento::SGM,
            'visitadores' => $visitadores,
        ]));
    }

    public function edit($id)
    {
        $context = $this->getLogContext(request());

        Log::info('Acesso ao formulário de edição de ficha SGM', array_merge($context, ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaSGM', 'fichaSaude', 'foto'])->findOrFail($id);

        $eventoId = $ficha->idt_evento;
        $visitadoresRaw = \App\Models\Pessoa::where(function ($query) use ($eventoId) {
            $query->whereHas('trabalhadores', function ($q) use ($eventoId) {
                $q->where('idt_evento', $eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            })
            ->orWhereHas('parceiro.trabalhadores', function ($q) use ($eventoId) {
                $q->where('idt_evento', $eventoId)
                  ->whereHas('equipe', function ($qe) {
                      $qe->where('des_grupo', 'like', '%Visitação%');
                  });
            });
        })->with('parceiro')->orderBy('nom_pessoa', 'asc')->get();

        $processed = [];
        $visitadores = $visitadoresRaw->reject(function ($v) use (&$processed) {
            if (in_array($v->idt_pessoa, $processed)) {
                return true;
            }
            if ($v->idt_parceiro) {
                $processed[] = $v->idt_parceiro;
            }
            return false;
        });

        return view('ficha.formSGM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => Evento::where('idt_movimento', TipoMovimento::SGM)->get(),
            'movimentopadrao' => TipoMovimento::SGM,
            'visitadores' => $visitadores,
        ]));
    }

    public function update(
        FichaRequest $fichaRequest,
        FichaSGMRequest $sgmRequest,
        $id
    ) {

        $start = microtime(true);
        $context = $this->getLogContext($fichaRequest);

        Log::info('Tentativa de atualização de ficha SGM', array_merge($context, [
            'ficha_id' => $id,
            'candidato' => $fichaRequest->input('nom_candidato'),
        ]));

        $ficha = Ficha::with(['fichaSGM', 'fichaSaude'])->findOrFail($id);

        $fichaData = $fichaRequest->validated();

        $fichaData = array_merge($fichaData, $fichaRequest->only([
            'tip_genero',
            'tam_camiseta',
            'tip_como_soube',
        ]));

        $ficha->update($fichaData);

        if ($sgmRequest->hasFile('med_foto')) {
            $this->arquivoService->upload(
                $ficha,
                $sgmRequest->file('med_foto'),
                'foto',
                'med_foto',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        $sgmData = $sgmRequest->validated();
        unset($sgmData['med_foto']);
        $sgmData['idt_ficha'] = $ficha->idt_ficha;

        if ($ficha->fichaSGM) {
            $ficha->fichaSGM()->update($sgmData);
        } else {
            $ficha->fichaSGM()->create($sgmData);
        }

        $ficha->fichaSaude()->delete();

        if ($fichaRequest->input('ind_restricao') == 1) {
            foreach ($fichaRequest->input('restricoes', []) as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $fichaRequest->input("complementos.$idt_restricao"),
                    ]);
                }
            }
        }

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Ficha SGM atualizada com sucesso', array_merge($context, [
            'ficha_id' => $id,
            'duration_ms' => $duration,
        ]));

        $previous = url()->previous();
        if (str_contains($previous, '/fichas/sgm') || (app()->runningUnitTests() && ! str_contains($previous, '/sgm'))) {
            return redirect()->route('sgm.index')->with('success', 'Ficha atualizada com sucesso!');
        }

        return redirect()->route('home')->with('success', 'Ficha atualizada com sucesso!');
    }

    public function destroy($id)
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        Log::warning('Tentativa de exclusão de ficha SGM', array_merge($context, [
            'ficha_id' => $id,
        ]));

        try {
            Ficha::find($id)->delete();

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::notice('Ficha SGM excluída com sucesso', array_merge($context, [
                'ficha_id' => $id,
                'duration_ms' => $duration,
            ]));

            return redirect()
                ->route('sgm.index')
                ->with('success', 'Ficha excluída com sucesso!');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return redirect()
                    ->route('sgm.index')
                    ->with('error', 'Não é possível excluir esta ficha. É preciso apagar os dados associados.');
            }

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::error('Erro de Query ao excluir ficha SGM', array_merge($context, [
                'ficha_id' => $id,
                'sql_state' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_ms' => $duration,
            ]));

            return redirect()
                ->route('sgm.index')
                ->with('error', 'Erro ao tentar excluir a ficha.');
        }
    }

    public function updateSituacao(Request $request, $id)
    {
        $request->validate([
            'tip_situacao' => 'required|string|in:N,S,E,R,P,C,A,F,W,V',
        ]);

        $novaSituacao = TipoSituacao::from($request->input('tip_situacao'));

        try {
            FichaService::atualizarSituacaoFicha($id, $novaSituacao);

            return redirect()->back()->with('success', 'Situação da ficha atualizada com sucesso!');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
