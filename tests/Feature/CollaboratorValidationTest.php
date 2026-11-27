<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Collaborator;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorValidationTest extends TestCase
{
    use RefreshDatabase;

    private Collaborator $collaborator;
    private array $companies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
        $this->collaborator = Collaborator::factory()->create(['user_id' => null]);
        $this->companies = [Company::create(['internal_name' => 'Fastbar', 'legal_name' => 'Fastbar LTDA', 'trade_name' => 'Fastbar'])->id];
    }

    public function test_enabling_login_without_a_user_group_is_a_validation_error(): void
    {
        $this->putJson("/api/collaborators/{$this->collaborator->id}", ['has_login' => true, 'company_ids' => $this->companies])
            ->assertJsonValidationErrors(['role_id' => 'Escolha o grupo de usuário para liberar o acesso ao sistema.']);
    }

    public function test_address_and_goals_are_validated_and_partial_address_is_saved(): void
    {
        $url = "/api/collaborators/{$this->collaborator->id}";

        $this->putJson($url, ['company_ids' => $this->companies, 'address' => ['street' => 'Rua A', 'number' => str_repeat('1', 51)]])
            ->assertJsonValidationErrors('address.number');
        $this->putJson($url, ['company_ids' => $this->companies, 'goals' => [['type' => 'weekly']]])
            ->assertJsonValidationErrors(['goals.0.type' => 'O tipo da meta deve ser mensal ou anual.']);

        $this->putJson($url, ['company_ids' => $this->companies, 'address' => ['street' => 'Rua A']])->assertOk();
        $this->assertSame('Rua A', $this->collaborator->fresh()->address->street);
    }
}
