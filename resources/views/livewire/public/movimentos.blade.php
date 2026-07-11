<?php

use App\Models\TipoParoquia;
use App\Models\TipoMovimento;
use Livewire\Volt\Component;

new class extends Component {
    public $paroquias;
    public $paroquiaId;

    public function mount()
    {
        $this->paroquias = TipoParoquia::orderBy('nom_paroquia')->get();
        if ($this->paroquias->isNotEmpty()) {
            $this->paroquiaId = $this->paroquias->first()->idt_paroquia;
        }
    }

    public function with(): array
    {
        $movimentos = collect();
        
        if ($this->paroquiaId) {
            $movimentos = TipoMovimento::where('idt_paroquia', $this->paroquiaId)
                ->orderBy('idt_movimento')
                ->get();
        }

        return [
            'movimentos' => $movimentos,
        ];
    }
}; ?>

<section class="text-center space-y-8 mt-16 w-full">
    <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">
            Nossos Movimentos
        </h2>

        {{-- Seleção de Paróquia --}}
        @if($paroquias->isNotEmpty())
            <div class="w-full md:w-auto">
                <label for="paroquiaId" class="sr-only">Selecione a Paróquia</label>
                <select wire:model.live="paroquiaId" id="paroquiaId" class="w-full md:w-64 rounded-md border border-gray-300 dark:border-zinc-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-4 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition">
                    @foreach($paroquias as $paroquia)
                        <option value="{{ $paroquia->idt_paroquia }}">{{ $paroquia->nom_paroquia }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Grid de Movimentos --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-7xl mx-auto my-16 px-4" wire:loading.class="opacity-50">
        @forelse($movimentos as $movimento)
            @php
                // Processamento da imagem
                $imagem = null;
                if ($movimento->med_logo) {
                    if (str_starts_with($movimento->med_logo, 'http')) {
                        $imagem = $movimento->med_logo;
                    } else {
                        $imagem = \Illuminate\Support\Facades\Storage::url($movimento->med_logo);
                    }
                }

                // Cor do título baseado na sigla conhecida (opcional, fallback genérico)
                $corTitulo = match (strtoupper($movimento->des_sigla)) {
                    'VEM' => 'text-blue-600 dark:text-blue-400',
                    'SEGUE-ME' => 'text-orange-600 dark:text-orange-400',
                    'ECC' => 'text-green-600 dark:text-green-400',
                    default => 'text-indigo-600 dark:text-indigo-400',
                };
                

                // Link para a ficha pública, se existir rota para a sigla
                // O fallback leva pra home caso não encontre
                $siglaRoute = match (strtoupper($movimento->des_sigla)) {
                    'SEGUE-ME' => 'sgm',
                    default => strtolower($movimento->des_sigla),
                };
                
                $rotaInscricao = \Illuminate\Support\Facades\Route::has("home.ficha.{$siglaRoute}") 
                    ? route("home.ficha.{$siglaRoute}") 
                    : null;
            @endphp

            <div class="border border-gray-300 dark:border-gray-700 rounded-xl p-6 flex flex-col justify-between shadow-sm dark:bg-gray-800 transition hover:shadow-md">
                <div>
                    @if($imagem)
                        <img src="{{ $imagem }}" class="w-full h-64 md:h-30 object-contain flex-shrink-0 rounded-2xl mb-4 bg-gray-50 dark:bg-gray-900" alt="Logo {{ $movimento->nom_movimento }}">
                    @else
                        <div class="w-full h-64 md:h-30 flex-shrink-0 rounded-2xl mb-4 bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                            <x-heroicon-o-photo class="w-16 h-16 opacity-50" />
                        </div>
                    @endif

                    <h3 class="text-xl font-bold {{ $corTitulo }}">{{ $movimento->des_sigla }}</h3>
                    <p class="text-gray-600 dark:text-gray-300 mt-2">{{ $movimento->nom_movimento }}</p>
                </div>

                <div class="mt-4">
                    @if ($movimento->ind_inscricao_aberta && $rotaInscricao)
                        <a href="{{ $rotaInscricao }}"
                            class="inline-block bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600 border-none shadow-md text-white font-semibold px-4 py-2 rounded-md transition text-center w-full">
                            Preencher Ficha
                        </a>
                    @else
                        <!-- NOVO Badge Premium de Esgotado / Fechado -->
                        <div class="relative w-full">
                            <div class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 dark:from-red-700 dark:to-red-800 text-white font-bold rounded-md shadow-lg border-2 border-red-500 dark:border-red-600 animate-pulse-subtle">
                                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex flex-col leading-tight text-left">
                                    <span class="text-sm uppercase tracking-wider">✓ Encerrado</span>
                                    <span class="text-xs font-normal opacity-90">Aguarde o próximo</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-1 md:col-span-3 text-center py-12 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                Nenhum movimento cadastrado para esta paróquia.
            </div>
        @endforelse
    </div>
</section>
