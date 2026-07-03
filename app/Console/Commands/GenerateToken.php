<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sanctum:generate-token {email : O e-mail do usuário para o qual gerar o token} {--name=api-token : Nome descritivo do token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um token de acesso pessoal (Sanctum) para um determinado usuário';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $tokenName = $this->option('name');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuário com o e-mail '{$email}' não foi encontrado.");
            return Command::FAILURE;
        }

        // Criar o token usando Sanctum
        $tokenResult = $user->createToken($tokenName);

        $this->info("Token criado com sucesso para o usuário: {$user->name} ({$user->email})");
        $this->comment("Nome do Token: {$tokenName}");
        $this->line("");
        $this->info("Token em texto plano (copie e guarde em local seguro, pois não será exibido novamente):");
        $this->warn($tokenResult->plainTextToken);
        $this->line("");

        return Command::SUCCESS;
    }
}
