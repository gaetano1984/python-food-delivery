<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'email'],
            'password' => ['required'],
            'nome' => ['required', 'string', 'max:255'],
        ]);

        if (User::where('email', $data['username'])->exists()) {
            return response()->json(['ko' => 'user already exists'], 409);
        }

        $user = new User();
        $user->email = $data['username'];
        $user->name = $data['nome'];
        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(['ok' => 'user created'], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['username'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['ko' => 'user not found'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['ok' => 'user found', 'token' => $token], 200);
    }
}
