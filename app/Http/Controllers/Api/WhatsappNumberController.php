<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsappNumberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adviserId = $request->attributes->get('adviser')->id;
        $allowed = array_values(array_unique(array_filter(array_map(
            'trim', explode(',', (string) config('services.whatsapp.phone_number_ids')),
        ))));
        $numbers = WhatsappNumber::whereIn('phone_number_id', $allowed)->get()->keyBy('phone_number_id');
        $connected = [];
        $available = [];

        foreach ($allowed as $phoneNumberId) {
            $number = $numbers->get($phoneNumberId);
            $item = [
                'phone_number_id' => $phoneNumberId,
                'display_phone' => $number?->display_phone,
            ];

            if ($number?->adviser_id === $adviserId) {
                $connected[] = $item;
            } elseif ($number?->adviser_id === null) {
                $available[] = $item;
            }
        }

        return response()->json([
            'connected' => $connected,
            'available' => $available,
            'configured' => (bool) (config('services.whatsapp.api_key')
                && config('services.whatsapp.webhook_token')),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['phone_number_id' => ['required', 'string', 'max:255']]);
        $phoneNumberId = $data['phone_number_id'];
        $allowed = array_filter(array_map('trim', explode(',', (string) config('services.whatsapp.phone_number_ids'))));

        if (! in_array($phoneNumberId, $allowed, true)) {
            return response()->json(['message' => 'Este número no está habilitado por la empresa.'], 422);
        }

        if (! config('services.whatsapp.api_key') || ! config('services.whatsapp.webhook_token')) {
            return response()->json(['message' => 'La conexión empresarial de WhatsApp aún no está configurada.'], 503);
        }

        $adviserId = $request->attributes->get('adviser')->id;

        DB::transaction(function () use ($phoneNumberId, $adviserId): void {
            DB::table('whatsapp_numbers')->insertOrIgnore([
                'phone_number_id' => $phoneNumberId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $number = WhatsappNumber::query()->lockForUpdate()->findOrFail($phoneNumberId);

            if ($number->adviser_id !== null && $number->adviser_id !== $adviserId) {
                abort(409, 'Este número ya está asignado a otro asesor.');
            }

            $number->update(['adviser_id' => $adviserId]);
            WhatsappConversation::where('phone_number_id', $phoneNumberId)
                ->update(['assigned_adviser_id' => $adviserId]);
        });

        return response()->json(['message' => 'Número conectado a tu bandeja.']);
    }

    public function destroy(Request $request, string $phoneNumberId): JsonResponse
    {
        $adviserId = $request->attributes->get('adviser')->id;

        DB::transaction(function () use ($phoneNumberId, $adviserId): void {
            $number = WhatsappNumber::query()->lockForUpdate()->findOrFail($phoneNumberId);

            if ($number->adviser_id !== $adviserId) {
                abort(404);
            }

            $number->update(['adviser_id' => null]);
            WhatsappConversation::where('phone_number_id', $phoneNumberId)
                ->update(['assigned_adviser_id' => null]);
        });

        return response()->json(['message' => 'Número desconectado de tu bandeja.']);
    }
}
