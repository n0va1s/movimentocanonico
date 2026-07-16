<?php

use Livewire\Volt\Volt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\TipoParoquia;
use App\Models\TipoMovimento;

test('admin can upload logo to a movement', function () {
    Storage::fake('public');
    
    $admin = User::factory()->create(['role' => 'admin']);
    $paroquia = TipoParoquia::create(['nom_paroquia' => 'Paroquia Teste']);
    
    $file = UploadedFile::fake()->create('logo.png', 500);
    
    Volt::actingAs($admin)
        ->test('organizacao.index')
        ->set('paroquiaSelecionada', $paroquia->idt_paroquia)
        ->call('abrirModalMovimento')
        ->set('nom_movimento', 'Movimento Teste')
        ->set('des_sigla', 'MT')
        ->set('med_logo', $file)
        ->call('salvarMovimento');

    $movimento = TipoMovimento::where('nom_movimento', 'Movimento Teste')->first();
    expect($movimento)->not->toBeNull();
    expect($movimento->med_logo)->not->toBeNull();
    
    Storage::disk('public')->assertExists($movimento->med_logo);
});
