<?php

namespace App\Http\Controllers;

use App\Http\Requests\PessoaRequest;
use App\Models\Pessoa;
use App\Models\PessoaFoto;
use App\Models\TipoRestricao;
use App\Services\UserService;
use App\Traits\LogContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PessoaController extends Controller
{
    use LogContext;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function show($id): View
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        $pessoa = Pessoa::with([
            'foto:idt_pessoa,med_foto',
            'restricoes',
            'pontos',
            'trabalhadores.evento:idt_evento,des_evento,dat_inicio',
            'trabalhadores.equipe:idt_equipe,des_grupo',
        ])->findOrFail($id);

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Visualização de pessoa', array_merge($context, [
            'pessoa_id' => $id,
            'duration_ms' => $duration,
        ]));

        return view('pessoa.show', compact('pessoa'));
    }

    public function index(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');

        Log::info('Requisição de listagem de pessoas iniciada', array_merge($context, [
            'search_term' => $search,
        ]));

        $pessoas = Pessoa::select(
            'idt_pessoa',
            'idt_usuario',
            'idt_parceiro',
            'nom_pessoa',
            'nom_apelido',
            'tel_pessoa',
            'eml_pessoa',
            'tip_estado_civil',
            'tip_habilidade',
            'created_at'
        )
            ->with([
                'foto:idt_pessoa,med_foto',
            ])
            ->when($search, function ($query, $search) {
                return $query->searchByName($search);
            })
            ->orderBy('nom_pessoa')
            ->paginate(10)
            ->withQueryString();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Listagem de pessoas concluída com sucesso', array_merge($context, [
            'total_pessoas' => $pessoas->total(),
            'duration_ms' => $duration,
        ]));

        return view('pessoa.list', compact('pessoas', 'search'));
    }

    public function create(): View
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de criação de pessoa', $context);

        $pessoa = new Pessoa;
        $restricoes = TipoRestricao::select(
            'idt_restricao',
            'tip_restricao',
            'des_restricao',
        )->get();

        $meuId = auth()->user()->pessoa->idt_pessoa;

        $pessoasDisponiveis = Pessoa::query()
            ->where(function ($query) use ($meuId) {
                $query->whereNull('idt_parceiro')
                    ->orWhere('idt_parceiro', $meuId);
            })
            // ->whereIn('tip_estado_civil', ['C', 'E', 'U'])
            ->where('idt_pessoa', '!=', $meuId)
            ->orderBy('nom_pessoa')
            ->pluck('nom_pessoa', 'idt_pessoa');

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Dados obtidos', array_merge($context, [
            'total_restricoes' => $restricoes->count(),
            'total_pessoas_disponiveis' => $pessoasDisponiveis->count(),
            'duration_ms' => $duration,
        ]));

        return view('pessoa.form', [
            'pessoa' => $pessoa,
            'restricoes' => $restricoes,
            'pessoasDisponiveis' => $pessoasDisponiveis,
        ]);
    }

    public function store(PessoaRequest $request): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);
        Log::info('Tentativa de criação de nova pessoa', array_merge($context, [
            'nome' => $request->input('nom_pessoa'),
            'email' => $request->input('eml_pessoa'),
        ]));

        // Pega o ID do usuário conforme o email informado no cadastro da pessoa
        $data = $request->validated();
        $user = $this->userService::getUsuarioByEmail($request->input('eml_pessoa'));
        if ($user) {
            $data['idt_usuario'] = $user->id;
        }
        $pessoa = Pessoa::create($data);

        // Foto
        if ($request->hasFile('med_foto')) {
            $arquivo = $request->file('med_foto');
            $caminho = $arquivo->store('fotos/pessoa', 'public'); // pasta 'storage/app/public/fotos/pessoa/'

            if ($pessoa->foto) {
                $pessoa->foto()->update(['med_foto' => $caminho]);
            } else {
                $pessoa->foto()->create(['med_foto' => $caminho]);
            }
        }

        // Parceiro
        if ($request->input('idt_parceiro')) {
            $pessoa->idt_parceiro = $request->input('idt_parceiro');
            $pessoa->save();
        }

        // Saude
        $countRestricoes = 0;
        if ($request->input('ind_restricao') == 1) {
            foreach ($request->input('restricoes', []) as $idt_restricao => $value) {
                if ($value) {
                    $pessoa->restricoes()->attach($idt_restricao, [
                        'txt_complemento' => $request->input("complementos.$idt_restricao"),
                    ]);
                    $countRestricoes++;
                }
            }
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Pessoa criada com sucesso', array_merge($context, [
            'pessoa_id' => $pessoa->idt_pessoa,
            'restricoes_registradas' => $countRestricoes,
            'duration_ms' => $duration,
        ]));

        return redirect()->route('pessoas.index')->with('success', 'Pessoa criada com sucesso.');
    }

    public function edit($id): View
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        $pessoa = Pessoa::with([
            'foto:idt_pessoa,med_foto',
            'usuario:id,name',
            'restricoes',
        ])->findOrFail($id);

        // Usuários não-admin só podem editar a própria pessoa
        $user = auth()->user();
        if (! $user->isAdmin() && optional($user->pessoa)->idt_pessoa !== $pessoa->idt_pessoa) {
            abort(403);
        }

        $restricoes = TipoRestricao::select(
            'idt_restricao',
            'tip_restricao',
            'des_restricao'
        )->get();

        $pessoasDisponiveis = Pessoa::where(function ($query) use ($pessoa) {
            $query->whereNull('idt_parceiro');
            if ($pessoa->idt_parceiro) {
                $query->orWhere('idt_pessoa', $pessoa->idt_parceiro);
            }
        })
            ->where('idt_pessoa', '!=', $pessoa->idt_pessoa)
            ->orderBy('nom_pessoa')
            ->pluck('nom_pessoa', 'idt_pessoa');

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Dados para edição obtidos', array_merge($context, [
            'pessoa_id' => $id,
            'duration_ms' => $duration,
        ]));

        return view('pessoa.form', compact('pessoa', 'restricoes', 'pessoasDisponiveis'));
    }

    public function update(PessoaRequest $request, $id): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        Log::info('Tentativa de atualização de pessoa', array_merge($context, [
            'pessoa_id' => $id,
            'nome' => $request->input('nom_pessoa'),
        ]));

        $pessoa = Pessoa::with(['foto', 'usuario', 'restricoes'])->findOrFail($id);

        // Usuários não-admin só podem editar a própria pessoa
        $user = auth()->user();
        if (! $user->isAdmin() && optional($user->pessoa)->idt_pessoa !== $pessoa->idt_pessoa) {
            abort(403);
        }

        $user2 = $this->userService::getUsuarioByEmail($request->input('eml_pessoa'));
        $data = $request->validated();

        if ($user2) {
            $data['idt_usuario'] = $user2->id;
        }

        $pessoa->update($data);

        // Foto
        if ($request->hasFile('med_foto')) {
            $arquivo = $request->file('med_foto');
            $caminho = $arquivo->store('fotos/pessoa', 'public');

            if ($caminho) {
                $fotoExistente = PessoaFoto::where('idt_pessoa', $pessoa->idt_pessoa)->first();

                if ($fotoExistente) {
                    if (Storage::disk('public')->exists($fotoExistente->med_foto)) {
                        Storage::disk('public')->delete($fotoExistente->med_foto);
                    }
                    $fotoExistente->update(['med_foto' => $caminho]);
                } else {
                    $pessoa->foto()->create(['med_foto' => $caminho]);
                }
            }
        }

        // Parceiro
        if ($request->filled('idt_parceiro')) {
            $pessoa->idt_parceiro = $request->input('idt_parceiro');
            $pessoa->save();
        }

        // Saude
        $pessoa->ind_restricao = $request->boolean('ind_restricao');
        $pessoa->save();

        if ($pessoa->ind_restricao) {
            $restricoesMarcadas = $request->input('restricoes', []);
            $complementos = $request->input('complementos', []);
            $dadosSincronizacao = [];

            foreach ($restricoesMarcadas as $idt_restricao => $valor) {
                if ($valor) {
                    $dadosSincronizacao[$idt_restricao] = [
                        'txt_complemento' => $complementos[$idt_restricao] ?? null,
                    ];
                }
            }

            // O sync remove o que não está no array e insere/atualiza o que está
            $pessoa->restricoes()->sync($dadosSincronizacao);
        } else {
            // Se o mestre estiver desmarcado, remove todos os vínculos da pivot
            $pessoa->restricoes()->detach();
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Pessoa atualizada com sucesso', array_merge($context, [
            'pessoa_id' => $id,
            'duration_ms' => $duration,
        ]));

        return redirect()->route('dashboard')->with('success', 'Dados atualizados com sucesso.');
    }

    public function destroy($id): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::warning('Tentativa de exclusão de pessoa', array_merge($context, [
            'pessoa_id' => $id,
        ]));

        try {
            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::notice('Pessoa excluída com sucesso', array_merge($context, [
                'pessoa_id' => $id,
                'duration_ms' => $duration,
            ]));

            // Cascade
            Pessoa::findOrFail($id)->delete();

            return redirect()
                ->route('pessoas.index')
                ->with('success', 'Pessoa excluída com sucesso!');
        } catch (QueryException $e) {
            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::error('Erro de Query ao excluir pessoa', array_merge($context, [
                'pessoa_id' => $id,
                'sql_state' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'duration_ms' => $duration,
            ]));

            if ($e->getCode() === '23000') {
                return redirect()
                    ->route('pessoas.index')
                    ->with('error', 'Não é possível excluir esta pessoa. È preciso apagar os dados associados.');
            }

            // Se for outro erro de banco
            return redirect()
                ->route('pessoas.index')
                ->with('error', 'Erro ao tentar excluir a pessoa.');
        }
    }

    public function buscaPorCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        $pessoa = Pessoa::where('num_cpf_pessoa', $cpf)->first();
        if ($pessoa) {
            return response()->json($pessoa);
        }

        return response()->json(['error' => 'not found'], 404);
    }
}
