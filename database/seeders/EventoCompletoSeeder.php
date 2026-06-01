<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\Ficha;
use App\Models\FichaVem;
use App\Models\Pessoa;
use App\Models\TipoMovimento;
use App\Models\User;
use App\Models\Voluntario;
use Illuminate\Database\Seeder;

class EventoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar o Evento Principal
        $evento = Evento::factory()->create([
            'tip_evento' => 'E',
            'idt_movimento' => TipoMovimento::VEM,
            'des_evento' => 'Grande Encontro Nacional VEM',
            'num_evento' => 1,
            'dat_inicio' => '2026-07-10',
            'dat_termino' => '2026-07-12',
        ]);

        $this->command->info("Evento '{$evento->des_evento}' criado.");

        // 2. Obter ou criar usuário para auditoria (usu_inclusao)
        $user = User::first() ?? User::factory()->create();

        // 3. Gerar 500 Voluntários
        $this->command->info('Gerando 500 voluntários...');

        // Criamos 500 pessoas e as vinculamos como voluntários do evento
        Pessoa::factory()->count(500)->create()->each(function ($pessoa) use ($evento) {
            Voluntario::factory()->create([
                'idt_pessoa' => $pessoa->idt_pessoa,
                'idt_evento' => $evento->idt_evento,
                'txt_habilidade' => 'Voluntário com habilidade principal em: '.$pessoa->tip_habilidade->name,
            ]);
        });

        $this->command->info('500 voluntários vinculados ao evento.');

        // 4. Gerar 200 Fichas vinculadas a novas Pessoas
        $this->command->info('Gerando 200 fichas VEM...');

        Pessoa::factory()->count(200)->create()->each(function ($pessoa) use ($evento, $user) {
            $ficha = Ficha::factory()->create([
                'idt_pessoa' => $pessoa->idt_pessoa,
                'idt_evento' => $evento->idt_evento,
                'nom_candidato' => $pessoa->nom_pessoa,
                'nom_apelido' => $pessoa->nom_apelido,
                'eml_candidato' => $pessoa->eml_pessoa,
                'tel_candidato' => $pessoa->tel_pessoa,
                'dat_nascimento' => $pessoa->dat_nascimento,
                'tip_genero' => $pessoa->tip_genero,
                'des_endereco' => $pessoa->des_endereco,
                'tam_camiseta' => $pessoa->tam_camiseta,
                'ind_aprovado' => false,
                'usu_inclusao' => $user->id,
                'usu_alteracao' => $user->id,
            ]);

            FichaVem::factory()->create([
                'idt_ficha' => $ficha->idt_ficha,
            ]);
        });

        $this->command->info('Cenário completo gerado: 1 Evento, 500 Voluntários e 200 Fichas VEM.');
    }
}
