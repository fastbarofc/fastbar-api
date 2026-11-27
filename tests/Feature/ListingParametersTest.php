<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingParametersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_invalid_listing_parameters_are_rejected(): void
    {
        $this->getJson('/api/units?per_page=abc')->assertJsonValidationErrors('per_page');
        $this->getJson('/api/units?per_page=-5')->assertJsonValidationErrors('per_page');
        $this->getJson('/api/units?direction=sideways')->assertJsonValidationErrors('direction');
        $this->getJson('/api/units?filter_by=' . urlencode('{not json'))->assertJsonValidationErrors('filter_by');
        $this->getJson('/api/units?filter_by=' . urlencode('[1]'))->assertJsonValidationErrors('filter_by');
        $this->getJson('/api/units?order_by=' . urlencode('name; drop table units'))->assertJsonValidationErrors('order_by');
    }

    public function test_usual_front_parameters_keep_working(): void
    {
        $filters = urlencode(json_encode([['search' => 'kg']]));

        $this->getJson("/api/units?per_page=0&order_by=name&direction=desc&filter_by={$filters}")->assertOk();
        $this->getJson('/api/units?per_page=30&page=2&filter_by=%5B%5D')->assertOk();
    }

    public function test_relation_name_in_the_event_list_does_not_call_model_methods(): void
    {
        Event::create(['name' => 'Casamento', 'is_draft' => false]);

        $this->getJson('/api/events?order_by=save.name')->assertOk();
        $this->getJson('/api/events?filter_by=' . urlencode(json_encode([['save.name' => 'x']])))->assertOk();
        $this->getJson('/api/clients?order_by=save.name')->assertOk();

        $this->assertSame(1, Event::count());
    }

    public function test_unknown_column_is_ignored_instead_of_failing(): void
    {
        $this->getJson('/api/companies?order_by=nao_existe')->assertOk();
        $this->getJson('/api/companies?filter_by=' . urlencode(json_encode([['nao_existe' => 1]])))->assertOk();
        $this->getJson('/api/collaborators?order_by=nao_existe')->assertOk();
        $this->getJson('/api/events?order_by=email')->assertOk();
    }
}
