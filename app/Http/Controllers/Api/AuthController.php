<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(
                ['message' => 'Invalid credentials.'],
                401
            );
        }

        /** @var \App\Models\User $user */
        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }
}
