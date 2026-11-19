<?php

namespace App\Http\Middleware;

use App\Models\Adviser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdviser
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $adviser = $token ? Adviser::where('api_token', hash('sha256', $token))->first() : null;

        if (! $adviser) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $request->attributes->set('adviser', $adviser);

        return $next($request);
    }
}
