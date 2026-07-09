<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Participante;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use LogContext;

    /**
     * Exibe o dashboard principal com aniversariantes, líderes de Aura,
     * próximos eventos e contador de pessoas evangelizadas.
     */
    public function index(Request $request): View
    {
        $start = microtime(true);

        // ── Próximos Eventos ────────────────────────────────────────────
        $proximoseventos = Evento::with(['movimento:idt_movimento,des_sigla'])
            ->where('dat_inicio', '>=', today())
            ->when(auth()->user()->isEspec(), function ($q) {
                $q->where('idt_movimento', auth()->user()->idt_movimento);
            })
            ->orderBy('dat_inicio', 'asc')
            ->take(5)
            ->select('idt_evento', 'des_evento', 'dat_inicio', 'idt_movimento')
            ->get();

        // ── Pessoas Evangelizadas (distinct participantes) ──────────────
        $qtdParticipantesCadastrados = Participante::distinct('idt_pessoa')
            ->when(auth()->user()->isEspec(), function ($q) {
                $q->whereHas('evento', function ($eq) {
                    $eq->where('idt_movimento', auth()->user()->idt_movimento);
                });
            })
            ->count('idt_pessoa');

        // ── Aniversariantes da Semana ───────────────────────────────────
        $daysOfWeek = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->startOfWeek()->addDays($i);
            $daysOfWeek[] = [
                'month' => $date->month,
                'day' => $date->day,
            ];
        }

        $startOfWeek = now()->startOfWeek();
        $aniversariantes = Pessoa::whereNotNull('dat_nascimento')
            ->where(function ($query) use ($daysOfWeek) {
                foreach ($daysOfWeek as $day) {
                    $query->orWhere(function ($q) use ($day) {
                        $q->whereMonth('dat_nascimento', $day['month'])
                          ->whereDay('dat_nascimento', $day['day']);
                    });
                }
            })
            ->with('foto')
            ->get()
            ->sortBy(function ($pessoa) use ($startOfWeek) {
                $birthDate = $pessoa->dat_nascimento;
                for ($i = 0; $i < 7; $i++) {
                    $date = $startOfWeek->clone()->addDays($i);
                    if ($birthDate->month === $date->month && $birthDate->day === $date->day) {
                        return $i;
                    }
                }
                return 99;
            })
            ->values();

        // ── Líderes de Aura (Top 10 pontuação) ─────────────────────────
        $lideresAura = Pessoa::where('qtd_pontos_total', '>', 0)
            ->with('foto')
            ->orderByDesc('qtd_pontos_total')
            ->take(10)
            ->get();

        // ── Movimentos (para o formulário de contato) ───────────────────
        $movimentos = TipoMovimento::select(
            'idt_movimento',
            'nom_movimento',
            'des_sigla',
            'ind_inscricao_aberta'
        )->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Dashboard carregado', ['duration_ms' => $duration]);

        return view('dashboard', compact(
            'proximoseventos',
            'qtdParticipantesCadastrados',
            'aniversariantes',
            'lideresAura',
            'movimentos'
        ));
    }
}
