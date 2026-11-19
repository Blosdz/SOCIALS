<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoiceCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class TwilioCallController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $calls = VoiceCall::query()
            ->where('adviser_id', $request->attributes->get('adviser')->id)
            ->latest()
            ->limit(20)
            ->get(['id', 'client_phone', 'status', 'transcription_status', 'transcript', 'created_at']);

        return response()->json(['data' => $calls]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'adviser_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'recording_consent' => ['accepted'],
        ]);
        $config = config('services.twilio');

        if (! $config['account_sid'] || ! $config['auth_token'] || ! $config['from']) {
            return response()->json(['message' => 'Twilio aún no está configurado en el servidor.'], 503);
        }

        $adviser = $request->attributes->get('adviser');
        $voiceCall = VoiceCall::create([
            'adviser_id' => $adviser->id,
            'client_phone' => $data['client_phone'],
            'adviser_phone' => $data['adviser_phone'],
            'status' => 'initiating',
        ]);

        try {
            $voiceUrl = $this->webhookUrl("/api/webhooks/twilio/voice/{$voiceCall->id}");
            $statusUrl = $this->webhookUrl("/api/webhooks/twilio/call-status/{$voiceCall->id}");
            $twiml = '<Response><Gather action="'.e($voiceUrl).'" method="POST" numDigits="1" timeout="15">'
                .'<Say language="es-PE">Esta llamada puede ser grabada y transcrita. Si todas las personas han dado su consentimiento, pulse uno para continuar.</Say>'
                .'</Gather><Say language="es-PE">No se recibió confirmación. La llamada finalizará.</Say><Hangup/></Response>';

            $response = Http::asForm()->withBasicAuth($config['account_sid'], $config['auth_token'])
                ->timeout(25)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Calls.json", [
                    'To' => $data['client_phone'],
                    'From' => $config['from'],
                    'Twiml' => $twiml,
                    'StatusCallback' => $statusUrl,
                    'StatusCallbackMethod' => 'POST',
                    'StatusCallbackEvent' => 'completed',
                ]);
            $response->throw();

            $voiceCall->update([
                'twilio_call_sid' => $response->json('sid'),
                'status' => $response->json('status', 'queued'),
            ]);

            return response()->json([
                'message' => 'Llamada iniciada. La grabación comenzará después de la confirmación de consentimiento.',
                'call' => $voiceCall->only(['id', 'status']),
            ], 202);
        } catch (Throwable $exception) {
            report($exception);
            $voiceCall->update(['status' => 'failed']);

            return response()->json(['message' => 'No se pudo iniciar la llamada con Twilio.'], 502);
        }
    }

    public function consent(Request $request, VoiceCall $voiceCall)
    {
        $this->validateTwilioRequest($request);

        if ($request->input('Digits') !== '1') {
            $voiceCall->update(['status' => 'consent_declined']);

            return $this->twiml('<Response><Say language="es-PE">Gracias. No se grabará esta llamada.</Say><Hangup/></Response>');
        }

        $voiceCall->update(['status' => 'in-progress']);
        $recordingCallback = e($this->webhookUrl("/api/webhooks/twilio/recording/{$voiceCall->id}"));
        $adviserPhone = e($voiceCall->adviser_phone);
        $twiml = '<Response><Dial record="record-from-answer-dual" recordingStatusCallback="'.$recordingCallback.'" recordingStatusCallbackMethod="POST" recordingStatusCallbackEvent="completed absent"><Number>'.$adviserPhone.'</Number></Dial></Response>';

        return $this->twiml($twiml);
    }

    public function callStatus(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $this->validateTwilioRequest($request);
        $status = $request->input('CallStatus');

        if (is_string($status)) {
            $voiceCall->update(['status' => Str::lower($status)]);
        }

        return response()->json(['ok' => true]);
    }

    public function recording(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        $this->validateTwilioRequest($request);
        $recordingSid = $request->input('RecordingSid');
        $recordingStatus = $request->input('RecordingStatus');

        if ($recordingSid) {
            $voiceCall->update([
                'recording_sid' => $recordingSid,
                'recording_url' => $request->input('RecordingUrl'),
                'status' => $recordingStatus === 'completed' ? 'recorded' : $voiceCall->status,
            ]);

            if ($recordingStatus === 'completed') {
                $this->requestTranscription($voiceCall);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function transcription(Request $request): JsonResponse
    {
        $this->validateTwilioRequest($request);
        $sourceId = $request->input('sourceId');
        $voiceCall = VoiceCall::query()->where('recording_sid', $sourceId)->firstOrFail();
        $status = Str::upper((string) $request->input('status', ''));
        $sentences = $request->input('sentences', []);
        $transcript = is_array($sentences)
            ? collect($sentences)->pluck('text')->filter(fn ($text) => is_string($text) && $text !== '')->implode("\n")
            : null;

        $voiceCall->update([
            'transcription_sid' => $request->input('id', $voiceCall->transcription_sid),
            'transcription_status' => $status ?: $voiceCall->transcription_status,
            'transcript' => is_string($transcript) ? $transcript : $voiceCall->transcript,
        ]);

        return response()->json(['ok' => true]);
    }

    private function requestTranscription(VoiceCall $voiceCall): void
    {
        $config = config('services.twilio');

        if (! $config['api_key'] || ! $config['api_secret'] || ! $config['transcription_configuration_id'] || ! $voiceCall->recording_sid) {
            $voiceCall->update(['transcription_status' => 'configuration_required']);

            return;
        }

        try {
            $response = Http::withBasicAuth($config['api_key'], $config['api_secret'])
                ->acceptJson()
                ->timeout(25)
                ->post('https://voice.twilio.com/v3/Transcriptions', [
                    'sourceId' => $voiceCall->recording_sid,
                    'transcriptionConfigurationId' => $config['transcription_configuration_id'],
                ]);
            $response->throw();

            $voiceCall->update([
                'transcription_sid' => $response->json('transcription.id', $response->json('id')),
                'transcription_status' => Str::lower($response->json('status', 'pending')),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $voiceCall->update(['transcription_status' => 'failed']);
        }
    }

    private function validateTwilioRequest(Request $request): void
    {
        $authToken = config('services.twilio.auth_token');
        $signature = $request->header('X-Twilio-Signature');

        if (! $authToken || ! $signature) {
            throw new HttpException(403, 'Invalid Twilio signature.');
        }

        $url = $this->webhookUrl($request->getRequestUri());

        if ($request->isJson()) {
            $bodyHash = $request->query('bodySHA256');
            $rawBody = $request->getContent();

            if (! is_string($bodyHash) || ! hash_equals($bodyHash, hash('sha256', $rawBody))) {
                throw new HttpException(403, 'Invalid Twilio signature.');
            }

            $expected = base64_encode(hash_hmac('sha1', $url, $authToken, true));

            if (! hash_equals($expected, $signature)) {
                throw new HttpException(403, 'Invalid Twilio signature.');
            }

            $accountSid = $request->input('accountId');
            if ($accountSid && ! hash_equals((string) config('services.twilio.account_sid'), (string) $accountSid)) {
                throw new HttpException(403, 'Unexpected Twilio account.');
            }

            return;
        }

        $parameters = $request->post();
        ksort($parameters, SORT_STRING);
        $signaturePayload = $url;

        foreach ($parameters as $key => $value) {
            if (is_scalar($value)) {
                $signaturePayload .= $key.(string) $value;
            }
        }

        $expected = base64_encode(hash_hmac('sha1', $signaturePayload, $authToken, true));

        if (! hash_equals($expected, $signature)) {
            throw new HttpException(403, 'Invalid Twilio signature.');
        }

        $accountSid = $request->input('AccountSid');
        if ($accountSid && ! hash_equals((string) config('services.twilio.account_sid'), (string) $accountSid)) {
            throw new HttpException(403, 'Unexpected Twilio account.');
        }
    }

    private function webhookUrl(string $path): string
    {
        $baseUrl = config('services.twilio.webhook_base_url');

        return $baseUrl ? rtrim($baseUrl, '/').'/'.ltrim($path, '/') : url($path);
    }

    private function twiml(string $xml)
    {
        return response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }
}
