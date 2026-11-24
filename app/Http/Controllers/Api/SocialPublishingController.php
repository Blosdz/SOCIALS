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
use RuntimeException;
use Throwable;

class SocialPublishingController extends Controller
{
    public function publish(Request $request, MarketingContent $marketingContent, SocialAccountTokenService $tokens): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:linkedin,tiktok'],
            'title' => ['required_if:provider,tiktok', 'string', 'max:90'],
            'description' => ['nullable', 'string', 'max:4000'],
            'privacy_level' => ['required_if:provider,tiktok', 'in:PUBLIC_TO_EVERYONE,MUTUAL_FOLLOW_FRIENDS,FOLLOWER_OF_CREATOR,SELF_ONLY'],
            'allow_comment' => ['boolean'],
            'commercial_disclosure' => ['boolean'],
            'brand_organic' => ['boolean'],
            'brand_content' => ['boolean'],
            'terms_accepted' => ['accepted_if:provider,tiktok'],
        ]);
        abort_unless($marketingContent->is_active, 404);

        $adviser = $request->attributes->get('adviser');
        $account = SocialAccount::query()
            ->where('adviser_id', $adviser->id)
            ->where('provider', $data['provider'])
            ->first();

        if (! $account) {
            return response()->json(['message' => 'Conecta esta cuenta desde Integraciones antes de publicar.'], 409);
        }

        $requiredScope = $data['provider'] === 'tiktok' ? 'video.publish' : 'w_member_social';
        if (! in_array($requiredScope, $account->scopes ?? [], true)) {
            return response()->json(['message' => 'Reconecta la cuenta desde Integraciones para autorizar la publicación por API.'], 409);
        }

        try {
            return $data['provider'] === 'linkedin'
                ? $this->publishToLinkedIn($adviser, $account, $marketingContent, $tokens->validAccessToken($account))
                : $this->publishToTikTok($adviser, $marketingContent, $tokens->validAccessToken($account), $data);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'La plataforma rechazó la publicación. Revisa la conexión y vuelve a intentarlo.'], 502);
        }
    }

    public function tiktokCreatorInfo(Request $request, SocialAccountTokenService $tokens): JsonResponse
    {
        $account = SocialAccount::query()
            ->where('adviser_id', $request->attributes->get('adviser')->id)
            ->where('provider', 'tiktok')
            ->first();

        if (! $account) {
            return response()->json(['message' => 'Conecta TikTok desde Integraciones antes de publicar.'], 409);
        }

        if (! in_array('video.publish', $account->scopes ?? [], true)) {
            return response()->json(['message' => 'Reconecta TikTok desde Integraciones para autorizar video.publish.'], 409);
        }

        try {
            $creator = $this->fetchTikTokCreatorInfo($tokens->validAccessToken($account));
            if (empty($creator['privacy_level_options'])) {
                return response()->json(['message' => 'TikTok no habilitó opciones de privacidad para esta cuenta.'], 409);
            }

            return response()->json(['data' => [
                'creator_nickname' => $creator['creator_nickname'] ?? $account->display_name,
                'privacy_level_options' => $creator['privacy_level_options'] ?? [],
                'comment_disabled' => (bool) ($creator['comment_disabled'] ?? false),
            ]]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'TikTok no devolvió los permisos de publicación. Revisa la conexión de la cuenta.'], 502);
        }
    }

    public function publicationStatus(Request $request, SocialPublication $socialPublication, SocialAccountTokenService $tokens): JsonResponse
    {
        abort_unless($socialPublication->adviser_id === $request->attributes->get('adviser')->id && $socialPublication->provider === 'tiktok', 404);

        if (in_array($socialPublication->status, ['published', 'failed'], true)) {
            return response()->json(['status' => $socialPublication->status, 'message' => $socialPublication->message]);
        }

        $account = SocialAccount::query()
            ->where('adviser_id', $socialPublication->adviser_id)
            ->where('provider', 'tiktok')
            ->first();

        if (! $account || ! $socialPublication->external_id) {
            return response()->json(['message' => 'La conexión con TikTok ya no está disponible.'], 409);
        }

        try {
            $response = Http::withToken($tokens->validAccessToken($account))
                ->acceptJson()
                ->timeout(20)
                ->post('https://open.tiktokapis.com/v2/post/publish/status/fetch/', [
                    'publish_id' => $socialPublication->external_id,
                ]);
            $response->throw();
            $payload = $response->json();
            if (($payload['error']['code'] ?? null) !== 'ok') {
                return response()->json(['message' => $payload['error']['message'] ?? 'TikTok no devolvió el estado.'], 502);
            }

            $status = $payload['data']['status'] ?? null;
            if ($status === 'PUBLISH_COMPLETE') {
                $socialPublication->update(['status' => 'published', 'published_at' => now(), 'message' => 'Publicado en TikTok.']);
            } elseif ($status === 'FAILED') {
                $socialPublication->update(['status' => 'failed', 'message' => 'TikTok no pudo publicar: '.($payload['data']['fail_reason'] ?? 'error desconocido')]);
            }

            return response()->json(['status' => $socialPublication->status, 'message' => $socialPublication->message]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'No se pudo consultar el estado en TikTok.'], 502);
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

        if ($response->failed() && str_contains($response->body(), 'Content is a duplicate')) {
            return response()->json([
                'code' => 'duplicate_post',
                'message' => 'LinkedIn detectó que este contenido ya fue publicado. Revisa tu perfil antes de volver a intentarlo.',
            ], 409);
        }

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

    private function fetchTikTokCreatorInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->asJson()
            ->acceptJson()
            ->timeout(20)
            ->send('POST', 'https://open.tiktokapis.com/v2/post/publish/creator_info/query/');
        $response->throw();
        $payload = $response->json();

        if (($payload['error']['code'] ?? null) !== 'ok') {
            throw new RuntimeException($payload['error']['message'] ?? 'TikTok no devolvió los permisos de publicación.');
        }

        return $payload['data'] ?? [];
    }

    private function publishToTikTok($adviser, MarketingContent $content, string $accessToken, array $data): JsonResponse
    {
        $images = array_values(array_map(
            fn (string $image): string => filter_var($image, FILTER_VALIDATE_URL) ? $image : url($image),
            array_filter($content->images ?? [], 'is_string'),
        ));
        $verifiedPrefix = rtrim((string) config('services.tiktok.media_domain'), '/').'/';

        if (! $images || $verifiedPrefix === '/' || ! config('services.tiktok.media_domain_verified')) {
            return response()->json(['message' => 'TikTok requiere imágenes alojadas en una URL verificada por la app. Configura TIKTOK_MEDIA_DOMAIN y confirma su verificación antes de enviar contenido.'], 422);
        }

        foreach ($images as $image) {
            if (parse_url($image, PHP_URL_SCHEME) !== 'https' || ! str_starts_with($image, $verifiedPrefix)) {
                return response()->json(['message' => 'Las imágenes de esta publicación no están alojadas en la URL verificada de TikTok.'], 422);
            }
        }

        $creator = $this->fetchTikTokCreatorInfo($accessToken);
        if (! in_array($data['privacy_level'], $creator['privacy_level_options'] ?? [], true)) {
            return response()->json(['message' => 'La privacidad elegida ya no está disponible para esta cuenta. Vuelve a abrir TikTok.'], 422);
        }
        if (($creator['comment_disabled'] ?? false) && ($data['allow_comment'] ?? false)) {
            return response()->json(['message' => 'Esta cuenta no permite activar comentarios.'], 422);
        }
        if (($data['commercial_disclosure'] ?? false) && ! (($data['brand_organic'] ?? false) || ($data['brand_content'] ?? false))) {
            return response()->json(['message' => 'Indica si promocionas tu marca o una asociación pagada.'], 422);
        }
        if (! ($data['commercial_disclosure'] ?? false) && (($data['brand_organic'] ?? false) || ($data['brand_content'] ?? false))) {
            return response()->json(['message' => 'Activa la declaración comercial para etiquetar una marca.'], 422);
        }
        if (($data['brand_content'] ?? false) && $data['privacy_level'] === 'SELF_ONLY') {
            return response()->json(['message' => 'Una asociación pagada no puede publicarse con privacidad «Solo yo».'], 422);
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->post('https://open.tiktokapis.com/v2/post/publish/content/init/', [
                'post_info' => [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? '',
                    'privacy_level' => $data['privacy_level'],
                    'disable_comment' => ! ($data['allow_comment'] ?? false),
                    'brand_content_toggle' => (bool) ($data['brand_content'] ?? false),
                    'brand_organic_toggle' => (bool) ($data['brand_organic'] ?? false),
                    'auto_add_music' => false,
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_cover_index' => 0,
                    'photo_images' => array_slice($images, 0, 35),
                ],
                'post_mode' => 'DIRECT_POST',
                'media_type' => 'PHOTO',
            ]);
        $response->throw();

        $payload = $response->json();
        if (($payload['error']['code'] ?? null) !== 'ok') {
            return response()->json(['message' => $payload['error']['message'] ?? 'TikTok no aceptó el contenido.'], 422);
        }

        $publication = SocialPublication::create([
            'adviser_id' => $adviser->id,
            'marketing_content_id' => $content->id,
            'provider' => 'tiktok',
            'status' => 'processing',
            'external_id' => $payload['data']['publish_id'] ?? null,
            'message' => 'TikTok está procesando la publicación.',
        ]);

        return response()->json([
            'message' => 'TikTok está procesando la publicación.',
            'publication_id' => $publication->id,
        ], 202);
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
