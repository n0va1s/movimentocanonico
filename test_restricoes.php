<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$e = App\Models\Evento::first();
if(!$e) die('No Evento');

$c = new App\Http\Controllers\EventoController();
$req = new Illuminate\Http\Request();
$m = new ReflectionMethod($c, 'getRestricoesFiltradas');
$m->setAccessible(true);
$res = $m->invoke($c, $req, $e);

echo "Total Restricoes: " . count($res['restricoes']) . "\n";
print_r($res['restricoes']);
