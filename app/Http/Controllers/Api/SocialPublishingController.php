<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingContent;
use App\Models\SocialAccount;
use App\Models\SocialPublication;
use App\Services\SocialAccountTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class SocialPublishingController extends Controller
{
    public function publish(Request $request, MarketingContent $marketingContent, SocialAccountTokenService $tokens): JsonResponse
    {
        $data = $request->validate(['provider' => ['required', 'in:linkedin,tiktok']]);
        abort_unless($marketingContent->is_active, 404);

        $adviser = $request->attributes->get('adviser');
        $account = SocialAccount::query()
            ->where('adviser_id', $adviser->id)
            ->where('provider', $data['provider'])
            ->first();

        if (! $account) {
            return response()->json(['message' => 'Conecta esta cuenta desde Integraciones antes de publicar.'], 409);
        }

        try {
            return $data['provider'] === 'linkedin'
                ? $this->publishToLinkedIn($adviser, $account, $marketingContent, $tokens->validAccessToken($account))
                : $this->uploadToTikTok($adviser, $account, $marketingContent, $tokens->validAccessToken($account));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'La plataforma rechazó la publicación. Revisa la conexión y vuelve a intentarlo.'], 502);
        }
    }

    private function publishToLinkedIn($adviser, SocialAccount $account, MarketingContent $content, string $accessToken): JsonResponse
    {
        $version = config('services.linkedin.api_version');
        if (! $version) {
            return response()->json(['message' => 'Configura LINKEDIN_API_VERSION antes de publicar.'], 503);
        }

        $shareUrl = $this->attributedShareUrl($adviser, $content, 'linkedin');
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Linkedin-Version' => $version,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->acceptJson()
            ->timeout(30)
            ->post('https://api.linkedin.com/rest/posts', [
                'author' => 'urn:li:person:'.$account->provider_account_id,
                'commentary' => $content->title."\n\n".$content->description."\n\n".$shareUrl,
                'visibility' => 'PUBLIC',
                'distribution' => [
                    'feedDistribution' => 'MAIN_FEED',
                    'targetEntities' => [],
                    'thirdPartyDistributionChannels' => [],
                ],
                'lifecycleState' => 'PUBLISHED',
                'isReshareDisabledByAuthor' => false,
            ]);
        $response->throw();

        SocialPublication::create([
            'adviser_id' => $adviser->id,
            'marketing_content_id' => $content->id,
            'provider' => 'linkedin',
            'status' => 'published',
            'external_id' => $response->header('x-restli-id'),
            'published_at' => now(),
        ]);

        return response()->json(['message' => 'Publicado en LinkedIn.']);
    }

    private function uploadToTikTok($adviser, SocialAccount $account, MarketingContent $content, string $accessToken): JsonResponse
    {
        $images = array_values(array_filter($content->images ?? [], fn ($image) => is_string($image) && filter_var($image, FILTER_VALIDATE_URL)));
        $verifiedHost = parse_url((string) config('services.tiktok.media_domain'), PHP_URL_HOST);

        if (! $images || ! $verifiedHost || ! config('services.tiktok.media_domain_verified')) {
            return response()->json(['message' => 'TikTok requiere imágenes alojadas en un dominio verificado por la app. Configura TIKTOK_MEDIA_DOMAIN y su verificación antes de enviar contenido.'], 422);
        }

        foreach ($images as $image) {
            if (parse_url($image, PHP_URL_HOST) !== $verifiedHost) {
                return response()->json(['message' => 'Las imágenes de esta publicación no están alojadas en el dominio verificado de TikTok.'], 422);
            }
        }

        $shareUrl = $this->attributedShareUrl($adviser, $content, 'tiktok');
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->post('https://open.tiktokapis.com/v2/post/publish/content/init/', [
                'post_info' => [
                    'title' => mb_substr($content->title, 0, 90),
                    'description' => mb_substr($content->description."\n\n".$shareUrl, 0, 4000),
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_cover_index' => 0,
                    'photo_images' => array_slice($images, 0, 35),
                ],
                'post_mode' => 'MEDIA_UPLOAD',
                'media_type' => 'PHOTO',
            ]);
        $response->throw();

        $payload = $response->json();
        if (($payload['error']['code'] ?? null) !== 'ok') {
            return response()->json(['message' => $payload['error']['message'] ?? 'TikTok no aceptó el contenido.'], 422);
        }

        SocialPublication::create([
            'adviser_id' => $adviser->id,
            'marketing_content_id' => $content->id,
            'provider' => 'tiktok',
            'status' => 'draft_sent',
            'external_id' => $payload['data']['publish_id'] ?? null,
            'message' => 'Completa la publicación desde la notificación en la app de TikTok.',
        ]);

        return response()->json(['message' => 'Contenido enviado a TikTok. Abre la notificación en TikTok para terminar y publicar.'], 202);
    }

    private function attributedShareUrl($adviser, MarketingContent $content, string $source): string
    {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $adviser->name) ?: 'asesor';
        $name = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-') ?: 'asesor';
        $url = route('marketing.share', $content);

        return $url.'?'.http_build_query([
            'name' => $name,
            'adviser' => base64_encode((string) $adviser->id),
            'idmktcontent' => base64_encode((string) $content->id),
            'title' => trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $content->title) ?: 'contenido'), '-'),
            'utm_source' => $source,
            'utm_medium' => 'social',
            'utm_campaign' => 'adviser_share',
        ]);
    }
}
