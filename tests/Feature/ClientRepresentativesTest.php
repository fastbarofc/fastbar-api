<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientRepresentative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRepresentativesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_company_is_registered_with_several_representatives_each_with_its_own_address(): void
    {
        $response = $this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '529.982.247-25', 'Rua A'),
            $this->representative('Maria Souza', '15350946056', 'Rua B'),
        ]))->assertCreated();

        $client = Client::findOrFail($response->json('id'));
        $representatives = $client->representatives;

        $this->assertSame(['João Silva', 'Maria Souza'], $representatives->pluck('name')->all());
        $this->assertSame(['52998224725', '15350946056'], $representatives->pluck('cpf')->all());
        $this->assertSame(['Rua A', 'Rua B'], $representatives->map(fn ($representative) => $representative->address->street)->all());
        $this->assertSame('01001-000', $representatives[0]->address->zip_code);
        $this->assertSame(['João Silva', 'Maria Souza'], array_column($response->json('representatives'), 'name'));
        $this->assertSame('Rua B', $response->json('representatives.1.address.street'));
    }

    public function test_update_edits_adds_and_removes_representatives(): void
    {
        $client = Client::findOrFail($this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '52998224725', 'Rua A'),
            $this->representative('Maria Souza', '15350946056', 'Rua B'),
        ]))->json('id'));
        [$joao, $maria] = $client->representatives->all();

        $this->putJson("/api/clients/{$client->id}", $this->company([
            ['id' => $joao->id] + $this->representative('João da Silva', '52998224725', 'Rua C'),
            $this->representative('Ana Lima', '11144477735', 'Rua D'),
        ]))->assertOk();

        $representatives = $client->fresh()->representatives;

        $this->assertSame(['João da Silva', 'Ana Lima'], $representatives->pluck('name')->all());
        $this->assertSame($joao->id, $representatives[0]->id);
        $this->assertSame('Rua C', $representatives[0]->address->street);
        $this->assertNull(ClientRepresentative::find($maria->id));
        $this->assertSame(0, Address::where('addressable_type', ClientRepresentative::class)->where('addressable_id', $maria->id)->count());
    }

    public function test_request_without_representatives_keeps_the_ones_registered(): void
    {
        $client = Client::findOrFail($this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '52998224725', 'Rua A'),
        ]))->json('id'));

        $this->putJson("/api/clients/{$client->id}", ['nickname' => 'Empresa'])->assertOk();

        $this->assertSame(['João Silva'], $client->fresh()->representatives->pluck('name')->all());
    }

    public function test_company_needs_at_least_one_complete_representative(): void
    {
        $this->postJson('/api/clients', $this->company([]))
            ->assertJsonValidationErrors(['representatives' => 'Cadastre pelo menos um representante para pessoa jurídica.']);

        $incomplete = $this->representative('Maria Souza', '15350946056', 'Rua B');
        $incomplete['address']['street'] = '';

        $this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '52998224725', 'Rua A'),
            $incomplete,
        ]))->assertJsonValidationErrors(['representatives.1.address.street' => 'A rua do 2º representante é obrigatória.']);

        $this->assertSame(0, Client::count());
    }

    public function test_quick_registration_of_a_company_does_not_require_representatives(): void
    {
        $this->postJson('/api/clients', ['name' => 'Empresa Ltda', 'type' => 'pj', 'quick_registration' => true])
            ->assertCreated();
    }

    public function test_representative_of_another_client_cannot_be_edited(): void
    {
        $other = Client::findOrFail($this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '52998224725', 'Rua A'),
        ]))->json('id'));
        $client = Client::findOrFail($this->postJson('/api/clients', $this->company([
            $this->representative('Maria Souza', '15350946056', 'Rua B'),
        ]))->json('id'));

        $this->putJson("/api/clients/{$client->id}", $this->company([
            ['id' => $other->representatives->first()->id] + $this->representative('Ana Lima', '11144477735', 'Rua D'),
        ]))->assertJsonValidationErrors('representatives.0.id');

        $this->assertSame('João Silva', $other->representatives()->first()->name);
    }

    public function test_company_is_complete_only_when_every_representative_is(): void
    {
        $client = Client::findOrFail($this->postJson('/api/clients', $this->company([
            $this->representative('João Silva', '52998224725', 'Rua A'),
        ]))->json('id'));

        $this->assertNotContains('dados do representante legal', $client->missingRequiredFields());

        $client->representatives()->create(['name' => 'Maria Souza']);

        $this->assertContains('dados do representante legal', $client->fresh()->missingRequiredFields());
    }

    private function company(array $representatives): array
    {
        return [
            'name' => 'Empresa Ltda',
            'type' => 'pj',
            'cnpj' => '11222333000181',
            'phone_1' => '11999999999',
            'address' => $this->address('Rua Sede'),
            'representatives' => $representatives,
        ];
    }

    private function representative(string $name, string $cpf, string $street): array
    {
        return ['name' => $name, 'cpf' => $cpf, 'address' => $this->address($street)];
    }

    private function address(string $street): array
    {
        $city = City::first();

        return [
            'street' => $street,
            'number' => '10',
            'district' => 'Centro',
            'complement' => '',
            'zip_code' => '01001000',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
            'reference' => '',
        ];
    }
}
