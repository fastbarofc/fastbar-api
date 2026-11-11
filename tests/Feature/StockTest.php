<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Event;
use App\Models\EventBatch;
use App\Models\EventKit;
use App\Models\EventStatus;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Stock\EventStockForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private Product $vodka;
    private Product $lemon;
    private Product $caipvodka;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs($this->userWithRole('Admin'), 'api');

        $spirits = ProductGroup::create(['description' => 'BEBIDAS DESTILADAS']);
        $this->vodka = Product::create(['name' => 'Vodka Smirnoff 1L', 'consumption_per_guest' => 0.09, 'volume' => 1, 'product_group_id' => $spirits->id]);
        $this->lemon = Product::create(['name' => 'Limão', 'composition_item' => true]);
        $this->caipvodka = Product::create(['name' => 'Caipvodka']);
        $this->caipvodka->components()->attach([$this->lemon->id => ['quantity' => 0.03], $this->vodka->id => ['quantity' => 0.05]]);
    }

    public function test_forecast_uses_kit_products_and_drink_components_with_all_guests(): void
    {
        $event = $this->eventWithMenu('Planejamento', guests: 90);
        EventBatch::create(['event_id' => $event->id, 'guests' => 10, 'unit_value' => 0]);

        $response = $this->getJson("/api/events/{$event->id}/stock")->assertOk();

        $this->assertSame(100, $response->json('event.guests'));
        $this->assertEqualsCanonicalizing([$this->vodka->id, $this->lemon->id], $response->json('forecast.*.product_id'));

        $vodka = collect($response->json('forecast'))->firstWhere('product_id', $this->vodka->id);
        $this->assertEquals(9, $vodka['expected_quantity']);
        $this->assertEqualsCanonicalizing(['Bebidas', 'Caipvodka'], $vodka['used_in']);

        $lemon = collect($response->json('forecast'))->firstWhere('product_id', $this->lemon->id);
        $this->assertNull($lemon['expected_quantity']);
        $this->assertSame(['consumption', 'volume'], $lemon['missing']);
        $this->assertSame(1, $response->json('missing_count'));
    }

    public function test_expected_quantity_rounds_up_to_whole_packages(): void
    {
        $this->assertEquals(9, EventStockForecast::expectedQuantity(0.09, 1, 100));
        $this->assertEquals(1, EventStockForecast::expectedQuantity(0.01, 1, 14));
        $this->assertEquals(2, EventStockForecast::expectedQuantity(0.09, 1, 14));
        $this->assertEquals(0, EventStockForecast::expectedQuantity(0.09, 1, 0));
        $this->assertNull(EventStockForecast::expectedQuantity(null, 1, 100));
    }

    public function test_send_only_in_planning_and_allows_negative_stock(): void
    {
        $closed = $this->eventWithMenu('Fechado');
        $this->postJson("/api/events/{$closed->id}/stock/send", ['items' => [['product_id' => $this->vodka->id, 'sent_quantity' => 3]]])
            ->assertStatus(422);

        $event = $this->eventWithMenu('Planejamento');
        $response = $this->postJson("/api/events/{$event->id}/stock/send", ['items' => [
            ['product_id' => $this->vodka->id, 'sent_quantity' => 3],
            ['product_id' => $this->lemon->id, 'sent_quantity' => 2],
        ]])->assertOk();

        $this->assertSame('sent', $response->json('stock.state'));
        $this->assertEquals(-3, $this->balance($this->vodka));
        $this->assertEquals(-2, $this->balance($this->lemon));
    }

    public function test_editing_the_send_posts_only_the_difference_and_removed_items_are_reversed(): void
    {
        $event = $this->eventWithMenu('Planejamento');
        $this->send($event, [$this->vodka->id => 3, $this->lemon->id => 2]);
        $this->send($event, [$this->vodka->id => 5]);

        $this->assertEquals(-5, $this->balance($this->vodka));
        $this->assertEquals(0, $this->balance($this->lemon));
        $this->assertSame(2, StockMovement::where('product_id', $this->vodka->id)->count());
        $this->assertDatabaseMissing('event_stock_items', ['product_id' => $this->lemon->id]);
    }

    public function test_return_draft_does_not_move_stock_and_finalizing_posts_return_and_extra(): void
    {
        $event = $this->eventWithMenu('Planejamento');
        $this->send($event, [$this->vodka->id => 3]);

        $this->returnStock($event, [['product_id' => $this->vodka->id, 'returned_quantity' => 1]], false)->assertStatus(422);

        $event->update(['date' => now()->subDay()->toDateString()]);

        $draft = $this->returnStock($event, [['product_id' => $this->vodka->id, 'returned_quantity' => 1.5]], false)->assertOk();
        $this->assertSame('return_draft', $draft->json('stock.state'));
        $this->assertEquals(-3, $this->balance($this->vodka));

        $final = $this->returnStock($event, [
            ['product_id' => $this->vodka->id, 'returned_quantity' => 1.5],
            ['product_id' => $this->lemon->id, 'returned_quantity' => null, 'extra_quantity' => 2],
        ], true)->assertOk();

        $this->assertSame('returned', $final->json('stock.state'));
        $this->assertEquals(-1.5, $this->balance($this->vodka));
        $this->assertEquals(-2, $this->balance($this->lemon));

        $vodka = collect($final->json('stock.items'))->firstWhere('product_id', $this->vodka->id);
        $this->assertEquals(1.5, $vodka['consumed_quantity']);

        $this->returnStock($event, [['product_id' => $this->vodka->id, 'returned_quantity' => 2]], false)->assertOk();
        $this->assertEquals(-1, $this->balance($this->vodka));

        $this->postJson("/api/events/{$event->id}/stock/send", ['items' => [['product_id' => $this->vodka->id, 'sent_quantity' => 4]]])
            ->assertStatus(422);
    }

    public function test_items_without_return_count_as_fully_consumed_after_finalizing(): void
    {
        $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-01',
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->assertCreated();

        $event = $this->eventWithMenu('Planejamento');
        $this->send($event, [$this->vodka->id => 3, $this->lemon->id => 2]);
        $event->update(['date' => now()->subDay()->toDateString()]);

        $response = $this->returnStock($event, [['product_id' => $this->vodka->id, 'returned_quantity' => 1]], true)->assertOk();

        $lemon = collect($response->json('stock.items'))->firstWhere('product_id', $this->lemon->id);
        $this->assertEquals(2, $lemon['consumed_quantity']);
        $this->assertEquals(20, $lemon['consumed_value']);
        $this->assertEquals(20, $response->json('stock.consumed_value'));
        $this->assertEquals(3, $this->balance($this->lemon));
    }

    public function test_return_cannot_exceed_sent_quantity(): void
    {
        $event = $this->eventWithMenu('Planejamento');
        $this->send($event, [$this->vodka->id => 3]);
        $event->update(['date' => now()->subDay()->toDateString()]);

        $this->returnStock($event, [['product_id' => $this->vodka->id, 'returned_quantity' => 4]], true)
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.returned_quantity');
    }

    public function test_purchases_feed_balance_average_and_last_price(): void
    {
        $first = $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-01',
            'supplier' => 'Atacadão',
            'items' => [['product_id' => $this->vodka->id, 'quantity' => 10, 'unit_price' => 60]],
        ])->assertCreated();

        $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-05',
            'items' => [['product_id' => $this->vodka->id, 'quantity' => 30, 'unit_price' => 70]],
        ])->assertCreated();

        $row = $this->productRow($this->vodka);
        $this->assertEquals(40, $row['balance']);
        $this->assertEquals(67.5, $row['average_price']);
        $this->assertEquals(70, $row['last_price']);
        $this->assertEquals(2700, $row['total_value']);

        $this->putJson('/api/stock/purchases/' . $first->json('id'), [
            'purchased_at' => '2026-11-01',
            'items' => [['product_id' => $this->vodka->id, 'quantity' => 20, 'unit_price' => 60]],
        ])->assertOk();
        $this->assertEquals(50, $this->productRow($this->vodka)['balance']);

        $this->deleteJson('/api/stock/purchases/' . $first->json('id'))->assertNoContent();
        $this->assertEquals(30, $this->productRow($this->vodka)['balance']);
        $this->assertEquals(70, $this->productRow($this->vodka)['average_price']);
    }

    public function test_returns_and_adjustments_do_not_change_the_average_price(): void
    {
        $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-01',
            'items' => [['product_id' => $this->vodka->id, 'quantity' => 10, 'unit_price' => 50]],
        ])->assertCreated();

        $adjustment = $this->postJson('/api/stock/adjustments', [
            'product_id' => $this->vodka->id,
            'quantity' => -2,
            'reason' => 'loss',
        ])->assertCreated();

        $row = $this->productRow($this->vodka);
        $this->assertEquals(8, $row['balance']);
        $this->assertEquals(50, $row['average_price']);

        $this->deleteJson('/api/stock/adjustments/' . $adjustment->json('id'))->assertNoContent();
        $this->assertEquals(10, $this->productRow($this->vodka)['balance']);
    }

    public function test_drinks_are_not_stock_products(): void
    {
        $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-01',
            'items' => [['product_id' => $this->caipvodka->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.product_id');

        $ids = collect($this->getJson('/api/stock/products')->json('data'))->pluck('id');
        $this->assertNotContains($this->caipvodka->id, $ids);
        $this->assertContains($this->lemon->id, $ids);
    }

    public function test_limits_flag_products_below_minimum(): void
    {
        $this->putJson('/api/stock/limits', ['items' => [['product_id' => $this->vodka->id, 'min_quantity' => 5, 'max_quantity' => 2]]])
            ->assertStatus(422);

        $this->putJson('/api/stock/limits', ['items' => [['product_id' => $this->vodka->id, 'min_quantity' => 5, 'max_quantity' => 20]]])
            ->assertOk();

        $this->assertTrue($this->productRow($this->vodka)['below_min']);

        $this->putJson('/api/stock/limits', ['items' => [['product_id' => $this->vodka->id, 'min_quantity' => null, 'max_quantity' => null]]])
            ->assertOk();
        $this->assertDatabaseCount('stock_limits', 0);
    }

    public function test_permissions_split_reading_purchases_events_and_limits(): void
    {
        $this->actingAs($this->userWithPermissions([]), 'api');
        $this->getJson('/api/stock/products')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['estoque-visualizar']), 'api');
        $this->getJson('/api/stock/products')->assertOk()->assertJsonPath('can.purchases', false);
        $this->getJson('/api/stock/movements')->assertOk();
        $this->postJson('/api/stock/purchases', [
            'purchased_at' => '2026-11-01',
            'items' => [['product_id' => $this->vodka->id, 'quantity' => 1]],
        ])->assertForbidden();
        $this->putJson('/api/stock/limits', ['items' => [['product_id' => $this->vodka->id, 'min_quantity' => 1]]])->assertForbidden();

        $event = $this->eventWithMenu('Planejamento');
        $this->getJson("/api/events/{$event->id}/stock")->assertOk()->assertJsonPath('can.send', false);
        $this->postJson("/api/events/{$event->id}/stock/send", ['items' => []])->assertForbidden();

        $this->actingAs($this->userWithPermissions(['estoque-eventos']), 'api');
        $this->getJson("/api/events/{$event->id}/stock")->assertOk()->assertJsonPath('can.send', true);
    }

    public function test_pending_lists_products_without_consumption_in_upcoming_events(): void
    {
        $this->eventWithMenu('Fechado');
        $this->eventWithMenu('Aberto');

        $pending = $this->getJson('/api/stock/pending')->assertOk()->json('data');

        $this->assertCount(1, $pending);
        $this->assertSame($this->lemon->id, $pending[0]['product_id']);
        $this->assertCount(1, $pending[0]['events']);
    }

    public function test_events_list_shows_planning_events_and_events_with_stock(): void
    {
        $planning = $this->eventWithMenu('Planejamento');
        $this->eventWithMenu('Fechado');

        $rows = $this->getJson('/api/stock/events')->assertOk()->json('data');

        $this->assertSame([$planning->id], array_column($rows, 'id'));
        $this->assertSame('forecast', $rows[0]['state']);
        $this->assertSame(1, $rows[0]['missing_items']);
    }

    public function test_partenon_stock_tables_and_permissions_are_gone(): void
    {
        foreach (['stocks', 'stock_levels', 'requisitions', 'devolutions', 'purchase_orders', 'purchase_requests'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }

        $this->assertSame(0, Permission::where('slug', 'like', 'requisicoes-%')->count());
        $this->assertSame(
            ['estoque-compras', 'estoque-eventos', 'estoque-limites', 'estoque-visualizar'],
            Role::where('name', 'Admin')->first()->permissions()->where('slug', 'like', 'estoque-%')->orderBy('slug')->pluck('slug')->all()
        );
    }

    public function test_populate_migration_fills_consumption_and_volume_from_the_spreadsheet(): void
    {
        $smirnoff = Product::create(['name' => 'Vodka Smirnoff 1,75L']);
        $tonic = Product::create(['name' => 'Gin Tônica', 'consumption_per_guest' => 1]);
        $tonic->components()->attach($this->lemon->id, ['quantity' => 0.1]);
        $passionFruit = Product::create(['name' => 'Xarope Fórmula Maracujá 0,7L.']);
        $promix = Product::create(['name' => 'Promix Morango 0,25 KG']);
        $redBull = Product::create(['name' => 'Red Bull Tradicional 0,25L']);

        (require database_path('migrations/2026_11_11_000002_populate_products_consumption_per_guest_and_volume.php'))->up();

        $this->assertEquals([0.09, 1.75], [$smirnoff->fresh()->consumption_per_guest, $smirnoff->fresh()->volume]);
        $this->assertNull($tonic->fresh()->consumption_per_guest);
        $this->assertEquals([0.015, 0.7], [$passionFruit->fresh()->consumption_per_guest, $passionFruit->fresh()->volume]);
        $this->assertEquals([0.02, 1.0], [$promix->fresh()->consumption_per_guest, $promix->fresh()->volume]);
        $this->assertEquals([null, 0.25], [$redBull->fresh()->consumption_per_guest, $redBull->fresh()->volume]);
    }

    private function eventWithMenu(string $status, int $guests = 100): Event
    {
        $event = Event::create([
            'name' => 'Casamento ' . $status,
            'date' => now()->addDays(10)->toDateString(),
            'event_guests' => $guests,
            'event_status_id' => EventStatus::where('name', $status)->value('id'),
        ]);

        $drinks = EventKit::create(['event_id' => $event->id, 'name' => 'Clássicos']);
        $drinks->products()->attach($this->caipvodka->id, ['checklist' => true]);
        $spirits = EventKit::create(['event_id' => $event->id, 'name' => 'Bebidas']);
        $spirits->products()->attach($this->vodka->id, ['checklist' => true]);

        return $event;
    }

    private function send(Event $event, array $quantities): void
    {
        $items = collect($quantities)->map(fn ($quantity, $productId) => ['product_id' => $productId, 'sent_quantity' => $quantity])->values()->all();

        $this->postJson("/api/events/{$event->id}/stock/send", ['items' => $items])->assertOk();
    }

    private function returnStock(Event $event, array $items, bool $finalize)
    {
        return $this->postJson("/api/events/{$event->id}/stock/return", ['items' => $items, 'finalize' => $finalize]);
    }

    private function balance(Product $product): float
    {
        return (float) DB::table('stock_movements')->where('product_id', $product->id)->sum('quantity');
    }

    private function productRow(Product $product): array
    {
        return collect($this->getJson('/api/stock/products')->assertOk()->json('data'))->firstWhere('id', $product->id);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }

    private function userWithPermissions(array $slugs): User
    {
        $role = Role::create(['name' => 'Estoque ' . uniqid(), 'guard_name' => 'api']);
        $role->permissions()->sync(Permission::whereIn('slug', $slugs)->pluck('id'));

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
