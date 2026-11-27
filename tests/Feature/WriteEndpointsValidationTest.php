<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\BankDetail;
use App\Models\Event;
use App\Models\ParthenonBankAccount;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriteEndpointsValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_event_payment_without_required_data_is_rejected(): void
    {
        $this->postJson('/api/event-payments', ['comment' => 'sem nada'])
            ->assertJsonValidationErrors(['event_id', 'parthenon_bank_account_id', 'payment_method_id', 'value', 'date']);

        $this->postJson('/api/event-payments', [
            'event_id' => Event::create(['name' => 'Casamento'])->id,
            'parthenon_bank_account_id' => ParthenonBankAccount::factory()->create()->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'value' => 1500.5,
            'date' => '27/11/2026',
        ])->assertJsonValidationErrors(['date' => 'A data do pagamento é inválida.']);
    }

    public function test_supplier_is_validated_and_saved_with_partial_children(): void
    {
        $this->postJson('/api/suppliers', ['fantasy_name' => 'Gelo Bom'])
            ->assertJsonValidationErrors('corporate_name');

        $this->postJson('/api/suppliers', [
            'corporate_name' => 'Gelo Bom LTDA',
            'fantasy_name' => 'Gelo Bom',
            'cnpj' => '07.349.700/0001-35',
            'state_registration' => 'isento',
            'phone_1' => '31999999999',
            'address' => ['street' => 'Rua A', 'number' => '10'],
            'bank_details' => [['bank_name' => 'Banco X']],
            'pix_keys' => [['type' => 'cnpj', 'key' => '07349700000135']],
        ])->assertCreated();

        $supplier = Supplier::sole();
        $this->assertSame('Rua A', $supplier->address->street);
        $this->assertSame(['Banco X'], $supplier->bankDetails->pluck('bank_name')->all());
    }

    public function test_supplier_update_does_not_touch_children_of_another_supplier(): void
    {
        [$mine, $other] = Supplier::factory()->count(2)->create()->all();
        $foreign = $other->bankDetails()->create(['bank_name' => 'Do outro']);

        $this->putJson("/api/suppliers/{$mine->id}", [
            'bank_details' => [['id' => $foreign->id, 'bank_name' => 'Tomado']],
            'removed_bank_detail_ids' => [$foreign->id],
        ])->assertOk();

        $this->assertSame('Do outro', BankDetail::find($foreign->id)?->bank_name);
        $this->putJson("/api/suppliers/{$mine->id}", ['corporate_name' => null])->assertJsonValidationErrors('corporate_name');
    }

    public function test_product_without_price_is_saved_with_zero(): void
    {
        $product = Product::factory()->create(['value' => 10]);

        $this->putJson("/api/products/{$product->id}", ['value' => null])->assertOk();

        $this->assertSame('0.00', (string) $product->fresh()->value);
    }
}
