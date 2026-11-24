<?php

namespace Tests\Feature;

use App\Models\Adviser;
use App\Models\Customer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customers_require_an_adviser_token(): void
    {
        $this->getJson('/api/customers')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }

    public function test_adviser_can_create_a_customer(): void
    {
        $adviser = $this->createAdviser('ana@example.test', 'ana-token');

        $this->withHeaders(['Authorization' => 'Bearer ana-token'])
            ->postJson('/api/customers', [
                'name' => 'María Pérez',
                'dni' => '01234567',
                'email' => 'maria@example.test',
                'phone' => '+51912345678',
                'extension' => '123',
            ])
            ->assertCreated()
            ->assertJsonPath('data.dni', '01234567')
            ->assertJsonPath('data.extension', '123');

        $this->assertDatabaseHas('customers', [
            'adviser_id' => $adviser->id,
            'dni' => '01234567',
            'phone' => '+51912345678',
        ]);
    }

    public function test_adviser_only_sees_own_customers(): void
    {
        $adviser = $this->createAdviser('ana@example.test', 'ana-token');
        $otherAdviser = $this->createAdviser('luis@example.test', 'luis-token');

        Customer::create([
            'adviser_id' => $adviser->id,
            'name' => 'María Pérez',
            'dni' => '01234567',
            'email' => 'maria@example.test',
            'phone' => '+51912345678',
        ]);
        Customer::create([
            'adviser_id' => $otherAdviser->id,
            'name' => 'Luis Torres',
            'dni' => '87654321',
            'email' => 'luis.torres@example.test',
            'phone' => '+51987654321',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ana-token'])
            ->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.dni', '01234567')
            ->assertJsonMissing(['dni' => '87654321']);
    }

    public function test_invalid_customer_data_is_rejected(): void
    {
        $this->createAdviser('ana@example.test', 'ana-token');
        $beforeCount = Customer::query()->count();

        $this->withHeaders(['Authorization' => 'Bearer ana-token'])
            ->postJson('/api/customers', [
                'name' => 'María Pérez',
                'dni' => '123',
                'email' => 'maria@example.test',
                'phone' => '912345678',
                'extension' => 'abc',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dni', 'phone', 'extension']);

        $this->assertSame($beforeCount, Customer::query()->count());
    }

    private function createAdviser(string $email, string $token): Adviser
    {
        $adviser = Adviser::create([
            'name' => 'Asesor de prueba',
            'email' => $email,
            'password' => 'test-password',
        ]);
        $adviser->forceFill(['api_token' => hash('sha256', $token)])->save();

        return $adviser;
    }
}
