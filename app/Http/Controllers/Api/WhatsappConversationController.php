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
        $accessToken = config('services.whatsapp.access_token');
        $version = (string) config('services.whatsapp.api_version');

        if (! $accessToken || ! in_array($conversation->phone_number_id, $allowedNumbers, true)
            || ! preg_match('/^v[0-9]+\.[0-9]+$/', $version)) {
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
            $response = Http::withToken($accessToken)
                ->connectTimeout(3)
                ->timeout(10)
                ->post("https://graph.facebook.com/{$version}/{$conversation->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($conversation->customer_phone, '+'),
                    'type' => 'text',
                    'text' => ['body' => $data['body']],
                ]);
        } catch (ConnectionException) {
            $message->update(['status' => 'unknown']);

            return response()->json(['message' => 'No se pudo confirmar el envío. Revisa la conversación antes de reenviar.'], 503);
        }

        $providerId = $response->json('messages.0.id');

        if (! $response->successful() || ! is_string($providerId) || $providerId === '') {
            $message->update(['status' => 'failed']);

            return response()->json(['message' => 'WhatsApp rechazó el mensaje. Revisa la configuración o la ventana de respuesta.'], 502);
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
