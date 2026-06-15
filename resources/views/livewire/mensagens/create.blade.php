<?php

use App\Models\Evento;
use App\Models\Mensagem;
use App\Models\MensagemEnvio;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;

new class extends Component {
    public ?int $eventoId = null;
    public string $nom_campanha = '';
    public string $txt_mensagem = '';
    public string $tip_destinatario = 'P'; // P - Participante, R - Responsável

    public int $previewSpinCounter = 0; // Utilizado para forçar atualização do spintax na prévia

    public function mount(): void
    {
        $primeiroEvento = Evento::orderBy('dat_inicio', 'desc')->first();
        if ($primeiroEvento) {
            $this->eventoId = $primeiroEvento->idt_evento;
        }
        $this->txt_mensagem = "{Olá|Oi|Tudo bem?}, {nome}! Confirmamos que seu evento {evento} está chegando.";
        $this->nom_campanha = "Informativo Geral - " . ($primeiroEvento ? $primeiroEvento->des_evento : '');
    }

    public function updatedEventoId($value): void
    {
        $evento = Evento::find($value);
        if ($evento) {
            $this->nom_campanha = "Informativo Geral - " . $evento->des_evento;
        }
    }

    public function girarPrevia(): void
    {
        $this->previewSpinCounter++;
    }

    #[Computed]
    public function eventos(): \Illuminate\Database\Eloquent\Collection
    {
        return Evento::with('movimento')->orderBy('dat_inicio', 'desc')->get();
    }

    #[Computed]
    public function destinatariosEstimados(): array
    {
        return $this->obterDestinatarios();
    }

    #[Computed]
    public function previewText(): string
    {
        // Linha adicionada para depender do contador reativo
        $spin = $this->previewSpinCounter;

        if (!$this->txt_mensagem) {
            return 'Digite uma mensagem para ver a prévia...';
        }

        $data = [
            'nome' => 'Maria Silva',
            'apelido' => 'Mari',
            'evento' => 'Evento Exemplo',
            'participante' => 'Joãozinho',
            'responsavel_nome' => 'Maria Silva',
        ];

        if ($this->eventoId) {
            $evento = Evento::find($this->eventoId);
            $data['evento'] = $evento->des_evento;

            $dest = $this->obterDestinatarios();
            if (!empty($dest)) {
                $primeiro = $dest[0];
                $data['nome'] = $primeiro['nom_destinatario'];
                $data['responsavel_nome'] = $primeiro['nom_destinatario'];
                if ($this->tip_destinatario === 'R') {
                    $data['participante'] = $primeiro['nom_responsavel']; // Nome do participante associado
                }
            }
        }

        return Mensagem::formatar($this->txt_mensagem, $data);
    }

    public function criarCampanha()
    {
        $this->validate([
            'eventoId' => 'required|exists:evento,idt_evento',
            'nom_campanha' => 'required|string|max:150',
            'txt_mensagem' => 'required|string',
            'tip_destinatario' => 'required|in:P,R',
        ]);

        $destinatarios = $this->obterDestinatarios();

        if (count($destinatarios) === 0) {
            $this->dispatch('notify', message: 'Nenhum destinatário com telefone válido foi encontrado para este evento/público-alvo.', variant: 'error');
            return;
        }

        // Criar registro da mensagem auditoria
        $mensagem = Mensagem::create([
            'idt_evento' => $this->eventoId,
            'usu_inclusao' => auth()->id(),
            'nom_campanha' => $this->nom_campanha,
            'txt_mensagem' => $this->txt_mensagem,
            'tip_destinatario' => $this->tip_destinatario,
            'qtd_impactados' => count($destinatarios),
        ]);

        // Criar destinatários de envio
        foreach ($destinatarios as $dest) {
            MensagemEnvio::create([
                'idt_mensagem' => $mensagem->idt_mensagem,
                'nom_destinatario' => $dest['nom_destinatario'],
                'tel_destinatario' => $dest['tel_destinatario'],
                'nom_responsavel' => $dest['nom_responsavel'],
                'ind_enviado' => false,
                'dat_envio' => null,
            ]);
        }

        $this->dispatch('notify', message: 'Envio de Mensagens configurado com sucesso!');

        return redirect()->route('mensagens.show', ['mensagem' => $mensagem->idt_mensagem]);
    }

    private function obterDestinatarios(): array
    {
        if (!$this->eventoId) {
            return [];
        }

        $participantes = \App\Models\Participante::where('idt_evento', $this->eventoId)
            ->with([
                'pessoa.fichas' => function ($query) {
                    $query->where('idt_evento', $this->eventoId)
                        ->with(['fichaVem', 'fichaSGM', 'fichaEcc']);
                }
            ])
            ->get();

        $destinatarios = [];

        foreach ($participantes as $p) {
            $pessoa = $p->pessoa;
            if (!$pessoa) {
                continue;
            }

            if ($this->tip_destinatario === 'P') {
                $telefone = $pessoa->tel_pessoa;
                if ($telefone) {
                    $destinatarios[] = [
                        'nom_destinatario' => $pessoa->nom_pessoa,
                        'tel_destinatario' => \App\Services\PhoneService::clean($telefone),
                        'nom_responsavel' => null,
                    ];
                }
            } else {
                $ficha = $pessoa->fichas->first();
                $respName = null;
                $respPhone = null;

                if ($ficha) {
                    if ($ficha->fichaVem) {
                        $fv = $ficha->fichaVem;
                        if (!empty($fv->tel_responsavel)) {
                            $respPhone = $fv->tel_responsavel;
                            $respName = $fv->nom_responsavel;
                        } elseif (!empty($fv->tel_mae)) {
                            $respPhone = $fv->tel_mae;
                            $respName = $fv->nom_mae;
                        } elseif (!empty($fv->tel_pai)) {
                            $respPhone = $fv->tel_pai;
                            $respName = $fv->nom_pai;
                        }
                    } elseif ($ficha->fichaSGM) {
                        $fs = $ficha->fichaSGM;
                        if (!empty($fs->tel_falar_com)) {
                            $respPhone = $fs->tel_falar_com;
                            $respName = $fs->nom_falar_com;
                        } elseif (!empty($fs->tel_mae)) {
                            $respPhone = $fs->tel_mae;
                            $respName = $fs->nom_mae;
                        } elseif (!empty($fs->tel_pai)) {
                            $respPhone = $fs->tel_pai;
                            $respName = $fs->nom_pai;
                        }
                    } elseif ($ficha->fichaEcc) {
                        $fe = $ficha->fichaEcc;
                        if (!empty($fe->tel_conjuge)) {
                            $respPhone = $fe->tel_conjuge;
                            $respName = $fe->nom_conjuge;
                        }
                    }
                }

                if ($respPhone) {
                    $destinatarios[] = [
                        'nom_destinatario' => $respName ?: 'Responsável',
                        'tel_destinatario' => \App\Services\PhoneService::clean($respPhone),
                        'nom_responsavel' => $pessoa->nom_pessoa, // Vincula o participante como a relação
                    ];
                }
            }
        }

        return $destinatarios;
    }
}; ?>

<div class="space-y-6 w-full max-w-4xl mx-auto p-4 md:p-8">
    {{-- Breadcrumbs / Voltar --}}
    <div class="flex items-center gap-2 text-sm text-zinc-500">
        <a href="{{ route('mensagens.index') }}" class="hover:underline" wire:navigate>Mensagens</a>
        <span>/</span>
        <span>Nova Mensagem</span>
    </div>

    {{-- Header --}}
    <div>
        <flux:heading size="xl">Configurar Nova Mensagem</flux:heading>
        <flux:subheading>Escolha o evento, defina o público e elabore um modelo com mensagens rotativas (Spintax) anti-bloqueio.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
        {{-- Form --}}
        <flux:card class="xl:col-span-2 space-y-6 border border-zinc-200 dark:border-zinc-700 shadow-sm rounded-xl">
            <form wire:submit="criarCampanha" class="space-y-6">
                {{-- Evento --}}
                <flux:select wire:model.live="eventoId" label="Evento Associado">
                    <option value="">Selecione um evento...</option>
                    @foreach ($this->eventos as $e)
                        <option value="{{ $e->idt_evento }}">
                            {{ $e->des_evento }} ({{ $e->movimento->des_sigla }})
                        </option>
                    @endforeach
                </flux:select>

                {{-- Target --}}
                <flux:radio.group wire:model.live="tip_destinatario" label="Público-Alvo" variant="cards" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:radio value="P" label="Participantes" description="Envia diretamente aos contatos dos confirmados no evento." />
                    <flux:radio value="R" label="Responsáveis" description="Envia para os pais, contatos de emergência ou cônjuges obtidos nas fichas." />
                </flux:radio.group>

                {{-- Título da Campanha --}}
                <flux:input wire:model="nom_campanha" label="Título da Campanha (Fins de auditoria)" placeholder="Ex: Aviso Importante - Camisetas" />

                {{-- Template da Mensagem --}}
                <flux:textarea
                    wire:model.live="txt_mensagem"
                    label="Mensagem (Template)"
                    rows="6"
                    placeholder="Digite a mensagem..."
                />
                
                <div class="bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 text-xs space-y-2 text-zinc-600 dark:text-zinc-400">
                    <p class="font-bold flex items-center gap-1 text-zinc-800 dark:text-zinc-200">
                        <flux:icon.information-circle class="size-4" /> Placeholders & Spintax Disponíveis:
                    </p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>{nome}</strong>: Nome do destinatário da mensagem.</li>
                        <li><strong>{apelido}</strong>: Apelido do participante.</li>
                        <li><strong>{evento}</strong>: Nome descritivo do evento.</li>
                        @if ($tip_destinatario === 'R')
                            <li><strong>{participante}</strong>: Nome do participante vinculado a este responsável.</li>
                        @endif
                        <li><strong>{Opção1|Opção2|Opção3}</strong>: Escolhe aleatoriamente uma das opções separadas por barras. Excelente para variar a saudação inicial (ex: <code>{Olá|Oi|Tudo bem?}</code>) e evitar bloqueios de spam.</li>
                    </ul>
                </div>

                {{-- Contador e Submit --}}
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <div class="text-sm">
                        <span class="text-zinc-500">Destinatários estimados:</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-200">
                            {{ count($this->destinatariosEstimados) }} contatos válidos
                        </span>
                    </div>

                    <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                        Criar e Iniciar Envios
                    </flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Preview Panel --}}
        <div class="space-y-4 lg:sticky lg:top-8">
            <flux:heading size="md" class="flex justify-between items-center">
                <span>Prévia do Envio</span>
                @if (str_contains($txt_mensagem, '|'))
                    <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click="girarPrevia">
                        Girar Spintax
                    </flux:button>
                @endif
            </flux:heading>
            
            <div class="relative bg-[#efeae2] dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden shadow-sm h-72">
                {{-- Mockup Top Bar WhatsApp --}}
                <div class="bg-[#075e54] dark:bg-zinc-800 text-white px-4 py-3 flex items-center gap-3">
                    <div class="size-8 rounded-full bg-zinc-300 dark:bg-zinc-600 flex items-center justify-center font-bold text-xs text-[#075e54]">
                        W
                    </div>
                    <div>
                        <div class="font-bold text-xs">WhatsApp Preview</div>
                        <div class="text-3xs text-emerald-100">online</div>
                    </div>
                </div>

                {{-- Chat Balloon Area --}}
                <div class="p-4 h-[calc(100%-60px)] overflow-y-auto space-y-4 bg-[url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png')] bg-repeat bg-contain dark:bg-none">
                    <div class="max-w-[85%] bg-white dark:bg-zinc-800 dark:text-zinc-100 p-3 rounded-lg shadow-sm text-xs rounded-tl-none border border-zinc-100 dark:border-zinc-700 leading-relaxed whitespace-pre-wrap">
                        {{ $this->previewText }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
