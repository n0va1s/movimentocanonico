<?php

namespace App\Http\Controllers;

use App\Http\Requests\PessoaRequest;
use App\Models\Pessoa;
use App\Models\PessoaFoto;
use App\Models\TipoRestricao;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Trabalhador;
use App\Models\Ficha;
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

        // Se não for admin, ele só pode ver o PRÓPRIO perfil.
        $user = auth()->user();
        if (! $user->isAdmin() && optional($user->pessoa)->idt_pessoa !== $pessoa->idt_pessoa) {
            abort(403, 'Você não tem permissão para visualizar estes dados.');
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Visualização de pessoa', array_merge($context, [
            'pessoa_id' => $id,
            'duration_ms' => $duration,
        ]));

        return view('pessoa.show', compact('pessoa'));
    }


    public function create(): View
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário de criação de pessoa', $context);

        $user = auth()->user();
        $pessoa = new Pessoa([
            'nom_pessoa' => $user->name,
            'eml_pessoa' => $user->email,
            'tel_pessoa' => $user->phone,
        ]);
        $restricoes = TipoRestricao::select(
            'idt_restricao',
            'tip_restricao',
            'des_restricao',
        )->get();

        $meuId = auth()->user()->pessoa ? auth()->user()->pessoa->idt_pessoa : null;

        $pessoasDisponiveis = Pessoa::query()
            ->when($meuId, function ($query, $meuId) {
                return $query->where(function ($q) use ($meuId) {
                    $q->whereNull('idt_parceiro')
                        ->orWhere('idt_parceiro', $meuId);
                })->where('idt_pessoa', '!=', $meuId);
            }, function ($query) {
                return $query->whereNull('idt_parceiro');
            })
            // ->whereIn('tip_estado_civil', ['C', 'E', 'U'])
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

        $cpf = $request->input('num_cpf_pessoa') ? preg_replace('/\D/', '', $request->input('num_cpf_pessoa')) : null;

        if (!auth()->user()->isAdmin() && $cpf) {
            $temFichaNaoAprovada = \App\Models\Ficha::where('num_cpf_candidato', $cpf)
                ->whereNotIn('tip_situacao', [\App\Enums\TipoSituacao::APROVADA, \App\Enums\TipoSituacao::CANCELADA])
                ->exists();

            if ($temFichaNaoAprovada) {
                return redirect()->route('dashboard')->with('info', 'Você já possui uma ficha de inscrição em andamento. Por favor, aguarde a aprovação da sua ficha.');
            }
        }

        $pessoaExistente = $cpf ? Pessoa::where('num_cpf_pessoa', $cpf)->first() : null;
        $data = $request->validated();

        if ($pessoaExistente) {
            if (!auth()->user()->isAdmin() && $pessoaExistente->idt_usuario && $pessoaExistente->idt_usuario !== auth()->id()) {
                return redirect()->back()->with('error', 'Este CPF já está vinculado a outro usuário.');
            }

            if (!auth()->user()->isAdmin()) {
                $data['idt_usuario'] = auth()->id();
            }

            $pessoaExistente->update($data);
            $pessoa = $pessoaExistente;
        } else {
            if (auth()->user()->isAdmin()) {
                $user = $this->userService::getUsuarioByEmail($request->input('eml_pessoa'));
                if ($user) {
                    $data['idt_usuario'] = $user->id;
                }
            } else {
                $data['idt_usuario'] = auth()->id();
            }
            $pessoa = Pessoa::create($data);
        }

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
            // Limpa as antigas para evitar duplicidade na vinculação
            $pessoa->restricoes()->detach();
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
        Log::notice('Pessoa salva com sucesso', array_merge($context, [
            'pessoa_id' => $pessoa->idt_pessoa,
            'restricoes_registradas' => $countRestricoes,
            'duration_ms' => $duration,
        ]));

        return back()->with('success', 'Cadastro localizado e vinculado à sua conta com sucesso!');
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
            abort(403, 'Você não tem permissão para editar estes dados.');
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
            abort(403, 'Você não tem permissão para editar estes dados.');
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

        return back()->with('success', 'Dados atualizados com sucesso.');
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

            return back()->with('success', 'Pessoa excluída com sucesso!');
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
                return back()
                    ->with('error', 'Não é possível excluir esta pessoa. È preciso apagar os dados associados.');
            }

            // Se for outro erro de banco
            return back()
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
