<?php

namespace App\Http\Controllers;

use App\Mail\BoasVindasMail;
use App\Models\Evento;
use App\Models\Pessoa;
use App\Models\TipoEquipe;
use App\Models\User;
use App\Services\CpfService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CandidaturaPublicaController extends Controller
{
    public function store(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless(Evento::comCandidaturaAberta()->whereKey($evento->getKey())->exists(), 404);

        $equipes = TipoEquipe::where('idt_movimento', $evento->idt_movimento)->get();
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'apelido' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'tipo_candidatura' => ['required', 'string', Rule::in(['jovem', 'casal'])],
            'nome_conjuge' => ['exclude_unless:tipo_candidatura,casal', 'required', 'string', 'max:255'],
            'apelido_conjuge' => ['exclude_unless:tipo_candidatura,casal', 'nullable', 'string', 'max:255'],
            'telefone_conjuge' => ['exclude_unless:tipo_candidatura,casal', 'required', 'string', 'max:32', new \App\Rules\Telefone],
            'email_conjuge' => ['exclude_unless:tipo_candidatura,casal', 'required', 'email', 'max:255'],
            'cpf' => ['required', 'string', 'regex:/^[0-9.\-]+$/', function ($attribute, $cpf, $fail) {
                if (! CpfService::validate($cpf, forceStrict: true)) {
                    $fail('Informe um CPF válido.');
                }
            }],
            'equipes_anteriores' => ['nullable', 'array'],
            'equipes_anteriores.*' => ['integer', 'distinct', Rule::in($equipes->modelKeys())],
            'equipes' => ['required', 'array'],
            'equipes.*' => ['array:selecionado,habilidade'],
            'equipes.*.selecionado' => ['sometimes', 'accepted'],
            'equipes.*.habilidade' => ['nullable', 'string', 'max:1000'],
            'toca_instrumento' => ['sometimes', 'accepted'],
            'instrumento' => ['exclude_unless:toca_instrumento,1', 'required', 'string', 'max:120'],
            'canta' => ['sometimes', 'accepted'],
            'observacoes' => ['nullable', 'string', 'max:3000'],
        ], [
            'tipo_candidatura.required' => 'Selecione Jovem ou Casal.',
            'tipo_candidatura.in' => 'Selecione Jovem ou Casal.',
            'nome_conjuge.required' => 'Informe o nome do cônjuge.',
            'telefone_conjuge.required' => 'Informe o telefone do cônjuge.',
            'email_conjuge.required' => 'Informe o e-mail do cônjuge.',
        ]);

        $escolhas = collect($dados['equipes'])->filter(fn ($equipe) => isset($equipe['selecionado']));
        $disponiveis = $equipes->where('ind_disponivel_candidatura', true)->modelKeys();
        if ($escolhas->isEmpty() || $escolhas->diffKeys(array_flip($disponiveis))->isNotEmpty()
            || $escolhas->contains(fn ($equipe) => blank($equipe['habilidade'] ?? null))) {
            throw ValidationException::withMessages(['equipes' => 'Selecione ao menos uma equipe disponível e informe o motivo de cada escolha.']);
        }

        try {
            [$usuario, $senha, $recebida] = DB::transaction(function () use ($evento, $dados, $escolhas) {
                Evento::comCandidaturaAberta()->lockForUpdate()->findOrFail($evento->getKey());
                $cpf = CpfService::clean($dados['cpf']);
                $pessoa = Pessoa::withTrashed()->where('num_cpf_pessoa', $cpf)->lockForUpdate()->first();
                if ($pessoa?->trashed()) {
                    return [null, null, false];
                }

                $email = Str::lower($dados['email']);
                $pessoa ??= Pessoa::create([
                    'nom_pessoa' => $dados['nome'],
                    'nom_apelido' => $dados['apelido'] ?? null,
                    'num_cpf_pessoa' => $cpf,
                    'eml_pessoa' => $email,
                ]);

                $candidatura = DB::table('candidatura_voluntario')
                    ->where('idt_evento', $evento->getKey())->where('idt_pessoa', $pessoa->getKey());
                if ($candidatura->exists() || $pessoa->trabalhadores()->where('idt_evento', $evento->getKey())->exists()
                    || $pessoa->participantes()->where('idt_evento', $evento->getKey())->exists()
                    || $pessoa->fichas()->where('idt_evento', $evento->getKey())->exists()) {
                    return [null, null, false];
                }

                DB::table('candidatura_voluntario')->insert([
                    'idt_evento' => $evento->getKey(),
                    'idt_pessoa' => $pessoa->getKey(),
                    'ind_publica' => true,
                    'tipo_candidatura' => $dados['tipo_candidatura'],
                    'nome_conjuge' => $dados['nome_conjuge'] ?? null,
                    'apelido_conjuge' => $dados['apelido_conjuge'] ?? null,
                    'telefone_conjuge' => \App\Services\PhoneService::clean($dados['telefone_conjuge'] ?? null),
                    'email_conjuge' => isset($dados['email_conjuge']) ? Str::lower($dados['email_conjuge']) : null,
                    'equipes_anteriores' => json_encode($dados['equipes_anteriores'] ?? [], JSON_THROW_ON_ERROR),
                    'instrumento' => $dados['instrumento'] ?? null,
                    'ind_canta' => isset($dados['canta']),
                    'observacoes' => $dados['observacoes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($escolhas as $id => $equipe) {
                    $pessoa->voluntarios()->firstOrCreate(
                        ['idt_evento' => $evento->getKey(), 'idt_equipe' => $id],
                        ['txt_habilidade' => $equipe['habilidade']],
                    );
                }

                $usuario = $pessoa->usuario;
                $senha = null;
                if (! $usuario && filter_var($pessoa->eml_pessoa, FILTER_VALIDATE_EMAIL)
                    && ! User::where('email', $pessoa->eml_pessoa)->exists()) {
                    $senha = Str::password(20);
                    $usuario = User::create([
                        'name' => $pessoa->nom_pessoa,
                        'email' => $pessoa->eml_pessoa,
                        'password' => Hash::make($senha),
                        'role' => User::ROLE_USER,
                    ]);
                    $usuario->forceFill(['senha_temporaria_expira_em' => now()->addDays(7)])->save();
                    $pessoa->update(['idt_usuario' => $usuario->id]);
                }

                return [$usuario, $senha, true];
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            report($exception);
            throw ValidationException::withMessages(['cpf' => 'Não foi possível registrar a candidatura. Confira os dados e tente novamente.']);
        }

        if ($recebida && $usuario) {
            try {
                if ($senha) {
                    Mail::to($usuario->email)->queue(new BoasVindasMail($usuario, $senha));
                } else {
                    Mail::raw(
                        "Sua candidatura foi recebida para {$evento->des_evento}. Para recuperar o acesso, visite ".route('password.request'),
                        fn ($mensagem) => $mensagem->to($usuario->email)->subject('Candidatura recebida'),
                    );
                }
            } catch (\Exception $exception) {
                report($exception);
            }
        }

        return back()->with('candidatura_enviada', 'Solicitação recebida. A coordenação avalia as candidaturas na montagem das equipes.');
    }
}
