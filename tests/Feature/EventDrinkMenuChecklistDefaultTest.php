<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventKit;
use App\Models\Kit;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDrinkMenuChecklistDefaultTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->event = Event::create(['name' => 'Casamento']);
    }

    public function test_product_added_to_event_subfolder_comes_with_checklist(): void
    {
        $folder = EventKit::create(['name' => 'Drinks', 'event_id' => $this->event->id]);
        $subfolder = EventKit::create(['name' => 'Clássicos', 'event_id' => $this->event->id, 'event_kit_id' => $folder->id]);
        $product = Product::create(['name' => 'Negroni']);

        $this->postJson('/api/products/attach-to-event-kit', [
            'event_kit_id' => $subfolder->id,
            'product_id' => $product->id,
        ])->assertOk();

        $this->assertTrue($this->checklistOf($subfolder, $product));
    }

    public function test_catalog_folder_added_to_event_brings_products_with_checklist(): void
    {
        $catalogFolder = Kit::forceCreate(['id' => 9000, 'name' => 'Drinks']);
        $catalogSubfolder = Kit::forceCreate(['id' => 9001, 'name' => 'Clássicos', 'kit_id' => $catalogFolder->id]);
        $gin = Product::forceCreate(['id' => 9001, 'name' => 'Gin Tônica']);
        $negroni = Product::create(['name' => 'Negroni']);
        $catalogFolder->products()->attach($gin->id, ['checklist' => false]);
        $catalogSubfolder->products()->attach($negroni->id, ['checklist' => false]);

        $this->postJson('/api/event-kits', [
            ...$catalogFolder->toArray(),
            'event_id' => $this->event->id,
            'event_kit_id' => null,
        ])->assertCreated();

        $folder = EventKit::where('event_id', $this->event->id)->where('name', 'Drinks')->firstOrFail();
        $subfolder = EventKit::where('event_kit_id', $folder->id)->where('name', 'Clássicos')->firstOrFail();

        $this->assertTrue($this->checklistOf($folder, $gin));
        $this->assertTrue($this->checklistOf($subfolder, $negroni));
    }

    public function test_catalog_product_added_to_event_comes_with_checklist(): void
    {
        $catalogFolder = Kit::create(['name' => 'Drinks']);
        $product = Product::create(['name' => 'Negroni']);
        $catalogFolder->products()->attach($product->id, ['checklist' => false]);

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

    public function test_user_can_uncheck_checklist_of_added_product(): void
    {
        $folder = EventKit::create(['name' => 'Drinks', 'event_id' => $this->event->id]);
        $product = Product::create(['name' => 'Negroni']);

        $this->postJson('/api/products/attach-to-event-kit', [
            'event_kit_id' => $folder->id,
            'product_id' => $product->id,
        ])->assertOk();

        $this->putJson('/api/event-kits/update-product', [
            'id' => $product->id,
            'event_kit_id' => $folder->id,
            'checklist' => false,
        ])->assertOk();

        $this->assertFalse($this->checklistOf($folder, $product));
    }

    private function checklistOf(EventKit $folder, Product $product): bool
    {
        return (bool) $folder->products()->where('products.id', $product->id)->firstOrFail()->pivot->checklist;
    }
}
