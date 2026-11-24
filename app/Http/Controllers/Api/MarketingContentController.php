<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingContent;
use Illuminate\Http\JsonResponse;

class MarketingContentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MarketingContent::query()
                ->where('is_active', true)
                ->latest()
                ->get(['id', 'title', 'description', 'images', 'purchase_url'])
                ->map(function (MarketingContent $content): array {
                    $data = $content->toArray();
                    $data['images'] = array_map(
                        fn (string $image): string => filter_var($image, FILTER_VALIDATE_URL) ? $image : url($image),
                        $content->images ?? [],
                    );

                    return $data;
                }),
        ]);
    }
}
