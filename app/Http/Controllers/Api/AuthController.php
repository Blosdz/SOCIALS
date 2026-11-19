<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Adviser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:advisers,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $adviser = Adviser::create($data);

        return $this->tokenResponse($adviser, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $adviser = Adviser::where('email', $data['email'])->first();

        if (! $adviser || ! Hash::check($data['password'], $adviser->password)) {
            throw ValidationException::withMessages(['email' => ['Las credenciales son incorrectas.']]);
        }

        return $this->tokenResponse($adviser);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('adviser')->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    private function tokenResponse(Adviser $adviser, int $status = 200): JsonResponse
    {
        $token = Str::random(80);
        $adviser->forceFill(['api_token' => hash('sha256', $token)])->save();

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'adviser' => $adviser->only(['id', 'name', 'email']),
        ], $status);
    }
}
