<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\TwilioCallController;
use App\Models\VoiceCall;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TwilioVoiceTokenTest extends TestCase
{
    public function test_token_grants_browser_calls_only_to_the_configured_twiml_app(): void
    {
        config()->set('services.twilio', [
            'account_sid' => 'AC'.str_repeat('1', 32),
            'api_key' => 'SK'.str_repeat('2', 32),
            'api_secret' => 'test-secret',
            'twiml_app_sid' => 'AP'.str_repeat('3', 32),
            'from' => '+15551234567',
        ]);
        $request = Request::create('/api/twilio/token');
        $request->attributes->set('adviser', (object) ['id' => 42]);

        $response = (new TwilioCallController)->token($request);
        $jwt = $response->getData(true)['token'];
        $claims = json_decode(base64_decode(strtr(explode('.', $jwt)[1], '-_', '+/')), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame('adviser_42', $claims['grants']['identity']);
        $this->assertSame('AP'.str_repeat('3', 32), $claims['grants']['voice']['outgoing']['application_sid']);
        $this->assertArrayNotHasKey('incoming', $claims['grants']['voice']);
    }

    public function test_missing_twiml_app_returns_configuration_error(): void
    {
        config()->set('services.twilio.twiml_app_sid', null);
        $request = Request::create('/api/twilio/token');
        $request->attributes->set('adviser', (object) ['id' => 42]);

        $response = (new TwilioCallController)->token($request);

        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_signed_customer_screen_asks_for_consent_before_connection(): void
    {
        $accountSid = 'AC'.str_repeat('1', 32);
        $url = 'https://example.test/api/webhooks/twilio/customer-screen/7';
        config()->set('services.twilio.auth_token', 'test-auth-token');
        config()->set('services.twilio.account_sid', $accountSid);
        config()->set('services.twilio.webhook_base_url', 'https://example.test');

        $request = Request::create('/api/webhooks/twilio/customer-screen/7', 'POST', ['AccountSid' => $accountSid]);
        $request->headers->set('X-Twilio-Signature', base64_encode(hash_hmac('sha1', $url.'AccountSid'.$accountSid, 'test-auth-token', true)));
        $voiceCall = new VoiceCall;
        $voiceCall->id = 7;

        $response = (new TwilioCallController)->screenCustomer($request, $voiceCall);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('customer-consent/7', $response->getContent());
        $this->assertStringContainsString('<Gather', $response->getContent());
        $this->assertStringNotContainsString('<Dial', $response->getContent());
    }

    public function test_customer_screen_rejects_unsigned_requests(): void
    {
        config()->set('services.twilio.auth_token', 'test-auth-token');
        $request = Request::create('/api/webhooks/twilio/customer-screen/7', 'POST');
        $voiceCall = new VoiceCall;
        $voiceCall->id = 7;

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);

        (new TwilioCallController)->screenCustomer($request, $voiceCall);
    }
}
