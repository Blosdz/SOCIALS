<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Adviser;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SocialAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adviser = $this->adviser($request);

        return response()->json([
            'data' => $adviser->socialAccounts()
                ->get(['provider', 'provider_account_id', 'display_name', 'token_expires_at', 'scopes'])
                ->keyBy('provider'),
        ]);
    }

    public function connect(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['linkedin', 'tiktok'], true), 404);

        $adviser = $this->adviser($request);
        $settings = config("services.{$provider}");
        $clientId = $provider === 'linkedin' ? $settings['client_id'] : $settings['client_key'];
        $redirectUri = $settings['redirect_uri'] ?: route('oauth.callback', $provider);

        if (! $clientId || ! $settings['client_secret']) {
            return response()->json(['message' => "Falta configurar las credenciales de {$provider} en el servidor."], 503);
        }

        $state = Str::random(64);
        Cache::put($this->stateKey($state), [
            'adviser_id' => $adviser->id,
            'provider' => $provider,
        ], now()->addMinutes(10));

        $authorizationUrl = $provider === 'linkedin'
            ? 'https://www.linkedin.com/oauth/v2/authorization?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => $settings['scopes'],
            ])
            : 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query([
                'client_key' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => $settings['scopes'],
            ]);

        return response()->json(['authorization_url' => $authorizationUrl]);
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['linkedin', 'tiktok'], true), 404);

        $state = $request->query('state');
        $stateData = is_string($state) ? Cache::pull($this->stateKey($state)) : null;

        if (! $stateData || $stateData['provider'] !== $provider || $request->filled('error')) {
            return redirect('/integrations?connection_error='.$provider);
        }

        $settings = config("services.{$provider}");
        $redirectUri = $settings['redirect_uri'] ?: route('oauth.callback', $provider);

        try {
            $tokenResponse = $provider === 'linkedin'
                ? Http::asForm()->timeout(20)->post('https://www.linkedin.com/oauth/v2/accessToken', [
                    'grant_type' => 'authorization_code',
                    'code' => $request->query('code'),
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret'],
                    'redirect_uri' => $redirectUri,
                ])
                : Http::asForm()->timeout(20)->post('https://open.tiktokapis.com/v2/oauth/token/', [
                    'client_key' => $settings['client_key'],
                    'client_secret' => $settings['client_secret'],
                    'code' => $request->query('code'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                ]);

            $tokenResponse->throw();
            $tokenData = $tokenResponse->json();
            $tokenData = $provider === 'tiktok' ? ($tokenData['data'] ?? $tokenData) : $tokenData;
            $accessToken = $tokenData['access_token'] ?? null;

            if (! $accessToken) {
                throw new \RuntimeException('El proveedor no devolvió un token de acceso.');
            }

            $profileResponse = $provider === 'linkedin'
                ? Http::withToken($accessToken)->timeout(20)->get('https://api.linkedin.com/v2/userinfo')
                : Http::withToken($accessToken)->timeout(20)->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,display_name,avatar_url',
                ]);

            $profileResponse->throw();
            $profile = $profileResponse->json();
            $profile = $provider === 'tiktok' ? ($profile['data']['user'] ?? []) : $profile;
            $externalId = $provider === 'linkedin' ? ($profile['sub'] ?? null) : ($profile['open_id'] ?? null);
            $displayName = $profile['name'] ?? $profile['display_name'] ?? null;

            if (! $externalId) {
                throw new \RuntimeException('No se pudo leer el identificador de la cuenta social.');
            }

            SocialAccount::updateOrCreate(
                ['adviser_id' => $stateData['adviser_id'], 'provider' => $provider],
                [
                    'provider_account_id' => $externalId,
                    'display_name' => $displayName,
                    'access_token' => $accessToken,
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds((int) $tokenData['expires_in']) : null,
                    'refresh_token_expires_at' => isset($tokenData['refresh_expires_in']) ? now()->addSeconds((int) $tokenData['refresh_expires_in']) : null,
                    'scopes' => isset($tokenData['scope']) ? preg_split('/[\s,]+/', trim($tokenData['scope'])) : [],
                ],
            );

            return redirect('/integrations?connected='.$provider);
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/integrations?connection_error='.$provider);
        }
    }

    public function disconnect(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['linkedin', 'tiktok'], true), 404);

        $account = $this->adviser($request)->socialAccounts()->where('provider', $provider)->first();

        if ($account && $provider === 'tiktok' && config('services.tiktok.client_key') && config('services.tiktok.client_secret')) {
            try {
                Http::asForm()->timeout(10)->post('https://open.tiktokapis.com/v2/oauth/revoke/', [
                    'client_key' => config('services.tiktok.client_key'),
                    'client_secret' => config('services.tiktok.client_secret'),
                    'token' => $account->access_token,
                ])->throw();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $account?->delete();

        return response()->json(['message' => 'Cuenta desconectada.']);
    }

    private function adviser(Request $request): Adviser
    {
        return $request->attributes->get('adviser');
    }

    private function stateKey(string $state): string
    {
        return 'social-oauth:'.hash('sha256', $state);
    }
}
