<?php

namespace App\Services;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SocialAccountTokenService
{
    public function validAccessToken(SocialAccount $account): string
    {
        if (! $account->token_expires_at || $account->token_expires_at->isAfter(now()->addMinutes(5))) {
            return $account->access_token;
        }

        if (! $account->refresh_token || ($account->refresh_token_expires_at && $account->refresh_token_expires_at->isPast())) {
            throw new RuntimeException('La conexión expiró. Vuelve a conectar tu cuenta social.');
        }

        $settings = config("services.{$account->provider}");
        $response = $account->provider === 'tiktok'
            ? Http::asForm()->timeout(20)->post('https://open.tiktokapis.com/v2/oauth/token/', [
                'client_key' => $settings['client_key'],
                'client_secret' => $settings['client_secret'],
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
            ])
            : Http::asForm()->timeout(20)->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'client_id' => $settings['client_id'],
                'client_secret' => $settings['client_secret'],
            ]);

        $response->throw();
        $tokens = $response->json();
        $account->forceFill([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : null,
            'refresh_token_expires_at' => isset($tokens['refresh_expires_in'])
                ? now()->addSeconds((int) $tokens['refresh_expires_in'])
                : $account->refresh_token_expires_at,
            'scopes' => isset($tokens['scope']) ? explode(',', $tokens['scope']) : $account->scopes,
        ])->save();

        return $account->access_token;
    }
}
