<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappWebhookController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $secret = config('services.whatsapp.webhook_token');

        if (! $secret) {
            return response()->json(['message' => 'Webhook no configurado.'], 503);
        }

        if (! hash_equals($secret, (string) $request->query('token'))) {
            return response()->json(['message' => 'Token de webhook inválido.'], 403);
        }

        $payload = $request->json()->all();

        $allowedNumbers = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));
        $phoneNumberId = (string) ($allowedNumbers[0] ?? '');

        if ($phoneNumberId === '') {
            return response()->json(['message' => 'Canal de WhatsApp no configurado.'], 503);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    $this->storeIncoming($phoneNumberId, $value, $message);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->updateStatus($status);
                }
            }
        }

        return response()->json(['message' => 'Evento recibido.']);
    }

    private function storeIncoming(string $phoneNumberId, array $value, array $message): void
    {
        $messageId = (string) ($message['id'] ?? '');
        $customerPhone = (string) ($message['from'] ?? $message['from_user_id'] ?? '');

        if ($messageId === '' || $customerPhone === '' || strlen($customerPhone) > 255) {
            return;
        }

        if (WhatsappMessage::where('provider_message_id', $messageId)->exists()) {
            return;
        }

        $number = WhatsappNumber::firstOrCreate(
            ['phone_number_id' => $phoneNumberId],
            ['display_phone' => config('services.whatsapp.display_phone')],
        );

        if ($number->display_phone !== config('services.whatsapp.display_phone')) {
            $number->update(['display_phone' => config('services.whatsapp.display_phone')]);
        }

        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number_id' => $phoneNumberId, 'customer_phone' => ctype_digit($customerPhone) ? '+'.$customerPhone : $customerPhone],
            [
                'business_phone' => config('services.whatsapp.display_phone'),
                'assigned_adviser_id' => $number->adviser_id,
            ],
        );

        $customerName = $value['contacts'][0]['profile']['name'] ?? null;
        $inboundAt = isset($message['timestamp'])
            ? CarbonImmutable::createFromTimestamp((int) $message['timestamp'])
            : CarbonImmutable::now();

        WhatsappMessage::firstOrCreate(
            ['provider_message_id' => $messageId],
            [
                'conversation_id' => $conversation->id,
                'direction' => 'incoming',
                'type' => (string) ($message['type'] ?? 'unknown'),
                'body' => $message['text']['body'] ?? null,
                'status' => 'received',
                'occurred_at' => $inboundAt,
            ],
        );

        $conversation->forceFill([
            'business_phone' => config('services.whatsapp.display_phone') ?: $conversation->business_phone,
            'customer_name' => $customerName ?: $conversation->customer_name,
            'assigned_adviser_id' => $number->adviser_id,
            'last_inbound_at' => $conversation->last_inbound_at?->greaterThan($inboundAt)
                ? $conversation->last_inbound_at : $inboundAt,
        ])->save();
    }

    private function updateStatus(array $status): void
    {
        $message = WhatsappMessage::where('provider_message_id', $status['id'] ?? '')->first();

        if (! $message || $message->direction !== 'outgoing') {
            return;
        }

        $rank = ['submitting' => 0, 'accepted' => 1, 'sent' => 2, 'delivered' => 3, 'read' => 4];
        $newStatus = (string) ($status['status'] ?? '');

        if ($newStatus === 'failed' || (($rank[$newStatus] ?? -1) > ($rank[$message->status] ?? -1))) {
            $message->update(['status' => $newStatus]);
        }
    }
}
