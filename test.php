<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fichas = App\Models\Ficha::join('pessoa', 'ficha.idt_pessoa', '=', 'pessoa.idt_pessoa')
    ->where('pessoa.dat_nascimento', '1900-01-01')
    ->whereNull('pessoa.num_cpf_pessoa')
    ->select('ficha.idt_ficha', 'ficha.idt_pessoa', 'ficha.nom_candidato', 'ficha.eml_candidato', 'pessoa.idt_usuario')
    ->take(5)
    ->get();

echo json_encode($fichas, JSON_PRETTY_PRINT);
