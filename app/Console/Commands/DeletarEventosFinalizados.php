<?php

namespace App\Console\Commands;

use App\Models\Evento;
use Illuminate\Console\Command;

class DeletarEventosFinalizados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mov:deletar-eventos-finalizados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Buscamos eventos finalizados e ainda não foram deletados
        $eventosExpirados = Evento::where(
            'dat_termino',
            '<=',
            now()
        )->whereNull('deleted_at')
            ->get();

        foreach ($eventosExpirados as $evento) {
            // update em vez de delete para usar a data terminino e nao o now()
            $evento->update([
                'deleted_at' => $evento->dat_termino,
            ]);
        }

        $this->info($eventosExpirados->count().' eventos foram encerrados.');
    }
}
