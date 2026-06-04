<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $importService
    ) {}

    /**
     * Exibe a interface de importação com a listagem de eventos ativos.
     */
    public function index(): View
    {
        $hoje = now()->startOfDay();
        
        // Encontra eventos ativos (em andamento ou futuros)
        $eventosAtivos = Evento::where(function ($q) use ($hoje) {
            $q->where('dat_inicio', '>=', $hoje)
              ->orWhere('dat_termino', '>=', $hoje)
              ->orWhereNull('dat_termino');
        })
        ->with('movimento')
        ->orderBy('dat_inicio', 'asc')
        ->get();

        return view('evento.import', compact('eventosAtivos'));
    }

    /**
     * Processa a importação de participantes.
     */
    public function importarParticipantes(Request $request): RedirectResponse
    {
        $request->validate([
            'evento_id' => 'required|exists:evento,idt_evento',
            'arquivo_participantes' => 'required|file',
        ], [
            'evento_id.required' => 'Por favor, selecione um evento ativo.',
            'evento_id.exists' => 'O evento selecionado não existe.',
            'arquivo_participantes.required' => 'Por favor, selecione um arquivo CSV de participantes.',
            'arquivo_participantes.file' => 'O arquivo enviado não é válido.',
        ]);

        try {
            $evento = Evento::findOrFail($request->evento_id);
            $file = $request->file('arquivo_participantes');
            $filePath = $file->getRealPath();

            $result = $this->importService->importarParticipantes($evento, $filePath);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            $stats = $result['stats'];
            $successMsg = "Importação de Participantes concluída com sucesso!\n" .
                "• Lote processado em blocos de 50 registros.\n" .
                "• Linhas lidas: {$stats['total_rows']}\n" .
                "• Pessoas criadas: {$stats['created']}\n" .
                "• Pessoas atualizadas: {$stats['updated']}\n" .
                "• Vínculos criados/atualizados: {$stats['linked']}\n" .
                "• Erros/Avisos: {$stats['errors']}\n" .
                "O relatório detalhado está disponível em: storage/logs/import_participantes.log";

            return redirect()->route('eventos.importar')->with('success', $successMsg);

        } catch (\Throwable $e) {
            Log::error('Erro ao importar participantes no controller: ' . $e->getMessage());
            return back()->with('error', 'Ocorreu um erro no processamento do arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Processa a importação de trabalhadores.
     */
    public function importarTrabalhadores(Request $request): RedirectResponse
    {
        $request->validate([
            'evento_id' => 'required|exists:evento,idt_evento',
            'arquivo_trabalhadores' => 'required|file',
        ], [
            'evento_id.required' => 'Por favor, selecione um evento ativo.',
            'evento_id.exists' => 'O evento selecionado não existe.',
            'arquivo_trabalhadores.required' => 'Por favor, selecione um arquivo CSV de trabalhadores.',
            'arquivo_trabalhadores.file' => 'O arquivo enviado não é válido.',
        ]);

        try {
            $evento = Evento::findOrFail($request->evento_id);
            $file = $request->file('arquivo_trabalhadores');
            $filePath = $file->getRealPath();

            $result = $this->importService->importarTrabalhadores($evento, $filePath);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            $stats = $result['stats'];
            $successMsg = "Importação de Trabalhadores concluída com sucesso!\n" .
                "• Lote processado em blocos de 50 registros.\n" .
                "• Linhas lidas: {$stats['total_rows']}\n" .
                "• Pessoas criadas: {$stats['created']}\n" .
                "• Pessoas atualizadas: {$stats['updated']}\n" .
                "• Vínculos criados/atualizados: {$stats['linked']}\n" .
                "• Erros/Avisos: {$stats['errors']}\n" .
                "O relatório detalhado está disponível em: storage/logs/import_trabalhadores.log";

            return redirect()->route('eventos.importar')->with('success', $successMsg);

        } catch (\Throwable $e) {
            Log::error('Erro ao importar trabalhadores no controller: ' . $e->getMessage());
            return back()->with('error', 'Ocorreu um erro no processamento do arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Fornece download do CSV modelo de participantes.
     */
    public function downloadModeloParticipantes()
    {
        $headers = ['CPF', 'Nome', 'Apelido', 'Telefone', 'Email', 'Data Nascimento', 'Genero', 'Tamanho Camiseta', 'Endereco', 'Cor Troca', 'Taxa Pagou', 'Presente'];
        $sample = ['123.456.789-01', 'Maria da Silva', 'Mari', '(61) 99999-9999', 'maria@gmail.com', '15/05/1995', 'F', 'M', 'SHIN QL 12 Conjunto 5 Casa 3', 'azul', 'Sim', 'Sim'];
        
        return $this->gerarCsvResponse('modelo_participantes.csv', $headers, $sample);
    }

    /**
     * Fornece download do CSV modelo de trabalhadores.
     */
    public function downloadModeloTrabalhadores()
    {
        $headers = ['CPF', 'Nome', 'Apelido', 'Telefone', 'Email', 'Data Nascimento', 'Genero', 'Tamanho Camiseta', 'Endereco', 'Equipe', 'Coordenador', 'Primeira Vez', 'Recomendado', 'Lideranca', 'Destaque', 'Avaliacao', 'Camiseta Pediu', 'Camiseta Pagou', 'Taxa Pagou', 'Presente'];
        $sample = ['987.654.321-00', 'João de Souza', 'Joca', '(61) 98888-8888', 'joao@gmail.com', '20/10/1990', 'M', 'G', 'SQN 205 Bloco C Apto 401', 'Bandinha', 'Não', 'Não', 'Sim', 'Sim', 'Não', 'Sim', 'Sim', 'Sim', 'Sim', 'Sim'];
        
        return $this->gerarCsvResponse('modelo_trabalhadores.csv', $headers, $sample);
    }

    /**
     * Gera e envia arquivo CSV via stream.
     */
    private function gerarCsvResponse(string $filename, array $headers, array $sample)
    {
        $callback = function() use ($headers, $sample) {
            $file = fopen('php://output', 'w');
            // Adiciona BOM do UTF-8 para Excel abrir com codificação correta
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers, ';');
            fputcsv($file, $sample, ';');
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
