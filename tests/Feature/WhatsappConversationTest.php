<?php

namespace Tests\Feature;

use App\Models\Adviser;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNumber;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsappConversationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_webhook_verification_and_signature_are_required(): void
    {
        config()->set('services.whatsapp.verify_token', 'verify-test');
        config()->set('services.whatsapp.app_secret', 'secret-test');

        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123')
            ->assertForbidden();

        $this->get('/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=verify-test&hub.challenge=123')
            ->assertOk()->assertSeeText('123');

        $this->postJson('/api/webhooks/whatsapp', ['object' => 'whatsapp_business_account'])
            ->assertForbidden();

        $payload = '{"object":"whatsapp_business_account","entry":[]}';
        $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $payload, 'secret-test'),
        ], $payload)->assertOk();
    }

    public function test_incoming_message_is_visible_only_to_the_adviser_who_connects_its_number(): void
    {
        config()->set('services.whatsapp.app_secret', 'secret-test');
        config()->set('services.whatsapp.access_token', 'access-test');
        config()->set('services.whatsapp.verify_token', 'verify-test');
        config()->set('services.whatsapp.phone_number_ids', 'business-123');
        config()->set('services.whatsapp.api_version', 'v27.0');

        $adviser = $this->createAdviser('ana@example.test', 'ana-token');
        $this->createAdviser('luis@example.test', 'luis-token');

        $this->receiveIncoming('wamid.incoming')->assertOk();
        $this->receiveIncoming('wamid.incoming')->assertOk();

        $conversation = WhatsappConversation::query()->firstOrFail();
        $this->assertSame(1, WhatsappMessage::query()->count());
        $this->assertSame('+51912345678', $conversation->customer_phone);

        $this->getJson('/api/whatsapp/conversations')->assertUnauthorized();
        $this->withToken('ana-token')->getJson('/api/whatsapp/conversations')
            ->assertOk()->assertJsonCount(0, 'data');

        $this->withToken('ana-token')->postJson('/api/whatsapp/numbers', [
            'phone_number_id' => 'business-123',
        ])->assertOk();
        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $conversation->id,
            'assigned_adviser_id' => $adviser->id,
        ]);
        $this->withToken('ana-token')->getJson('/api/whatsapp/conversations')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->withToken('luis-token')->getJson("/api/whatsapp/conversations/{$conversation->id}")
            ->assertNotFound();
        $this->withToken('luis-token')->postJson('/api/whatsapp/numbers', [
            'phone_number_id' => 'business-123',
        ])->assertStatus(409);

        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v27.0/business-123/messages' => Http::response([
                'messages' => [['id' => 'wamid.outgoing']],
            ], 200),
        ]);

        $this->withToken('ana-token')->postJson("/api/whatsapp/conversations/{$conversation->id}/messages", [
            'body' => 'Hola, te ayudamos desde la empresa.',
        ])->assertCreated()->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'wamid.outgoing',
            'sent_by_adviser_id' => $adviser->id,
        ]);
        Http::assertSentCount(1);

        $this->withToken('ana-token')->deleteJson('/api/whatsapp/numbers/business-123')->assertOk();
        $this->withToken('luis-token')->postJson('/api/whatsapp/numbers', [
            'phone_number_id' => 'business-123',
        ])->assertOk();
        $this->withToken('ana-token')->postJson("/api/whatsapp/conversations/{$conversation->id}/messages", [
            'body' => 'No autorizado',
        ])->assertNotFound();
    }

    public function test_expired_conversation_cannot_send_free_text(): void
    {
        config()->set('services.whatsapp.phone_number_ids', 'business-123');
        $adviser = $this->createAdviser('ana@example.test', 'ana-token');
        WhatsappNumber::create(['phone_number_id' => 'business-123', 'adviser_id' => $adviser->id]);
        $conversation = WhatsappConversation::create([
            'phone_number_id' => 'business-123',
            'customer_phone' => '+51912345678',
            'assigned_adviser_id' => $adviser->id,
            'last_inbound_at' => now()->subHours(25),
        ]);

        Http::preventStrayRequests();

        $this->withToken('ana-token')->postJson("/api/whatsapp/conversations/{$conversation->id}/messages", [
            'body' => 'Hola',
        ])->assertUnprocessable();

        Http::assertNothingSent();
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

    private function receiveIncoming(string $messageId): TestResponse
    {
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => 'business-123', 'display_phone_number' => '+51999999999'],
                        'contacts' => [['profile' => ['name' => 'María Pérez']]],
                        'messages' => [[
                            'id' => $messageId,
                            'from' => '51912345678',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Necesito información'],
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        return $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $payload, 'secret-test'),
        ], $payload);
    }
}
