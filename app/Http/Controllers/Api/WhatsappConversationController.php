<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsappConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adviserId = $request->attributes->get('adviser')->id;
        $allowedNumbers = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));
        $phoneNumberIds = WhatsappNumber::where('adviser_id', $adviserId)
            ->whereIn('phone_number_id', $allowedNumbers)->pluck('phone_number_id');
        $conversations = WhatsappConversation::query()
            ->whereIn('phone_number_id', $phoneNumberIds)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $conversations]);
    }

    public function show(Request $request, WhatsappConversation $conversation): JsonResponse
    {
        $this->assertOwnedNumber($request, $conversation);

        return response()->json([
            'data' => $conversation,
            'messages' => $conversation->messages()->orderBy('occurred_at')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function send(Request $request, WhatsappConversation $conversation): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:4096']]);
        $adviserId = $request->attributes->get('adviser')->id;
        $this->assertOwnedNumber($request, $conversation);

        if (! $conversation->last_inbound_at?->greaterThan(now()->subDay())) {
            return response()->json(['message' => 'La ventana de respuesta expiró. Se requiere una plantilla aprobada.'], 422);
        }

        $allowedNumbers = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));
        $apiKey = config('services.whatsapp.api_key');
        $apiUrl = rtrim((string) config('services.whatsapp.api_url'), '/');
        $recipient = config('services.whatsapp.recipient') ?: ltrim($conversation->customer_phone, '+');
        $recipientType = config('services.whatsapp.recipient_type', 'phone');

        if (! $apiKey || ! $recipient || ! in_array($recipientType, ['phone', 'bsuid'], true)
            || ! preg_match('/^https:\/\//', $apiUrl)
            || ! in_array($conversation->phone_number_id, $allowedNumbers, true)) {
            return response()->json(['message' => 'WhatsApp no está configurado para este número.'], 503);
        }

        $message = WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'type' => 'text',
            'body' => $data['body'],
            'status' => 'submitting',
            'sent_by_adviser_id' => $adviserId,
            'occurred_at' => now(),
        ]);

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'type' => 'text',
                'text' => ['body' => $data['body']],
            ];

            if ($recipientType === 'phone') {
                $payload['recipient_type'] = 'individual';
                $payload['to'] = $recipient;
            } else {
                $payload['recipient'] = $recipient;
            }

            $response = Http::withHeaders(['D360-API-KEY' => $apiKey])
                ->connectTimeout(3)
                ->timeout(10)
                ->post("{$apiUrl}/messages", $payload);
        } catch (ConnectionException) {
            $message->update(['status' => 'unknown']);

            return response()->json(['message' => 'No se pudo confirmar el envío. Revisa la conversación antes de reenviar.'], 503);
        }

        $providerId = $response->json('messages.0.id');

        if (! $response->successful() || ! is_string($providerId) || $providerId === '') {
            $message->update(['status' => 'failed']);

            return response()->json(['message' => '360dialog rechazó el mensaje. Revisa la API key, el destinatario de prueba y el límite del sandbox.'], 502);
        }

        $message->update(['provider_message_id' => $providerId, 'status' => 'accepted']);
        $conversation->touch();

        return response()->json(['data' => $message], 201);
    }

    private function assertOwnedNumber(Request $request, WhatsappConversation $conversation): void
    {
        $allowedNumbers = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));

        if (! in_array($conversation->phone_number_id, $allowedNumbers, true)
            || ! WhatsappNumber::whereKey($conversation->phone_number_id)
                ->where('adviser_id', $request->attributes->get('adviser')->id)->exists()) {
            abort(404);
        }
    }
}
