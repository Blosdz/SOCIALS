<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VoiceCall;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\Security\RequestValidator;

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

    public function token(Request $request): JsonResponse
    {
        $config = config('services.twilio');

        if (! $config['account_sid'] || ! $config['api_key'] || ! $config['api_secret'] || ! $config['twiml_app_sid'] || ! $config['from']) {
            return response()->json(['message' => 'Configura las credenciales de Voice y el SID de la TwiML App.'], 503);
        }

        $token = new AccessToken(
            $config['account_sid'],
            $config['api_key'],
            $config['api_secret'],
            3600,
            'adviser_'.$request->attributes->get('adviser')->id,
        );
        $token->addGrant((new VoiceGrant)->setOutgoingApplicationSid($config['twiml_app_sid'])->setIncomingAllow(false));

        return response()->json(['token' => $token->toJWT()])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'recording_consent' => ['accepted'],
        ]);
        $config = config('services.twilio');

        if (! $config['account_sid'] || ! $config['auth_token'] || ! $config['api_key'] || ! $config['api_secret'] || ! $config['twiml_app_sid'] || ! $config['from']) {
            return response()->json(['message' => 'Configura las credenciales de Voice y el SID de la TwiML App.'], 503);
        }

        $adviser = $request->attributes->get('adviser');
        $voiceCall = VoiceCall::create([
            'adviser_id' => $adviser->id,
            'client_phone' => $data['client_phone'],
            'adviser_phone' => null,
            'status' => 'initiating',
        ]);

        return response()->json(['call' => $voiceCall->only(['id', 'status'])], 201);
    }

    public function cancel(Request $request, VoiceCall $voiceCall): JsonResponse
    {
        abort_unless($voiceCall->adviser_id === $request->attributes->get('adviser')->id, 404);

        VoiceCall::query()
            ->whereKey($voiceCall->id)
            ->whereIn('status', ['initiating', 'dialing'])
            ->update(['status' => 'failed']);

        return response()->json(['ok' => true]);
    }

    public function browserVoice(Request $request)
    {
        $this->validateTwilioRequest($request);
        $from = (string) $request->input('From');
        $callId = $request->input('CallId');
        $callSid = (string) $request->input('CallSid');

        if (! preg_match('/^client:adviser_(\d+)$/', $from, $matches) || ! ctype_digit((string) $callId) || ! preg_match('/^CA[a-fA-F0-9]{32}$/', $callSid)) {
            return $this->twiml('<Response><Hangup/></Response>');
        }

        $voiceCall = DB::transaction(function () use ($callId, $callSid, $matches) {
            $voiceCall = VoiceCall::query()
                ->whereKey($callId)
                ->where('adviser_id', (int) $matches[1])
                ->where('status', 'initiating')
                ->lockForUpdate()
                ->first();

            if ($voiceCall) {
                $voiceCall->update(['twilio_call_sid' => $callSid, 'status' => 'dialing']);
            }

            return $voiceCall;
        });

        if (! $voiceCall) {
            return $this->twiml('<Response><Hangup/></Response>');
        }

        $dialStatusUrl = e($this->webhookUrl("/api/webhooks/twilio/dial-status/{$voiceCall->id}"));
        $screenUrl = e($this->webhookUrl("/api/webhooks/twilio/customer-screen/{$voiceCall->id}"));
        $clientPhone = e($voiceCall->client_phone);
        $callerId = e(config('services.twilio.from'));

        return $this->twiml('<Response><Dial callerId="'.$callerId.'" answerOnBridge="true" action="'.$dialStatusUrl.'" method="POST"><Number url="'.$screenUrl.'" method="POST">'.$clientPhone.'</Number></Dial></Response>');
    }

    public function screenCustomer(Request $request, VoiceCall $voiceCall)
    {
        $this->validateTwilioRequest($request);
        $consentUrl = e($this->webhookUrl("/api/webhooks/twilio/customer-consent/{$voiceCall->id}"));

        return $this->twiml('<Response><Gather action="'.$consentUrl.'" method="POST" numDigits="1" timeout="15">'
            .'<Say language="es-PE">Esta llamada puede ser grabada y transcrita. Si acepta, pulse uno para continuar.</Say>'
            .'</Gather><Hangup/></Response>');
    }

    public function customerConsent(Request $request, VoiceCall $voiceCall)
    {
        $this->validateTwilioRequest($request);

        if ($request->input('Digits') !== '1') {
            $voiceCall->update(['status' => 'consent_declined']);

            return $this->twiml('<Response><Hangup/></Response>');
        }

        if (! $voiceCall->twilio_call_sid || VoiceCall::query()->whereKey($voiceCall->id)->where('status', 'dialing')->update(['status' => 'recording-starting']) !== 1) {
            return $this->twiml('<Response><Hangup/></Response>');
        }

        try {
            $config = config('services.twilio');
            $recordingUrl = $this->webhookUrl("/api/webhooks/twilio/recording/{$voiceCall->id}");
            $response = Http::asForm()->withBasicAuth($config['account_sid'], $config['auth_token'])
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Calls/{$voiceCall->twilio_call_sid}/Recordings.json", [
                    'RecordingChannels' => 'dual',
                    'RecordingStatusCallback' => $recordingUrl,
                    'RecordingStatusCallbackMethod' => 'POST',
                    'RecordingStatusCallbackEvent' => 'completed',
                ]);
            $response->throw();
            $voiceCall->update(['status' => 'in-progress', 'recording_sid' => $response->json('sid')]);

            return $this->twiml('<Response><Say language="es-PE">Conectando con su asesor.</Say></Response>');
        } catch (Throwable $exception) {
            report($exception);
            $voiceCall->update(['status' => 'failed']);

            return $this->twiml('<Response><Say language="es-PE">No se pudo iniciar la llamada.</Say><Hangup/></Response>');
        }
    }

    public function dialStatus(Request $request, VoiceCall $voiceCall)
    {
        $this->validateTwilioRequest($request);

        if (! in_array($voiceCall->status, ['recorded', 'failed', 'consent_declined'], true)) {
            $voiceCall->update(['status' => Str::lower((string) $request->input('DialCallStatus', 'completed'))]);
        }

        return $this->twiml('<Response><Hangup/></Response>');
    }

    public function browserStatus(Request $request): JsonResponse
    {
        $this->validateTwilioRequest($request);
        $voiceCall = VoiceCall::query()->where('twilio_call_sid', $request->input('CallSid'))->first();

        if ($voiceCall && ! in_array($voiceCall->status, ['recorded', 'failed', 'consent_declined'], true)) {
            $voiceCall->update(['status' => Str::lower((string) $request->input('CallStatus', 'completed'))]);
        }

        return response()->json(['ok' => true]);
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
        $validator = new RequestValidator($authToken);
        $algorithm = RequestValidator::getSignatureAlgorithm($request->header(RequestValidator::SIGNATURE_SETTINGS_HEADER));

        if ($request->isJson()) {
            if (! $validator->validate($signature, $url, $request->getContent(), $algorithm)) {
                throw new HttpException(403, 'Invalid Twilio signature.');
            }

            $accountSid = $request->input('accountId');
            if ($accountSid && ! hash_equals((string) config('services.twilio.account_sid'), (string) $accountSid)) {
                throw new HttpException(403, 'Unexpected Twilio account.');
            }

            return;
        }

        if (! $validator->validate($signature, $url, $request->post(), $algorithm)) {
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
