<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('event_tasting', 'event_event_tasting');
        
        Schema::table('event_event_tasting', function (Blueprint $table) {
            $table->renameColumn('tasting_event_id', 'related_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_event_tasting', function (Blueprint $table) {
            $table->renameColumn('related_event_id', 'tasting_event_id');
        });
        
        Schema::rename('event_event_tasting', 'event_tasting');
    }
};
