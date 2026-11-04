<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CLIENT = 'App\\Models\\Client';

    private const REPRESENTATIVE = 'App\\Models\\ClientRepresentative';

    public function up(): void
    {
        Schema::create('client_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('cpf')->nullable();
            $table->timestamps();
        });

        DB::table('clients')->orderBy('id')->each(function ($client) {
            $address = DB::table('addresses')
                ->where('addressable_type', self::CLIENT)
                ->where('addressable_id', $client->id)
                ->where('type', 'representative')
                ->whereNull('deleted_at')
                ->first();

            $hasAddress = $address && collect(['street', 'number', 'district', 'zip_code', 'city_id'])
                ->contains(fn (string $field) => filled($address->{$field}));

            if (blank($client->representative_name) && blank($client->representative_cpf) && !$hasAddress) {
                return;
            }

            $id = DB::table('client_representatives')->insertGetId([
                'client_id' => $client->id,
                'name' => $client->representative_name,
                'cpf' => $client->representative_cpf,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($address) {
                DB::table('addresses')->where('id', $address->id)->update([
                    'addressable_type' => self::REPRESENTATIVE,
                    'addressable_id' => $id,
                ]);
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['representative_name', 'representative_cpf']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('representative_name')->nullable()->after('cnpj');
            $table->string('representative_cpf')->nullable()->after('representative_name');
        });

        // A coluna só guarda um representante: volta o primeiro de cada cliente e o endereço dos demais é apagado.
        $kept = [];

        DB::table('client_representatives')->orderBy('id')->each(function ($representative) use (&$kept) {
            if (isset($kept[$representative->client_id])) {
                return;
            }

            $kept[$representative->client_id] = $representative->id;

            DB::table('clients')->where('id', $representative->client_id)->update([
                'representative_name' => $representative->name,
                'representative_cpf' => $representative->cpf,
            ]);

            DB::table('addresses')
                ->where('addressable_type', self::REPRESENTATIVE)
                ->where('addressable_id', $representative->id)
                ->update(['addressable_type' => self::CLIENT, 'addressable_id' => $representative->client_id]);
        });

        DB::table('addresses')->where('addressable_type', self::REPRESENTATIVE)->delete();

        Schema::dropIfExists('client_representatives');
    }
};
