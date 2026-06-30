<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Ficha;
use App\Models\Participante;
use App\Models\Trabalhador;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View; // Adicionado para type hint

class DashboardController extends Controller
{
    use LogContext;

    /**
     * Exibe o dashboard principal com contadores e listas de itens recentes.
     */
    // DashboardController.php
    public function index(Request $request): View
    {
        $start = microtime(true);

        // Otimização 1: Eager Loading com colunas específicas para reduzir memória
        $proximoseventos = Evento::with(['movimento:idt_movimento,des_sigla'])
            ->where('dat_inicio', '>=', now())
            ->when(auth()->user()->isDirig(), function ($q) {
                $q->where('idt_movimento', auth()->user()->idt_movimento);
            })
            ->orderBy('dat_inicio', 'asc')
            ->take(5)
            ->select('idt_evento', 'des_evento', 'dat_inicio', 'idt_movimento')
            ->get();

        // Otimização 3: Queries de contagem simples
        $qtdEventosAtivos = Evento::where('dat_termino', '>=', today())
            ->when(auth()->user()->isDirig(), function ($q) {
                $q->where('idt_movimento', auth()->user()->idt_movimento);
            })
            ->count();

        $qtdFichasCadastradas = Ficha::when(auth()->user()->isDirig(), function ($q) {
            $q->whereHas('evento', function ($eq) {
                $eq->where('idt_movimento', auth()->user()->idt_movimento);
            });
        })->count();

        // Otimização 4: Se o banco crescer muito, considere Cache::remember nestes contadores de distinct
        $qtdParticipantesCadastrados = Participante::distinct('idt_pessoa')
            ->when(auth()->user()->isDirig(), function ($q) {
                $q->whereHas('evento', function ($eq) {
                    $eq->where('idt_movimento', auth()->user()->idt_movimento);
                });
            })
            ->count('idt_pessoa');

        $qtdTrabalhadoresCadastrados = Trabalhador::distinct('idt_pessoa')
            ->when(auth()->user()->isDirig(), function ($q) {
                $q->whereHas('evento', function ($eq) {
                    $eq->where('idt_movimento', auth()->user()->idt_movimento);
                });
            })
            ->count('idt_pessoa');

        $pessoa = auth()->user()->pessoa;
        $contaMercadinho = null;
        if ($pessoa) {
            $contaMercadinho = \App\Models\Conta::with(['evento', 'transacoes' => fn($q) => $q->orderBy('dat_transacao', 'desc')])
                ->where('idt_pessoa', $pessoa->idt_pessoa)
                ->whereHas('evento', function($q) {
                    $q->where('dat_termino', '>=', today()->subDays(7));
                })
                ->orderByDesc('created_at')
                ->first();
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Dashboard carregado', ['duration_ms' => $duration]);

        return view('dashboard', compact(
            'proximoseventos',
            'qtdEventosAtivos',
            'qtdFichasCadastradas',
            'qtdParticipantesCadastrados',
            'qtdTrabalhadoresCadastrados',
            'contaMercadinho'
        ));
    }
}
