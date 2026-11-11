<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lotes saíram do sistema (pedido do André, 11/11/2026): os lotes já gravados
 * deixam de contar no total e nos convidados dos eventos. Ficam só marcados
 * como apagados (deleted_at), com uma data fixa para dar para desfazer.
 */
return new class extends Migration
{
    private const MARK = '2026-11-11 00:00:01';

    public function up(): void
    {
        DB::table('event_batches')->whereNull('deleted_at')->update(['deleted_at' => self::MARK]);
    }

    public function down(): void
    {
        DB::table('event_batches')->where('deleted_at', self::MARK)->update(['deleted_at' => null]);
    }
};
