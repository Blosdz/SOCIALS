<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->where('adviser_id', $request->attributes->get('adviser')->id)
            ->orderBy('name')
            ->get(['id', 'name', 'dni', 'email', 'phone', 'extension']);

        return response()->json(['data' => $customers]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'digits:8'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
            'extension' => ['nullable', 'string', 'regex:/^\d{1,10}$/'],
        ]);

        $customer = Customer::create([
            ...$data,
            'adviser_id' => $request->attributes->get('adviser')->id,
        ]);

        return response()->json([
            'data' => $customer->only(['id', 'name', 'dni', 'email', 'phone', 'extension']),
        ], 201);
    }
}
