<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaSGM;
use App\Models\TipoMovimento;
use App\Notifications\NovoContatoTelegram;
use App\Services\FichaService;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class HomeController extends Controller
{
    use LogContext;

    protected $fichaService;

    public function __construct(FichaService $fichaService)
    {
        $this->fichaService = $fichaService;
    }

    public function index()
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::info('Acesso à página inicial (index)', $context);

        $movimentos = TipoMovimento::select(
            'idt_movimento',
            'nom_movimento',
            'des_sigla'
        )->get();

        $proximoseventos = Evento::with(['movimento'])
            ->where('dat_inicio', '>=', now())
            ->orderBy('dat_inicio', 'asc')
            ->take(5)
            ->select('idt_evento', 'des_evento', 'dat_inicio', 'idt_movimento')
            ->get();

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Carregamento da página inicial concluído', array_merge($context, [
            'total_movimentos' => $movimentos->count(),
            'proximos_eventos' => $proximoseventos->pluck('idt_evento')->toArray(),
            'duration_ms' => $duration,
        ]));

        return view('welcome', compact('proximoseventos', 'movimentos'));
    }

    public function contato(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::info('Tentativa de registro de contato', array_merge($context, [
            'contato_nome' => $request->input('nom_contato'),
            'contato_movimento' => $request->input('idt_movimento'),
        ]));

        $data = $request->validate([
            'nom_contato' => 'required|string|max:255',
            'eml_contato' => 'nullable|email|max:255',
            'tel_contato' => 'required|string|max:20',
            'txt_mensagem' => 'required|string|max:1000',
            'idt_movimento' => 'required|exists:tipo_movimento,idt_movimento',
        ]);

        $contato = Contato::create($data);

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Contato registrado com sucesso', array_merge($context, [
            'movimento_id' => $data['idt_movimento'],
            'duration_ms' => $duration,
        ]));

        $chatIds = explode(',', config('services.telegram-bot-api.chat_id', ''));
        foreach ($chatIds as $chatId) {
            if (trim($chatId)) {
                try {
                    Notification::route('telegram', trim($chatId))
                        ->notify(new NovoContatoTelegram($contato));
                } catch (\Throwable $e) {
                    Log::error("Erro ao enviar notificação pro Telegram (Chat ID: {$chatId}): ".$e->getMessage());
                }
            }
        }

        return redirect()->route('home')->with('success', 'Recebemos seu contato. Em breve retornaremos!');
    }

    public function fichaVem()
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário público de ficha VEM', $context);

        $ficha = new Ficha;
        $ficha->idt_movimento = TipoMovimento::VEM;
        $eventos = Evento::porTipo(TipoMovimento::VEM, 'E', 3)->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Contato registrado com sucesso', array_merge($context, [
            'movimento_id' => $ficha->idt_movimento,
            'duration_ms' => $duration,
        ]));

        return view('ficha.formVEM', array_merge(FichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => $eventos,
            'movimentopadrao' => TipoMovimento::VEM,
        ]));
    }

    public function fichaEcc()
    {
        $context = $this->getLogContext(request());
        Log::info('Acesso ao formulário público de ficha ECC', $context);

        $ficha = new Ficha;
        $ficha->idt_movimento = TipoMovimento::ECC;
        $eventos = Evento::porTipo(TipoMovimento::ECC, 'E', 3)->get();

        return view('ficha.formECC', array_merge(FichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => $eventos,
            'movimentopadrao' => TipoMovimento::ECC,
        ]));
    }

    public function fichaSgm()
    {
        $ficha = new Ficha;
        $ficha->idt_movimento = TipoMovimento::SGM;
        $ficha->setRelation('fichaSGM', new FichaSGM);
        $eventos = Evento::porTipo(TipoMovimento::SGM, 'E', 3)->get();

        return view('ficha.formSGM', array_merge(FichaService::dadosFixosFicha($ficha), [
            'ficha' => $ficha,
            'eventos' => $eventos,
            'movimentopadrao' => TipoMovimento::SGM,
        ]));
    }
}
