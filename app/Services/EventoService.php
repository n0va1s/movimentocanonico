<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\Trabalhador;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EventoService
{
    /**
     * Busca a timeline otimizada.
     * Melhoria: Uso de map dinâmico para evitar repetição de chaves.
     */
    public function getEventosTimeline(Pessoa $pessoa): array
    {
        $relacoesBase = ['evento.movimento:idt_movimento,des_sigla'];

        $trabalhos = Trabalhador::where('idt_pessoa', $pessoa->idt_pessoa)
            ->whereHas('evento')
            ->with(array_merge($relacoesBase, ['equipe:idt_equipe,des_grupo']))
            ->get()
            ->map(fn ($t) => $this->formataTimeline($t, 'Trabalhador'));

        $participacoes = Participante::where('idt_pessoa', $pessoa->idt_pessoa)
            ->whereHas('evento')
            ->with($relacoesBase)
            ->get()
            ->map(fn ($p) => $this->formataTimeline($p, 'Participante'));

        $timeline = $trabalhos->concat($participacoes)->sortByDesc('date');

        return $this->agruparEventosPorAno($timeline);
    }

    private function agruparEventosPorAno(Collection $eventos): array
    {
        return $eventos
            ->groupBy(fn ($entry) => Carbon::parse($entry['date'])->year) // Agrupa por ano
            ->sortKeysDesc() // Garante o mais recente primeiro
            ->map(fn ($yearEntries, $year) => [
                'year' => $year,
                'events' => $yearEntries->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function formataTimeline($model, string $type): array
    {
        // Verifica se o relacionamento 'evento' existe
        if (! $model->evento) {
            return [
                'type' => $type,
                'date' => null,
                'event' => null,
                'details' => [
                    'equipe' => $model->equipe->des_grupo ?? null,
                    'coordenador' => $model->ind_coordenador ?? false,
                ],
            ];
        }

        return [
            'type' => $type,
            'date' => $model->evento->dat_inicio,
            'event' => $model->evento,
            'details' => [
                'equipe' => $model->equipe->des_grupo ?? null,
                'coordenador' => $model->ind_coordenador ?? false,
            ],
        ];
    }

    public function calcularRanking(Pessoa $pessoa): int
    {
        // Se a pessoa não tem pontos, está na última posição (count total + 1)
        if (! $pessoa->qtd_pontos_total) {
            return Pessoa::count();
        }

        return Pessoa::where('qtd_pontos_total', '>', $pessoa->qtd_pontos_total)
            ->count() + 1;
    }

    public function confirmarParticipacao(Evento $evento, Pessoa $pessoa): Participante
    {
        return Participante::firstOrCreate([
            'idt_evento' => $evento->idt_evento,
            'idt_pessoa' => $pessoa->idt_pessoa,
        ]);
    }
}
