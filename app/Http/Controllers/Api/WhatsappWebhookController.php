<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNumber;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if (! $verifyToken || $request->query('hub_mode') !== 'subscribe'
            || ! hash_equals($verifyToken, (string) $request->query('hub_verify_token'))) {
            return response('No autorizado.', 403);
        }

        return response((string) $request->query('hub_challenge'), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): JsonResponse
    {
        $secret = config('services.whatsapp.app_secret');

        if (! $secret) {
            return response()->json(['message' => 'Webhook no configurado.'], 503);
        }

        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expectedSignature, (string) $request->header('X-Hub-Signature-256'))) {
            return response()->json(['message' => 'Firma inválida.'], 403);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->json(['message' => 'Evento ignorado.']);
        }

        $allowedNumbers = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');

                if (! in_array($phoneNumberId, $allowedNumbers, true)) {
                    continue;
                }

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
        $customerPhone = (string) ($message['from'] ?? '');

        if ($messageId === '' || ! preg_match('/^[1-9][0-9]{7,14}$/', $customerPhone)) {
            return;
        }

        if (WhatsappMessage::where('provider_message_id', $messageId)->exists()) {
            return;
        }

        $number = WhatsappNumber::firstOrCreate(
            ['phone_number_id' => $phoneNumberId],
            ['display_phone' => $value['metadata']['display_phone_number'] ?? null],
        );

        if ($number->display_phone !== ($value['metadata']['display_phone_number'] ?? null)) {
            $number->update(['display_phone' => $value['metadata']['display_phone_number'] ?? null]);
        }

        $conversation = WhatsappConversation::firstOrCreate(
            ['phone_number_id' => $phoneNumberId, 'customer_phone' => '+'.$customerPhone],
            [
                'business_phone' => $value['metadata']['display_phone_number'] ?? null,
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
            'business_phone' => $value['metadata']['display_phone_number'] ?? $conversation->business_phone,
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
