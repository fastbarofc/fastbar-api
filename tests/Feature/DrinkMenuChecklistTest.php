<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventKit;
use App\Models\Kit;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrinkMenuChecklistTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->event = Event::create(['name' => 'Casamento']);
    }

    public function test_product_added_to_catalog_folder_inherits_display_checklist(): void
    {
        $folder = Kit::create(['name' => 'Clássicos']);
        $negroni = Product::create(['name' => 'Negroni', 'display_checklist' => true]);
        $agua = Product::create(['name' => 'Água', 'display_checklist' => false]);

        $this->postJson('/api/products/attach-to-kit', ['kit_id' => $folder->id, 'product_id' => $negroni->id])->assertOk();
        $this->postJson('/api/products/attach-to-kit', ['kit_id' => $folder->id, 'product_id' => $agua->id])->assertOk();

        $this->assertTrue($this->checklistOf($folder, $negroni));
        $this->assertFalse($this->checklistOf($folder, $agua));
    }

    public function test_catalog_folder_added_to_event_keeps_catalog_checklist(): void
    {
        $catalogFolder = Kit::forceCreate(['id' => 9000, 'name' => 'Drinks']);
        $catalogSubfolder = Kit::forceCreate(['id' => 9001, 'name' => 'Clássicos', 'kit_id' => $catalogFolder->id]);
        $gin = Product::forceCreate(['id' => 9001, 'name' => 'Gin Tônica']);
        $negroni = Product::create(['name' => 'Negroni']);
        $catalogFolder->products()->attach($gin->id, ['checklist' => true]);
        $catalogSubfolder->products()->attach($negroni->id, ['checklist' => false]);

        $this->postJson('/api/event-kits', [
            ...$catalogFolder->toArray(),
            'event_id' => $this->event->id,
            'event_kit_id' => null,
        ])->assertCreated();

        $folder = EventKit::where('event_id', $this->event->id)->where('name', 'Drinks')->firstOrFail();
        $subfolder = EventKit::where('event_kit_id', $folder->id)->where('name', 'Clássicos')->firstOrFail();

        $this->assertTrue($this->checklistOf($folder, $gin));
        $this->assertFalse($this->checklistOf($subfolder, $negroni));
    }

    public function test_catalog_product_added_to_event_keeps_catalog_checklist(): void
    {
        $catalogFolder = Kit::create(['name' => 'Drinks']);
        $product = Product::create(['name' => 'Negroni']);
        $catalogFolder->products()->attach($product->id, ['checklist' => true]);

        $this->postJson('/api/event-kits', [
            'id' => $product->id,
            'kit_id' => $catalogFolder->id,
            'name' => $product->name,
            'type' => 'product',
            'event_id' => $this->event->id,
            'event_kit_id' => null,
        ])->assertCreated();

        $folder = EventKit::where('event_id', $this->event->id)->where('name', 'Drinks')->firstOrFail();

        $this->assertTrue($this->checklistOf($folder, $product));
    }

    private function checklistOf(Kit|EventKit $folder, Product $product): bool
    {
        return (bool) $folder->products()->where('products.id', $product->id)->firstOrFail()->pivot->checklist;
    }
}
