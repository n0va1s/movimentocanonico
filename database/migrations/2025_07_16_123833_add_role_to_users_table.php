<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user'); // valores possíveis: user, admin, coord
            }
            if (!Schema::hasColumn('users', 'idt_movimento')) {
                $table->foreignId('idt_movimento')->nullable()->constrained('tipo_movimento', 'idt_movimento')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('idt_movimento');
            $table->dropColumn('role');
        });
    }
};
