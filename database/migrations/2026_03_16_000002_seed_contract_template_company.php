<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parthenon = DB::table('companies')->where('internal_name', 'Parthenon')->whereNull('deleted_at')->first();

        if ($parthenon) {
            DB::table('contract_templates')
                ->whereNull('company_id')
                ->update(['company_id' => $parthenon->id]);
        }
    }

    public function down(): void
    {
        DB::table('contract_templates')->update(['company_id' => null]);
    }
};
