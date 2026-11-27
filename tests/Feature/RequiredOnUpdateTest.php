<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequiredOnUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearing_a_required_field_is_rejected_and_omitting_it_is_allowed(): void
    {
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
        $unit = Unit::create(['description' => 'Litro', 'slug' => 'litro']);

        $this->putJson("/api/units/{$unit->id}", ['description' => ''])
            ->assertJsonValidationErrors(['description' => 'O campo descrição é obrigatório.']);
        $this->putJson("/api/units/{$unit->id}", [])->assertOk();

        $this->assertSame('Litro', $unit->fresh()->description);
    }
}
