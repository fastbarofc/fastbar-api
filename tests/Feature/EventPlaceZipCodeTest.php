<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\Address;
use App\Models\City;
use App\Models\EventPlace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPlaceZipCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_place_created_with_unmasked_zip_code_is_saved_masked(): void
    {
        $id = $this->postJson('/api/event-places', $this->payload('36039080'))->assertCreated()->json('id');

        $this->assertSame('36039-080', $this->zipCodeOf($id));
    }

    public function test_place_created_with_unmasked_zip_code_can_be_updated(): void
    {
        $id = $this->postJson('/api/event-places', $this->payload('36039080'))->assertCreated()->json('id');

        $this->putJson("/api/event-places/{$id}", $this->payload('36039080'))->assertOk();
    }

    public function test_place_saved_without_mask_before_the_fix_can_be_updated(): void
    {
        $place = EventPlace::create(['name' => 'Espaço Lua', 'responsible' => 'Ana', 'phone_1' => '32999999999']);
        $city = City::first();
        Address::create([
            'addressable_id' => $place->id,
            'addressable_type' => EventPlace::class,
            'street' => 'Rodovia BR-040',
            'number' => '790',
            'district' => 'São Pedro',
            'zip_code' => '36039080',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
        ]);

        $this->putJson("/api/event-places/{$place->id}", $this->payload('36039080'))->assertOk();

        $this->assertSame('36039-080', $this->zipCodeOf($place->id));
    }

    public function test_incomplete_zip_code_is_still_rejected_on_update(): void
    {
        $id = $this->postJson('/api/event-places', $this->payload('36039080'))->assertCreated()->json('id');

        $this->putJson("/api/event-places/{$id}", $this->payload('3603908'))->assertJsonValidationErrors('zip_code');
    }

    private function payload(string $zipCode): array
    {
        $city = City::first();

        return [
            'name' => 'Espaço Lua',
            'responsible' => 'Ana',
            'phone_1' => '32999999999',
            'address' => [
                'street' => 'Rodovia BR-040',
                'district' => 'São Pedro',
                'complement' => null,
                'city_id' => $city->id,
                'state_id' => $city->state_id,
                'number' => '790',
                'zip_code' => $zipCode,
                'reference' => null,
            ],
        ];
    }

    private function zipCodeOf(int $placeId): ?string
    {
        return Address::where('addressable_type', EventPlace::class)
            ->where('addressable_id', $placeId)
            ->value('zip_code');
    }
}
