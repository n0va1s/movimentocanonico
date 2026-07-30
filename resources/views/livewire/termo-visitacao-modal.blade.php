<?php

use Livewire\Volt\Component;

new class extends Component {
    public ?int $idtEvento = null;
    public bool $openModal = false;

    public function mount(?int $idtEvento = null): void
    {
        if ($idtEvento) {
            $this->idtEvento = $idtEvento;
        }

        $user = auth()->user();
        if ($user && $user->autorizaVisit() && !$user->isAdmin() && !$user->isDirig()) {
            if (!$this->idtEvento) {
                $route = request()->route();
                $fichaId = $route?->parameter('vem') ?? $route?->parameter('ecc') ?? $route?->parameter('sgm') ?? $route?->parameter('ficha');
                $eventoParam = $route?->parameter('evento');

                if ($eventoParam) {
                    $this->idtEvento = is_numeric($eventoParam) ? (int) $eventoParam : $eventoParam->idt_evento;
                } elseif ($fichaId) {
                    $ficha = is_numeric($fichaId) ? \App\Models\Ficha::find($fichaId) : $fichaId;
                    $this->idtEvento = $ficha?->idt_evento;
                } else {
                    $this->idtEvento = \App\Models\Trabalhador::where('idt_pessoa', $user->pessoa?->idt_pessoa)
                        ->whereHas('equipe', function ($q) {
                            $q->whereRaw('LOWER(des_grupo) LIKE ?', ['%visita%']);
                        })->latest('idt_trabalhador')->value('idt_evento');
                }
            }

            if (!$user->hasAceitouTermoVisitacao($this->idtEvento)) {
                $this->openModal = true;
            }
        }
    }

    public function aceitar(): void
    {
        $user = auth()->user();
        if ($user) {
            $user->registrarAceiteTermoVisitacao($this->idtEvento, request()->ip());
            $this->openModal = false;
            $this->dispatch('notify', message: 'Termo de Confidencialidade aceito com sucesso.', type: 'sucesso');
            \Flux::toast('Termo de Confidencialidade aceito com sucesso.', variant: 'success');
        }
    }

    public function recusar()
    {
        return redirect()->route('minhas-fichas.index');
    }
}; ?>

<div>
    @if ($openModal)
        <div class="fixed inset-0 z-[999] flex items-center justify-center bg-zinc-900/80 backdrop-blur-sm p-4 sm:p-6 overflow-y-auto">
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-2xl shadow-2xl max-w-2xl w-full p-6 sm:p-8 space-y-6 relative transition-all animate-in fade-in zoom-in duration-200">
                {{-- Header --}}
                <div class="flex items-center gap-3 border-b border-zinc-100 dark:border-zinc-700/60 pb-4">
                    <div class="p-3 bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl shrink-0">
                        <flux:icon name="shield-check" class="size-6" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="text-zinc-900 dark:text-zinc-100 font-bold">
                            Termo de Confidencialidade e Proteção de Dados (LGPD)
                        </flux:heading>
                        <flux:subheading class="text-xs text-zinc-500 dark:text-zinc-400">
                            Acesso restrito à Equipe de Visitação
                        </flux:subheading>
                    </div>
                </div>

                {{-- Corpo do Termo --}}
                <div class="space-y-4 text-sm text-zinc-700 dark:text-zinc-300 leading-relaxed bg-zinc-50 dark:bg-zinc-900/50 p-4 sm:p-5 rounded-xl border border-zinc-200/80 dark:border-zinc-700/50 max-h-72 overflow-y-auto">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                        Declaro, para os devidos fins de conformidade com a <strong>Lei Geral de Proteção de Dados (Lei nº 13.709/2018)</strong>, que acessarei e utilizarei as informações pessoais, de contato e de saúde contidas nas fichas de inscrição <strong>ESTREITAMENTE PARA FINS DE CONSULTA, CONTATO E ACOMPANHAMENTO DA VISITAÇÃO</strong> dos candidatos do Encontro.
                    </p>
                    <p>
                        Comprometo-me a manter total sigilo e confidencialidade sobre todos os dados acessados, declarando que <strong>não irei copiar, armazenar externamente, compartilhar, divulgar ou utilizar estas informações para qualquer outra finalidade</strong> alheia ao serviço de visitação do movimento.
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 border-t border-zinc-200 dark:border-zinc-700/50 pt-3">
                        • Este aceite será registrado especificamente para a equipe de visitação deste evento, contendo seu usuário, data/hora e IP para fins de auditoria e segurança jurídica.
                    </p>
                </div>

                {{-- Ações --}}
                <div class="flex flex-col-reverse sm:flex-row gap-3 justify-end pt-2">
                    <flux:button wire:click="recusar" variant="outline" class="w-full sm:w-auto">
                        Negar e Voltar
                    </flux:button>
                    <flux:button wire:click="aceitar" variant="primary" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                        Confirmar e Continuar
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
