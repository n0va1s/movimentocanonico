<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (!\Illuminate\Support\Facades\Schema::hasColumn('produto', 'cod_produto_loja')) {
    \Illuminate\Support\Facades\Schema::table('produto', function ($table) {
        $table->string('cod_produto_loja', 50)->nullable();
    });
    echo "Column cod_produto_loja added successfully.\n";
} else {
    echo "Column cod_produto_loja already exists.\n";
}
