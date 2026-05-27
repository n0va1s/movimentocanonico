<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventoRequest;
use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Services\ArquivoService;
use App\Services\EventoService;
use App\Traits\LogContext;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventoController extends Controller
{
    use LogContext;

    public function __construct(
        protected EventoService $eventoService,
        protected ArquivoService $arquivoService
    ) {}

    public function index(Request $request): View
    {
        $context = $this->getLogContext($request);
        $pessoa = Auth::user()->pessoa;

        $eventos = Evento::query()
            ->with(['movimento:idt_movimento,des_sigla'])
            ->when($pessoa, function ($q) use ($pessoa) {
                $q->withExists([
                    'participantes as ja_inscrito_participante' => fn ($q) => $q
                        ->where('idt_pessoa', $pessoa->idt_pessoa),
                ])
                    ->withExists([
                        'voluntarios as ja_inscrito_voluntario' => fn ($q) => $q
                            ->where('idt_pessoa', $pessoa->idt_pessoa),
                    ]);
            })
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->idt_movimento, fn ($q) => $q->movimento($request->idt_movimento))
            ->orderBy('dat_inicio', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('evento.list', [
            'eventos' => $eventos,
            'movimentos' => TipoMovimento::all(['idt_movimento', 'des_sigla']),
            'search' => $request->search,
            'idt_movimento' => $request->idt_movimento,
        ]);
    }

    /**
     * Exibe o formulário para criar um novo evento.
     */
    public function create(): View
    {
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de criação de evento', $context);

        $movimentos = TipoMovimento::all();
        $evento = new Evento;

        return view('evento.form', compact('movimentos', 'evento'));
    }

    /**
     * Salva o evento delegando a complexidade ao Service.
     */
    public function store(EventoRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $dados = $request->validated();
            
            // Remove os campos para não tentar salvar med_foto/med_logo na tabela evento
            unset($dados['med_foto']);
            unset($dados['med_logo']);

            $evento = Evento::create($dados);

            // Prepara o nome customizado: "2026-04-11-nome-do-evento"
            $dataInicio = $evento->dat_inicio->format('Y-m-d');
            $tituloSlug = Str::slug($evento->des_evento); // trim, lowercase e hífens automáticos
            $nomeArquivoBase = "{$dataInicio}-{$tituloSlug}";

            // Upload da Foto Oficial
            if ($request->hasFile('med_foto')) {
                $this->arquivoService->upload(
                    model: $evento,
                    file: $request->file('med_foto'),
                    relationName: 'foto',
                    column: 'med_foto',
                    path: 'eventos/fotos',
                    customName: $nomeArquivoBase.'-oficial' // Sufixo para diferenciar se necessário
                );
            }

            // Upload da Logo/Padroeira
            if ($request->hasFile('med_logo')) {
                $this->arquivoService->upload(
                    model: $evento,
                    file: $request->file('med_logo'),
                    relationName: 'logo',
                    column: 'med_logo',
                    path: 'eventos/logos',
                    customName: $nomeArquivoBase.'-logo'
                );
            }

            DB::commit();

            Log::notice('Evento criado', ['evento_id' => $evento->idt_evento]);

            return redirect()
                ->route('eventos.index')
                ->with('success', 'Evento criado com sucesso!');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Falha ao criar evento', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()
                ->with('error', 'Erro ao processar cadastro.')
                ->withInput();
        }
    }

    public function show(Evento $evento): View
    {
        $context = $this->getLogContext(request());
        Log::info('Visualização de evento', array_merge($context, ['evento_id' => $evento->idt_evento]));

        $evento->load('foto');
        $movimentos = TipoMovimento::all();

        return view('evento.form', compact('movimentos', 'evento'));
    }

    /**
     * Exibe o formulário para editar um evento.
     */
    public function edit(Evento $evento): View
    {
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de edição de evento', array_merge($context, ['evento_id' => $evento->idt_evento]));

        $evento->load('foto', 'logo');
        $movimentos = TipoMovimento::all();

        return view('evento.form', compact('movimentos', 'evento'));
    }

    /**
     * Atualiza o evento no banco de dados.
     */
    public function update(EventoRequest $request, Evento $evento): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        Log::info('Tentativa de atualização de evento', array_merge($context, [
            'evento_id' => $evento->idt_evento,
            'titulo' => $request->get('des_evento'),
        ]));

        try {
            DB::beginTransaction();

            // Valida e obtém os dados
            $dados = $request->validated();

            // Remove os campos para não tentar atualiza med_foto na tabela evento
            unset($dados['med_foto']);
            unset($dados['med_logo']);

            $evento->update($dados);

            // Prepara o nome customizado: "2026-04-11-nome-do-evento"
            $dataInicio = $evento->dat_inicio instanceof Carbon || method_exists($evento->dat_inicio, 'format')
                ? $evento->dat_inicio->format('Y-m-d')
                : now()->format('Y-m-d');

            $tituloSlug = Str::slug($evento->des_evento);
            $nomeArquivoBase = "{$dataInicio}-{$tituloSlug}";

            // Upload da Foto Oficial (se enviada)
            if ($request->hasFile('med_foto')) {
                $this->arquivoService->upload(
                    model: $evento,
                    file: $request->file('med_foto'),
                    relationName: 'foto',
                    column: 'med_foto',
                    path: 'eventos/fotos',
                    customName: $nomeArquivoBase.'-oficial'
                );
            }

            // Upload da Logo/Padroeira (se enviada)
            if ($request->hasFile('med_logo')) {
                $this->arquivoService->upload(
                    model: $evento,
                    file: $request->file('med_logo'),
                    relationName: 'logo',
                    column: 'med_logo',
                    path: 'eventos/logos',
                    customName: $nomeArquivoBase.'-logo'
                );
            }

            DB::commit();

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::notice('Evento atualizado com sucesso', array_merge($context, [
                'evento_id' => $evento->idt_evento,
                'duration_ms' => $duration,
            ]));

            return redirect()
                ->route('eventos.index')
                ->with('success', 'Evento atualizado com sucesso!');

        } catch (\Throwable $e) {
            DB::rollBack();

            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::error('Erro ao atualizar evento', array_merge($context, [
                'evento_id' => $evento->idt_evento,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => $duration,
            ]));

            return redirect()
                ->route('eventos.index')
                ->with('error', 'Erro ao atualizar evento. Por favor, tente novamente.');
        }
    }

    /**
     * Remove o evento do banco de dados.
     *
     * @return RedirectResponse
     */
    public function destroy(Evento $evento)
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        Log::warning('Tentativa de exclusão de evento', array_merge($context, [
            'evento_id' => $evento->idt_evento,
            'titulo_evento' => $evento->des_evento,
        ]));

        try {
            // deleta a foto e o evento
            if ($evento->med_foto) {
                $this->arquivoService->excluirArquivo($evento->med_foto);
            }
            if ($evento->med_logo) {
                $this->arquivoService->excluirArquivo($evento->med_logo);
            }

            $evento->delete();

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::notice('Evento excluído com sucesso', array_merge($context, [
                'evento_id' => $evento->idt_evento,
                'duration_ms' => $duration,
            ]));

            return redirect()->route('eventos.index')
                ->with('success', 'Evento excluído com sucesso!');
        } catch (QueryException $e) {

            if ($e->getCode() === '23000') {
                return redirect()->route('eventos.index')
                    ->with('error', 'Não é possível excluir o evento, pois ele possui participantes vinculados.');
            }

            return redirect()->route('eventos.index')
                ->with('error', 'Ocorreu um erro de banco de dados ao excluir o evento.');
        }
    }

    /**
     * Confirma participação
     */
    public function confirm(Evento $evento, Pessoa $pessoa): RedirectResponse
    {
        $this->eventoService->confirmarParticipacao($evento, $pessoa);

        Log::notice('Participação confirmada', [
            'evento' => $evento->idt_evento,
            'pessoa' => $pessoa->idt_pessoa,
        ]);

        return redirect()->route('eventos.index')->with('success', 'Sua participação foi confirmada!');
    }

    /**
     * Linha do Tempo Otimizada.
     */
    public function timeline(): View
    {
        $start = microtime(true);
        $pessoa = Auth::user()->pessoa->fresh();

        // Dados cacheados/processados para alta performance
        $data = [
            'timeline' => $this->eventoService->getEventosTimeline($pessoa),
            'pontuacaoTotal' => $pessoa->qtd_pontos_total ?? 0,
            'posicaoNoRanking' => $this->eventoService->calcularRanking($pessoa),
            'pessoa' => $pessoa,
        ];

        $this->logPerformance($pessoa, $data, $start);

        return view('evento.linhadotempo', $data);
    }

    private function logPerformance($pessoa, $timeline, $start): void
    {
        Log::info('Timeline acessada', [
            'pessoa' => $pessoa->idt_pessoa,
            'count' => count($timeline),
            'ms' => round((microtime(true) - $start) * 1000, 2),
        ]);
    }
}
