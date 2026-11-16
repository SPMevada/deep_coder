<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $data['email'])->first();
        // dd(Hash::check($data['password'], $user->password), $data['password']);
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account is disabled. Please contact your administrator.',
            ]);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Login successful.',
                'user' => $this->userPayload($user),
                'redirect' => $request->session()->pull('url.intended', route('dashboard')),
            ]);
        }

        return response()->json([
            'message' => 'Login successful.',
            'token' => $user->createToken($data['device_name'] ?? 'api')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
            'redirect' => redirect()->route('dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department' => $user->department?->name,
        ];
    }
}
