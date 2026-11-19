<?php

namespace App\Http\Controllers;

use App\Models\MarketingContent;
use Illuminate\Http\Request;

class MarketingShareController extends Controller
{
    public function show(Request $request, MarketingContent $marketingContent)
    {
        abort_unless($marketingContent->is_active, 404);

        $attribution = collect([
            'name',
            'adviser',
            'idmktcontent',
            'title',
            'discount',
            'utm_source',
            'utm_medium',
            'utm_campaign',
        ])->mapWithKeys(function (string $key) use ($request): array {
            $value = $request->query($key);

            return is_scalar($value) && (string) $value !== '' ? [$key => (string) $value] : [];
        })->all();

        $purchaseUrl = $marketingContent->purchase_url;

        if ($purchaseUrl && $attribution) {
            $fragment = '';
            if (str_contains($purchaseUrl, '#')) {
                [$purchaseUrl, $fragment] = explode('#', $purchaseUrl, 2);
                $fragment = '#'.$fragment;
            }

            $separator = str_contains($purchaseUrl, '?') ? '&' : '?';
            $purchaseUrl .= $separator.http_build_query($attribution).$fragment;
        }

        $image = $marketingContent->images[0] ?? null;
        if ($image && ! preg_match('#^https?://#i', $image)) {
            $image = url($image);
        }

        return view('share.marketing-content', [
            'marketingContent' => $marketingContent,
            'shareUrl' => $request->fullUrl(),
            'purchaseUrl' => $purchaseUrl,
            'shareImage' => $image,
        ]);
    }
}
