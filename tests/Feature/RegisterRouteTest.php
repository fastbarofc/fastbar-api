<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_visitor_cannot_create_an_account(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Visitante',
            'email' => 'visitante@example.com',
            'password' => 'segredo123',
            'password_confirmation' => 'segredo123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'visitante@example.com']);
    }
}
