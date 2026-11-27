<?php

namespace Tests\Feature;

use App\Http\Middleware\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextLengthValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Admin::class);
        $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_texts_longer_than_the_column_are_rejected_instead_of_failing_in_the_database(): void
    {
        $tooLong = str_repeat('a', 192);

        $this->postJson('/api/units', ['description' => $tooLong])->assertJsonValidationErrors('description');
        $this->postJson('/api/lost-reasons', ['name' => $tooLong])->assertJsonValidationErrors('name');
        $this->postJson('/api/cost-centers', ['code' => $tooLong, 'description' => $tooLong, 'account' => 'analytic'])
            ->assertJsonValidationErrors(['code', 'description']);
    }

    public function test_text_with_the_column_size_is_saved(): void
    {
        $this->postJson('/api/units', ['description' => str_repeat('b', 191)])->assertCreated();
        $this->postJson('/api/units', ['description' => str_repeat('b', 190) . 'c'])->assertCreated();
    }
}
