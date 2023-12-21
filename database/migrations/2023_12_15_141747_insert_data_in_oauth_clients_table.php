<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('oauth_clients')->insert([     
            'id' => '9ad9c4dc-2990-4664-a145-5e6c6438b77d',     
            'user_id' => null,     
            'name' => 'Laravel Personal Access Client',     
            'secret' => 'nmFI76l9dui9FJtfUuJxo7sBQZqDl2hETyEFvyNS',     
            'provider' => null,     
            'redirect' => 'http://localhost',     
            'personal_access_client' => 1,     
            'password_client' => 0,     
            'revoked' => 0,     
            'created_at' => now(),     
            'updated_at' => now(), 
        ]);

        DB::table('oauth_personal_access_clients')->insert([     
            'id' => 1,     
            'client_id' => '9ad9c4dc-2990-4664-a145-5e6c6438b77d',     
            'created_at' => now(),     
            'updated_at' => now(), 
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('oauth_clients')->where('id', '9ad9c4dc-2990-4664-a145-5e6c6438b77d')->delete();  
        DB::table('oauth_personal_access_clients')->where('client_id', '9ad9c4dc-2990-4664-a145-5e6c6438b77d')->delete();  
    }
};
