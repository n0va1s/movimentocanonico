<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TriagemVoluntarios
{
    public function resumoEscalacao(Evento $evento, Collection $catalogo): array
    {
        $trabalhadores = DB::table('trabalhador')->where('idt_evento', $evento->getKey());
        $contagens = (clone $trabalhadores)
            ->select('idt_equipe')
            ->selectRaw('count(distinct idt_pessoa) as escalados')
            ->selectRaw('count(distinct case when ind_coordenador then idt_pessoa end) as coordenadores')
            ->groupBy('idt_equipe')->get()->keyBy('idt_equipe');

        $equipes = $catalogo->keyBy('idt_equipe');
        $historicas = $contagens->keys()->diff($equipes->keys());
        if ($historicas->isNotEmpty()) {
            $equipes = $equipes->union(TipoEquipe::withTrashed()->whereKey($historicas->all())
                ->get(['idt_equipe', 'des_grupo'])->keyBy('idt_equipe'));
        }

        return [
            'total' => $trabalhadores->distinct()->count('idt_pessoa'),
            'equipes' => $equipes->map(function (TipoEquipe $equipe) use ($contagens) {
                $contagem = $contagens->get($equipe->getKey());

                return [
                    'id' => $equipe->getKey(),
                    'nome' => $equipe->des_grupo,
                    'escalados' => (int) ($contagem?->escalados ?? 0),
                    'coordenadores' => (int) ($contagem?->coordenadores ?? 0),
                ];
            }),
        ];
    }

    public function fila(Evento $evento, ?int $pessoa = null): Builder
    {
        $id = $evento->getKey();
        $origem = $evento->idt_encontro_origem;
        $porEvento = fn (string $tabela, int $eventoId) => DB::table($tabela)
            ->where('idt_evento', $eventoId)
            ->when($pessoa !== null, fn (Builder $query) => $query->where('idt_pessoa', $pessoa));

        $ids = $porEvento('voluntario', $id)->select('idt_pessoa')
            ->union($porEvento('candidatura_voluntario', $id)->select('idt_pessoa'))
            ->union($porEvento('trabalhador', $id)->where('ind_coordenador', true)->select('idt_pessoa'));

        if ($origem) {
            $ids->union($porEvento('participante', $origem)->select('idt_pessoa'));
        }

        $trabalho = $porEvento('trabalhador', $id)->select('idt_pessoa')
            ->selectRaw('max(case when ind_coordenador then 1 else 0 end) as coordenador')
            ->groupBy('idt_pessoa');

        $dados = DB::table('pessoa as p')
            ->joinSub($ids, 'candidatos', 'p.idt_pessoa', '=', 'candidatos.idt_pessoa')
            ->leftJoinSub($trabalho, 'trabalho', 'trabalho.idt_pessoa', '=', 'p.idt_pessoa')
            ->leftJoin('candidatura_voluntario as meta', fn (JoinClause $join) => $join
                ->on('meta.idt_pessoa', '=', 'p.idt_pessoa')->where('meta.idt_evento', $id))
            ->leftJoin('participante as anterior', fn (JoinClause $join) => $join
                ->on('anterior.idt_pessoa', '=', 'p.idt_pessoa')->where('anterior.idt_evento', $origem))
            ->whereNull('p.deleted_at')
            ->where(fn (Builder $query) => $query->whereNotNull('trabalho.idt_pessoa')
                ->orWhereNotExists(fn (Builder $participante) => $participante->selectRaw('1')
                    ->from('participante')->whereColumn('idt_pessoa', 'p.idt_pessoa')->where('idt_evento', $id)))
            ->select(['p.idt_pessoa', 'p.nom_pessoa', 'p.nom_apelido', 'p.tel_pessoa', 'p.dat_nascimento', 'meta.ind_publica'])
            ->selectRaw('coalesce(trabalho.coordenador, 0) as coordenador')
            ->selectRaw('case when anterior.idt_pessoa is null then 0 else 1 end as prioritario')
            ->selectRaw("case when trabalho.idt_pessoa is not null then 'escalado'
                when nullif(meta.motivo_nao_selecao, '') is not null then 'recusado'
                else 'pendente' end as situacao");

        return DB::query()->fromSub($dados, 'fila');
    }

    public function filtrar(Builder $fila, Evento $evento, ?int $movimentoOrigem, string $busca, string $perfil, string $interesse, string $escala): Builder
    {
        $nome = $this->nomeSql($fila);
        $busca = trim($busca);
        if ($busca !== '') {
            if ($fila->getConnection()->getDriverName() === 'sqlite') {
                $fila->whereRaw("instr($nome, ?) > 0", [Str::ascii(Str::lower($busca))]);
            } else {
                $fila->whereRaw("locate(?, $nome) > 0", [Str::lower($busca)]);
            }
        }

        match ($perfil) {
            'publica' => $fila->where('ind_publica', true),
            'coordenador' => $fila->where('coordenador', 1),
            'vem', 'sgm' => $fila->where('prioritario', 1)
                ->when($movimentoOrigem !== ($perfil === 'vem' ? TipoMovimento::VEM : TipoMovimento::SGM),
                    fn (Builder $query) => $query->whereRaw('1 = 0')),
            default => null,
        };

        foreach (['pendente' => ['voluntario', $interesse], 'escalado' => ['trabalhador', $escala]] as $situacao => [$tabela, $equipe]) {
            if ($equipe !== '') {
                $fila->where(fn (Builder $query) => $query->where('situacao', '<>', $situacao)
                    ->orWhereExists(fn (Builder $equipeQuery) => $equipeQuery->selectRaw('1')->from($tabela)
                        ->whereColumn('idt_pessoa', 'fila.idt_pessoa')
                        ->where('idt_evento', $evento->getKey())->where('idt_equipe', (int) $equipe)));
            }
        }

        return $fila;
    }

    public function ordenar(Builder $fila, string $ordem): Builder
    {
        if ($ordem === 'prioridade') {
            $fila->orderByDesc('coordenador')->orderByDesc('prioritario');
        }

        $direcao = $ordem === 'nome-desc' ? 'desc' : 'asc';

        return $fila->orderByRaw($this->nomeSql($fila).' '.$direcao)->orderBy('idt_pessoa');
    }

    public function preferencias(int $evento, Collection $pessoas, bool $comMotivo = false): Collection
    {
        $colunas = ['v.idt_pessoa', 'equipe.des_grupo as nome'];
        if ($comMotivo) {
            $colunas[] = 'v.txt_habilidade';
        }

        return DB::table('voluntario as v')->leftJoin('tipo_equipe as equipe', fn (JoinClause $join) => $join
            ->on('v.idt_equipe', '=', 'equipe.idt_equipe')->whereNull('equipe.deleted_at'))
            ->where('v.idt_evento', $evento)->whereIn('v.idt_pessoa', $pessoas)
            ->orderBy('v.idt_voluntario')->get($colunas)->groupBy('idt_pessoa');
    }

    public function equipesEscaladas(int $evento, Collection $pessoas): Collection
    {
        return DB::table('trabalhador as t')->leftJoin('tipo_equipe as equipe', fn (JoinClause $join) => $join
            ->on('t.idt_equipe', '=', 'equipe.idt_equipe')->whereNull('equipe.deleted_at'))
            ->where('t.idt_evento', $evento)->whereIn('t.idt_pessoa', $pessoas)
            ->orderByDesc('t.ind_coordenador')->orderBy('t.idt_trabalhador')
            ->get(['t.idt_pessoa', 'equipe.des_grupo as nome'])->groupBy('idt_pessoa')
            ->map(fn (Collection $equipes) => $equipes->first());
    }

    private function nomeSql(Builder $fila): string
    {
        $conexao = $fila->getConnection();
        if ($conexao->getDriverName() === 'sqlite') {
            $conexao->getPdo()->sqliteCreateFunction('triagem_normalizar',
                static fn (string $nome) => Str::ascii(Str::lower($nome)), 1);

            return "triagem_normalizar(nom_pessoa || ' ' || coalesce(nom_apelido, ''))";
        }

        return "lower(concat(nom_pessoa, ' ', coalesce(nom_apelido, '')))";
    }
}
