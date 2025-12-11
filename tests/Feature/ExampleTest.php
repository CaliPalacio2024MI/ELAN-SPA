<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
      // 1. Crear un usuario de prueba usando la factory
        $user = User::factory()->create();

        // 2. Simular el inicio de sesión del usuario (actingAs)
        //    y luego acceder a la ruta raíz
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
