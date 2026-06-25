<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Notifications\NovoContatoTelegram;
use App\Traits\LogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContatoController extends Controller
{
    // Trait para evitar duplicacao de codigo
    use LogContext;

    public function index(Request $request): View
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $search = $request->get('search');

        Log::info('Requisição de listagem de contatos iniciada', array_merge($context, [
            'search' => $search,
        ]));

        $contatos = Contato::with(['movimento'])
            ->when($search, function ($query, $search) {
                return $query->search($search);
            })
            ->select(
                'idt_contato',
                'nom_contato',
                'eml_contato',
                'tel_contato',
                'txt_mensagem',
                'idt_movimento'
            )
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $duration = round((microtime(true) - $start) * 1000, 2);

        Log::notice('Listagem de contatos concluída com sucesso', array_merge($context, [
            'total_contatos' => $contatos->total(),
            'duration_ms' => $duration,
        ]));

        return view(
            'contato.list',
            compact(
                'contatos',
                'search'
            )
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom_contato' => 'required|string|max:255',
            'eml_contato' => 'required|email|max:255',
            'tel_contato' => 'nullable|string|max:20',
            'txt_mensagem' => 'required|string',
            'idt_movimento' => 'required|exists:tipo_movimento,idt_movimento',
        ]);

        $contato = Contato::create($data);

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

        return back()->with('success', 'Mensagem enviada com sucesso!');
    }

    public function destroy($id): RedirectResponse
    {
        $start = microtime(true);
        $context = $this->getLogContext(request());

        Log::warning('Tentativa de exclusão de contato', array_merge($context, [
            'contato_id' => $id,
        ]));

        $contato = Contato::findOrFail($id);

        try {
            $contato->delete();

            $duration = round((microtime(true) - $start) * 1000, 2);
            Log::notice('Contato excluído com sucesso', array_merge($context, [
                'contato_id' => $id,
                'duration_ms' => $duration,
            ]));

            return redirect()->route('contatos.index')
                ->with('success', 'Contato resolvido com sucesso!');
        } catch (\Throwable $e) {

            $duration = round((microtime(true) - $start) * 1000, 2);

            Log::error('Erro ao excluir contato', array_merge($context, [
                'contato_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'duration_ms' => $duration,
            ]));

            return redirect()->route('contatos.index')
                ->with('error', 'Erro ao resolver o contato. Verifique se há vínculos.');
        }
    }
}
