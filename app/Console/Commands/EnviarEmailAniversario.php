<?php

namespace App\Console\Commands;

use App\Mail\AniversarioMail;
use App\Models\Pessoa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailAniversario extends Command
{
    /**
     * A assinatura do comando permanece a mesma para manter o vínculo com o Scheduler.
     */
    protected $signature = 'mov:enviar-emails-aniversario';

    protected $description = 'Identifica aniversariantes do dia e dispara e-mails de felicidades';

    public function handle()
    {
        $hoje = now();
        $this->info("Iniciando processamento de aniversariantes para: {$hoje->format('d/m')}");

        $query = Pessoa::whereMonth('dat_nascimento', $hoje->month)
            ->whereDay('dat_nascimento', $hoje->day)
            ->whereNotNull('eml_pessoa')
            ->where('eml_pessoa', '<>', '');

        if ($query->count() === 0) {
            $this->info('Nenhum aniversariante encontrado para o dia de hoje.');

            return;
        }

        $query->chunk(100, function ($pessoas) {
            foreach ($pessoas as $pessoa) {
                // Validação de formato de e-mail para evitar exceções do Mailer
                if (! filter_var($pessoa->eml_pessoa, FILTER_VALIDATE_EMAIL)) {
                    $this->warn("E-mail inválido ignorado: {$pessoa->eml_pessoa} (ID: {$pessoa->idt_pessoa})");

                    continue;
                }

                try {
                    Mail::to($pessoa->eml_pessoa)->send(
                        new AniversarioMail([
                            'nome' => $pessoa->nom_pessoa,
                        ])
                    );

                    $this->line("<info>Sucesso:</info> {$pessoa->nom_pessoa} ({$pessoa->eml_pessoa})");
                } catch (\Exception $e) {
                    $this->error("Falha ao enviar para {$pessoa->eml_pessoa}: {$e->getMessage()}");

                    Log::error('Erro no envio de e-mail de aniversário', [
                        'pessoa_id' => $pessoa->idt_pessoa,
                        'email' => $pessoa->eml_pessoa,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info('Processo de envio de e-mails de aniversário concluído.');
    }
}
