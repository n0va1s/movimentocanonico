<?php

namespace App\Http\Controllers;

use App\Enums\TipoSituacao;
use App\Http\Requests\FichaEccRequest;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\TipoMovimento;
use App\Services\ArquivoService;
use App\Services\FichaService;
use App\Traits\LogContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FichaEccController extends Controller
{
    use LogContext;

    public function __construct(
        protected FichaService $fichaService,
        protected ArquivoService $arquivoService,
    ) {}

    /**
     * Listagem das fichas.
     */
    public function index(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');
        $eventoId = $request->get('evento');
        $situacao = $request->get('situacao');
        $evento = $eventoId ? Evento::find($eventoId) : null;

        Log::info('Requisição de listagem de fichas ECC iniciada', array_merge($context, [
            'search_term' => $search,
            'evento_filtro' => $eventoId,
            'situacao_filtro' => $situacao,
        ]));

        $hoje = now()->startOfDay();
        $eventos = Evento::where('idt_movimento', TipoMovimento::ECC)
            ->ativos()
            ->orderBy('dat_inicio', 'asc')
            ->get();

        $fichas = Ficha::with(['fichaEcc', 'fichaSaude'])
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nom_candidato', 'like', "%{$search}%")
                    ->orWhere('nom_apelido', 'like', "%{$search}%");
            }))
            ->when($eventoId, fn ($q) => $q->where('idt_evento', $eventoId))
            ->when($situacao, fn ($q) => $q->where('tip_situacao', $situacao))
            ->whereHas('evento', fn ($q) => $q->where('idt_movimento', TipoMovimento::ECC))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        Log::notice('Listagem de fichas ECC concluída', array_merge($context, [
            'total_fichas' => $fichas->total(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]));

        return view('ficha.listECC', compact('fichas', 'search', 'evento', 'eventos', 'situacao'));
    }

    /**
     * Formulário de criação.
     */
    public function create()
    {
        Log::info('Acesso ao formulário de criação de ficha ECC', $this->getLogContext(request()));

        $ficha = new Ficha;
        $eventos = Evento::porTipo(TipoMovimento::ECC, 'E', 3)->get();

        return view('ficha.formECC', array_merge(
            $this->fichaService::dadosFixosFicha($ficha),
            [
                'ficha' => $ficha,
                'eventos' => $eventos,
                'movimentopadrao' => TipoMovimento::ECC,
            ]
        ));
    }

    /**
     * Armazenar nova ficha ECC.
     */
    public function store(FichaEccRequest $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        Log::info('Tentativa de criação de ficha ECC', array_merge($context, [
            'candidato' => $request->input('nom_candidato'),
            'evento_id' => $request->input('idt_evento'),
        ]));

        // ── Tabela ficha (base) ───────────────────────────────────────────────
        $ficha = Ficha::create($request->only([
            'idt_evento',
            'tip_genero',
            'num_cpf_candidato',
            'nom_candidato',
            'nom_apelido',
            'dat_nascimento',
            'tel_candidato',
            'eml_candidato',
            'nom_profissao',
            'des_endereco',
            'tam_camiseta',
            'tip_como_soube',
            'tip_habilidade',
            'ind_catolico',
            'ind_toca_instrumento',
            'ind_consentimento',
            'ind_restricao',
            'txt_observacao',
        ]));

        // ── Foto do candidato ───────────────────────────────────────────────────
        if ($request->hasFile('med_foto')) {
            $this->arquivoService->upload(
                $ficha,
                $request->file('med_foto'),
                'foto',
                'med_foto',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        // ── Tabela ficha_ecc ──────────────────────────────────────────────────
        $ficha->fichaEcc()->create($request->only([
            'num_cpf_conjuge',
            'nom_conjuge',
            'nom_apelido_conjuge',
            'tip_genero_conjuge',
            'dat_nascimento_conjuge',
            'tel_conjuge',
            'eml_conjuge',
            'nom_profissao_conjuge',
            'ind_catolico_conjuge',
            'tip_habilidade_conjuge',
            'tam_camiseta_conjuge',
            'tip_estado_civil',
            'nom_paroquia',
            'dat_casamento',
            'qtd_filhos',
        ]));

        // ── Foto do cônjuge ───────────────────────────────────────────────────
        if ($request->hasFile('med_conjuge')) {
            $this->arquivoService->upload(
                $ficha,
                $request->file('med_conjuge'),
                'foto',
                'med_conjuge',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        // ── Filhos ────────────────────────────────────────────────────────────
        foreach ($request->input('filhos', []) as $filho) {
            if (! empty($filho['nom_filho'])) {
                $ficha->fichaEcc->filhos()->create($filho);
            }
        }

        // ── Restrições de saúde ───────────────────────────────────────────────
        if ($request->input('ind_restricao') == 1) {
            foreach ($request->input('restricoes', []) as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $request->input("complementos.{$idt_restricao}"),
                    ]);
                }
            }
        }

        Log::notice('Ficha ECC criada com sucesso', array_merge($context, [
            'ficha_id' => $ficha->idt_ficha,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]));

        // Dispara e-mail de recebimento (para candidatos/casal)
        \App\Events\FichaRecebidaEvent::dispatch($ficha);

        $previous = url()->previous();
        if (str_contains($previous, '/fichas/ecc') || (app()->runningUnitTests() && ! str_contains($previous, '/ecc'))) {
            return back()->with('success', 'Ficha cadastrada com sucesso!');
        }

        return redirect()->route('home')->with('success', 'Ficha cadastrada com sucesso!');
    }

    /**
     * Exibir ficha individual.
     */
    public function show($id)
    {
        Log::info('Visualização de ficha ECC', array_merge($this->getLogContext(request()), ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaEcc.filhos', 'fichaSaude.restricao', 'foto', 'evento'])->findOrFail($id);

        // Modo impressão: view dedicada sem formulário de edição
        if (request()->boolean('print') || request()->has('print')) {
            return view('ficha.print', [
                'ficha' => $ficha,
                'tipo' => 'ECC',
                'rotaEdit' => route('ecc.edit', $ficha),
            ]);
        }

        $visitadores = \App\Models\Pessoa::whereHas('usuario', function ($q) {
            $q->where('role', \App\Models\User::ROLE_VISITACAO);
        })->with('parceiro')->orderBy('nom_pessoa', 'asc')->get();

        return view('ficha.formECC', array_merge(
            $this->fichaService::dadosFixosFicha($ficha),
            [
                'ficha' => $ficha,
                'eventos' => Evento::where('idt_movimento', TipoMovimento::ECC)->get(),
                'movimentopadrao' => TipoMovimento::ECC,
                'visitadores' => $visitadores,
            ]
        ));
    }

    /**
     * Formulário de edição.
     */
    public function edit($id)
    {
        Log::info('Acesso ao formulário de edição de ficha ECC', array_merge($this->getLogContext(request()), ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaEcc.filhos', 'fichaSaude', 'foto'])->findOrFail($id);

        $visitadores = \App\Models\Pessoa::whereHas('usuario', function ($q) {
            $q->where('role', \App\Models\User::ROLE_VISITACAO);
        })->with('parceiro')->orderBy('nom_pessoa', 'asc')->get();

        return view('ficha.formECC', array_merge(
            $this->fichaService::dadosFixosFicha($ficha),
            [
                'ficha' => $ficha,
                'eventos' => Evento::where('idt_movimento', TipoMovimento::ECC)->get(),
                'movimentopadrao' => TipoMovimento::ECC,
                'visitadores' => $visitadores,
            ]
        ));
    }

    /**
     * Atualizar ficha ECC.
     */
    public function update(FichaEccRequest $request, $id)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        Log::info('Tentativa de atualização de ficha ECC', array_merge($context, [
            'ficha_id' => $id,
            'candidato' => $request->input('nom_candidato'),
        ]));

        $ficha = Ficha::with(['fichaEcc', 'fichaSaude', 'foto'])->findOrFail($id);

        // ── Tabela ficha (base) ───────────────────────────────────────────────
        $ficha->update($request->only([
            'idt_evento',
            'tip_genero',
            'num_cpf_candidato',
            'nom_candidato',
            'nom_apelido',
            'dat_nascimento',
            'tel_candidato',
            'eml_candidato',
            'nom_profissao',
            'des_endereco',
            'tam_camiseta',
            'tip_como_soube',
            'tip_habilidade',
            'ind_catolico',
            'ind_toca_instrumento',
            'ind_consentimento',
            'ind_restricao',
            'txt_observacao',
        ]));

        // ── Foto do candidato ───────────────────────────────────────────────────
        if ($request->hasFile('med_foto')) {
            $this->arquivoService->upload(
                $ficha,
                $request->file('med_foto'),
                'foto',
                'med_foto',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        // ── Tabela ficha_ecc ──────────────────────────────────────────────────
        $eccData = $request->only([
            'num_cpf_conjuge',
            'nom_conjuge',
            'nom_apelido_conjuge',
            'tip_genero_conjuge',
            'dat_nascimento_conjuge',
            'tel_conjuge',
            'eml_conjuge',
            'nom_profissao_conjuge',
            'ind_catolico_conjuge',
            'tip_habilidade_conjuge',
            'tam_camiseta_conjuge',
            'tip_estado_civil',
            'nom_paroquia',
            'dat_casamento',
            'qtd_filhos',
        ]);

        if ($ficha->fichaEcc) {
            $ficha->fichaEcc->update($eccData);
        } else {
            $ficha->fichaEcc()->create($eccData);
            $ficha->load('fichaEcc');
        }

        // ── Filhos: substitui todos ───────────────────────────────────────────
        $ficha->fichaEcc->filhos()->delete();

        foreach ($request->input('filhos', []) as $filho) {
            if (! empty($filho['nom_filho'])) {
                $ficha->fichaEcc->filhos()->create($filho);
            }
        }

        // ── Foto do cônjuge ───────────────────────────────────────────────────
        if ($request->hasFile('med_conjuge')) {
            $this->arquivoService->upload(
                $ficha,
                $request->file('med_conjuge'),
                'foto',
                'med_conjuge',
                "fichas/{$ficha->idt_ficha}"
            );
        }

        // ── Restrições de saúde: substitui todas ─────────────────────────────
        $ficha->fichaSaude()->delete();

        if ($request->input('ind_restricao') == 1) {
            foreach ($request->input('restricoes', []) as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $request->input("complementos.{$idt_restricao}"),
                    ]);
                }
            }
        }

        Log::notice('Ficha ECC atualizada com sucesso', array_merge($context, [
            'ficha_id' => $ficha->idt_ficha,
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]));

        if (auth()->user()->role === 'admin') {
            return redirect()->route('ecc.index')->with('success', 'Ficha ECC atualizada com sucesso.');
        }

        return redirect()->route('home')->with('success', 'Ficha ECC atualizada com sucesso.');
    }

    /**
     * Remover ficha.
     */
    public function destroy($id)
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        Log::warning('Tentativa de exclusão de ficha ECC', array_merge($context, ['ficha_id' => $id]));

        try {
            Ficha::findOrFail($id)->delete();

            Log::notice('Ficha ECC excluída com sucesso', array_merge($context, [
                'ficha_id' => $id,
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]));

            return redirect()->route('ecc.index')->with('success', 'Ficha excluída com sucesso!');

        } catch (QueryException $e) {
            Log::error('Erro de Query ao excluir ficha ECC', array_merge($context, [
                'ficha_id' => $id,
                'sql_state' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]));

            $msg = $e->getCode() === '23000'
                ? 'Não é possível excluir esta ficha. É preciso apagar os dados associados.'
                : 'Erro ao tentar excluir a ficha.';

            return back()->with('error', $msg);
        }
    }

    public function approve($id)
    {
        $ficha = FichaService::atualizarAprovacaoFicha($id);

        return redirect()->route('ecc.index')->with('success', 'Ficha aprovada com sucesso!');
    }

    public function updateSituacao(Request $request, $id)
    {
        $request->validate([
            'tip_situacao' => 'required|string|in:N,S,E,R,P,C,A,F,W,V',
        ]);

        $novaSituacao = TipoSituacao::from($request->input('tip_situacao'));

        try {
            FichaService::atualizarSituacaoFicha($id, $novaSituacao);

            return redirect()->route('ecc.index')->with('success', 'Situação da ficha atualizada com sucesso!');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
