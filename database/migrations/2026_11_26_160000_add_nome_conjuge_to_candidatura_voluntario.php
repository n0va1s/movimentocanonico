<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidatura_voluntario', function (Blueprint $table) {
            $table->string('nome_conjuge')->nullable();
            $table->string('apelido_conjuge')->nullable();
            $table->string('telefone_conjuge', 32)->nullable();
            $table->string('email_conjuge')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidatura_voluntario', function (Blueprint $table) {
            $table->dropColumn(['nome_conjuge', 'apelido_conjuge', 'telefone_conjuge', 'email_conjuge']);
        });
    }
};
