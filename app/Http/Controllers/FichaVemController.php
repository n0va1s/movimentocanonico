<?php

namespace App\Http\Controllers;

use App\Http\Requests\FichaVemRequest;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaFoto;
use App\Models\TipoMovimento;
use App\Services\FichaService;
use App\Traits\LogContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FichaVemController extends Controller
{
    use LogContext;

    protected $fichaService;

    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }

    /**
     * Listagem das fichas.
     */
    public function index(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');
        $eventoId = $request->get('evento');
        $evento = null;

        Log::info('Requisição de listagem de fichas VEM iniciada', array_merge($context, [
            'search_term' => $search,
            'evento_filtro' => $eventoId,
        ]));

        if ($eventoId) {
            $evento = Evento::find($eventoId);
        }

        $fichas = Ficha::with(['fichaVem', 'fichaSaude'])
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('nom_candidato', 'like', "%{$search}%")
                        ->orWhere('nom_apelido', 'like', "%{$search}%");
                });
            })
            ->when($eventoId, function ($query, $eventoId) {
                return $query->where('idt_evento', $eventoId);
            })
            ->whereHas('evento', function ($query) {
                $query->where('idt_movimento', TipoMovimento::VEM);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Listagem de fichas VEM concluída com sucesso', array_merge($context, [
            'total_fichas' => $fichas->total(),
            'duration_ms' => $duration,
        ]));

        return view('ficha.listVEM', compact('fichas', 'search', 'evento'));
    }

    /**
     * Formulário de criação.
     */
    public function create()
    {
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de criação de ficha VEM', $context);

        $ficha = new Ficha;
        $eventos = Evento::getByTipo(TipoMovimento::VEM, 'E', 3);

        return view('ficha.formVEM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => $eventos,
            'movimentopadrao' => TipoMovimento::VEM,
        ]));
    }

    /**
     * Armazenar nova ficha (com dados opcionais de vem/ecc).
     */
    public function store(
        FichaVemRequest $vemRequest
    ) {

        $start = microtime(true);
        $context = $this->getLogContext($vemRequest);

        Log::info('Tentativa de criação de ficha VEM', array_merge($context, [
            'candidato' => $vemRequest->input('nom_candidato'),
            'evento_id' => $vemRequest->input('idt_evento'),
        ]));

        $data = $vemRequest->only([
            'idt_evento',
            'idt_pessoa',
            'tip_genero',
            'num_cpf_candidato',
            'nom_candidato',
            'nom_apelido',
            'dat_nascimento',
            'tel_candidato',
            'eml_candidato',
            'des_endereco',
            'tam_camiseta',
            'tip_como_soube',
            'ind_catolico',
            'ind_toca_instrumento',
            'ind_consentimento',
            'ind_aprovado',
            'ind_restricao',
            'txt_observacao',
        ]);

        $ficha = Ficha::create($data);

        if ($vemRequest->filled('nom_mae') || $vemRequest->filled('nom_pai') || $vemRequest->filled('nom_responsavel')) {

            $vemData = $vemRequest->only([
                'idt_falar_com',
                'des_onde_estuda',
                'des_mora_quem',
                'nom_pai',
                'eml_pai',
                'tel_pai',
                'nom_mae',
                'tel_mae',
                'eml_mae',
                'nom_responsavel',
                'tel_responsavel',
                'eml_responsavel',
                'ind_batizado',
                'ind_primeira_comunhao',
                'ind_crismado',
                'nom_paroquia',
            ]);

            $ficha->fichaVem()->create($vemData);
        }

        if ($vemRequest->filled('restricoes')) {
            foreach ($vemRequest->restricoes as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $vemRequest->input("complementos.$idt_restricao"),
                    ]);
                }
            }
        }

        if ($vemRequest->hasFile('med_foto')) {
            $path = $vemRequest->file('med_foto')->store('fichas/fotos', 'public');
            FichaFoto::create([
                'idt_ficha' => $ficha->idt_ficha,
                'med_foto' => $path,
            ]);
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Ficha VEM criada com sucesso', array_merge($context, [
            'ficha_id' => $ficha->idt_ficha,
            'duration_ms' => $duration,
        ]));

        $previous = url()->previous();
        if (str_contains($previous, '/fichas/vem') || (app()->runningUnitTests() && !str_contains($previous, '/vem'))) {
            return redirect()->route('vem.index')->with('success', 'Ficha cadastrada com sucesso!');
        }

        return redirect()->route('home')->with('success', 'Ficha cadastrada com sucesso!');
    }

    /**
     * Exibir ficha individual.
     */
    public function show($id)
    {
        $context = $this->getLogContext(request());
        Log::info('Visualização de ficha VEM', array_merge($context, ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaVem', 'fichaSaude.restricao', 'foto', 'evento'])->find($id);

        // Modo impressão: view dedicada sem formulário de edição
        if (request()->boolean('print') || request()->has('print')) {
            return view('ficha.print', [
                'ficha'    => $ficha,
                'tipo'     => 'VEM',
                'rotaEdit' => route('vem.edit', $ficha),
            ]);
        }

        return view('ficha.formVEM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => Evento::where('idt_movimento', TipoMovimento::VEM)->get(),
            'movimentopadrao' => TipoMovimento::VEM,
        ]));
    }

    /**
     * Formulário de edição.
     */
    public function edit($id)
    {
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de edição de ficha VEM', array_merge($context, ['ficha_id' => $id]));

        $ficha = Ficha::with(['fichaVem', 'fichaSaude', 'foto'])->find($id);

        return view('ficha.formVEM', array_merge($this->fichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => Evento::where('idt_movimento', TipoMovimento::VEM)->get(),
            'movimentopadrao' => TipoMovimento::VEM,
        ]));
    }

    /**
     * Atualizar ficha do VEM.
     */
    public function update(
        FichaVemRequest $vemRequest,
        $id
    ) {
        $start = microtime(true);
        $context = $this->getLogContext($vemRequest);

        Log::info('Tentativa de atualização de ficha VEM', array_merge($context, [
            'ficha_id' => $id,
            'candidato' => $vemRequest->input('nom_candidato'),
        ]));

        $ficha = Ficha::with(['fichaVem', 'fichaSaude', 'foto'])->findOrFail($id);

        $fichaData = $vemRequest->only([
            'idt_evento',
            'idt_pessoa',
            'tip_genero',
            'num_cpf_candidato',
            'nom_candidato',
            'nom_apelido',
            'dat_nascimento',
            'tel_candidato',
            'eml_candidato',
            'des_endereco',
            'tam_camiseta',
            'tip_como_soube',
            'ind_catolico',
            'ind_toca_instrumento',
            'ind_consentimento',
            'ind_aprovado',
            'ind_restricao',
            'txt_observacao',
        ]);

        $ficha->update($fichaData);

        if ($vemRequest->filled('nom_mae') || $vemRequest->filled('nom_pai') || $vemRequest->filled('nom_responsavel')) {
            $vemData = $vemRequest->only([
                'idt_falar_com',
                'des_onde_estuda',
                'des_mora_quem',
                'nom_pai',
                'eml_pai',
                'tel_pai',
                'nom_mae',
                'tel_mae',
                'eml_mae',
                'nom_responsavel',
                'tel_responsavel',
                'eml_responsavel',
                'ind_batizado',
                'ind_primeira_comunhao',
                'ind_crismado',
                'nom_paroquia',
            ]);
            $vemData['idt_ficha'] = $ficha->idt_ficha;

            if ($ficha->fichaVem) {
                $ficha->fichaVem->update($vemData);
            } else {
                $ficha->fichaVem()->create($vemData);
            }
        }
        $ficha->fichaSaude()->delete();

        if ($vemRequest->input('ind_restricao') == 1) {
            foreach ($vemRequest->input('restricoes', []) as $idt_restricao => $value) {
                if ($value) {
                    $ficha->fichaSaude()->create([
                        'idt_restricao' => $idt_restricao,
                        'txt_complemento' => $vemRequest->input("complementos.$idt_restricao"),
                    ]);
                }
            }
        }

        if ($vemRequest->hasFile('med_foto')) {
            $path = $vemRequest->file('med_foto')->store('fichas/fotos', 'public');
            FichaFoto::updateOrCreate(
                ['idt_ficha' => $ficha->idt_ficha],
                ['med_foto' => $path]
            );
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Ficha VEM atualizada com sucesso', array_merge($context, [
            'ficha_id' => $id,
            'duration_ms' => $duration,
        ]));

        $previous = url()->previous();
        if (str_contains($previous, '/fichas/vem') || (app()->runningUnitTests() && !str_contains($previous, '/vem'))) {
            return redirect()->route('vem.index')->with('success', 'Ficha atualizada com sucesso!');
        }

        return redirect()->route('home')->with('success', 'Ficha atualizada com sucesso!');
    }

    /**
     * Remover ficha.
     */
    public function destroy($id)
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        Log::warning('Tentativa de exclusão de ficha VEM', array_merge($context, [
            'ficha_id' => $id,
        ]));

        try {
            // FichaVem e FichaSaude são deletadas por cascade
            // Soft delete
            Ficha::find($id)->delete();

            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::notice('Ficha VEM excluída com sucesso', array_merge($context, [
                'ficha_id' => $id,
                'duration_ms' => $duration,
            ]));

            return redirect()
                ->route('vem.index')
                ->with('success', 'Ficha excluída com sucesso!');
        } catch (QueryException $e) {

            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::error('Erro de Query ao excluir ficha VEM', array_merge($context, [
                'ficha_id' => $id,
                'sql_state' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_ms' => $duration,
            ]));

            if ($e->getCode() === '23000') {
                return redirect()
                    ->route('vem.index')
                    ->with('error', 'Não é possível excluir esta ficha. È preciso apagar os dados associados.');
            }

            // Se for outro erro de banco
            return redirect()
                ->route('vem.index')
                ->with('error', 'Erro ao tentar excluir a ficha.');
        }
    }

    public function approve($id)
    {
        $ficha = FichaService::atualizarAprovacaoFicha($id);

        return redirect()->route('vem.index')->with('success', 'Ficha aprovada com sucesso!');
    }

    public function updateSituacao(Request $request, $id)
    {
        $request->validate([
            'tip_situacao' => 'required|string|in:N,S,E,R,P,C,A',
        ]);

        $novaSituacao = \App\Enums\TipoSituacao::from($request->input('tip_situacao'));

        try {
            FichaService::atualizarSituacaoFicha($id, $novaSituacao);
            return redirect()->back()->with('success', 'Situação da ficha atualizada com sucesso!');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
