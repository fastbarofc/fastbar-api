<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Kit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitsAndMiscValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_moving_a_product_between_kits_works_and_validates_the_ids(): void
    {
        [$from, $to] = [$this->kit('Origem'), $this->kit('Destino')];
        $product = Product::factory()->create();
        $from->products()->attach($product->id, ['ordination' => 1, 'optional' => false, 'checklist' => false]);

        $this->postJson('/api/products/move-to-kit', ['new_kit' => ['id' => 999999], 'old_kit' => ['id' => $from->id], 'product' => ['id' => $product->id]])
            ->assertJsonValidationErrors('new_kit.id');

        $this->postJson('/api/products/move-to-kit', ['new_kit' => ['id' => $to->id], 'old_kit' => ['id' => $from->id], 'product' => ['id' => $product->id]])
            ->assertCreated();

        $this->assertSame([$product->id], $to->products()->pluck('products.id')->all());
        $this->assertSame([], $from->products()->pluck('products.id')->all());
    }

    public function test_event_folder_requires_an_existing_event(): void
    {
        $this->postJson('/api/event-kits', ['name' => 'Pasta'])->assertJsonValidationErrors('event_id');
    }

    public function test_malformed_zip_code_is_rejected_before_calling_the_lookup_service(): void
    {
        $this->getJson('/api/addresses/cep/abc')->assertStatus(422);
    }

    public function test_routes_without_an_action_are_gone(): void
    {
        $this->putJson('/api/events/1/installments', [])->assertNotFound();
        $this->getJson('/api/sectors/1/offices')->assertNotFound();
    }

    private function kit(string $name): Kit
    {
        return Kit::create(['name' => $name, 'ordination' => 1, 'themed_table' => false, 'optional' => false, 'checklist' => false]);
    }
}
